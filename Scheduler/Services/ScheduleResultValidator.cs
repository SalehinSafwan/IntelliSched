
using Scheduler.Models;

namespace Scheduler.Services;

// Independently checks a generated schedule against the scheduler
// input. It does not trust the solver: every hard constraint is
// re-derived from the input data.
public class ScheduleResultValidator
{
    public ScheduleValidationResult Validate(
        SchedulerInput input,
        List<ClassSession> sessions,
        List<ScheduledSessionResult> schedule)
    {
        var result = new ScheduleValidationResult();
        var errors = result.Errors;

        var slotLookup = input.TimeSlots.ToDictionary(s => s.Id);
        var roomLookup = input.Rooms.ToDictionary(r => r.Id);
        var teacherLookup = input.Teachers.ToDictionary(t => t.Id);
        var sectionLookup = input.Sections.ToDictionary(s => s.Id);
        var sessionLookup = sessions.ToDictionary(s => s.SessionKey);

        ExistingScheduleIndex existing;

        try
        {
            existing = new ExistingScheduleIndex(
                input.ExistingScheduleEntries, slotLookup);
        }
        catch (ArgumentException ex)
        {
            errors.Add(ex.Message);
            return result;
        }

        // =========================================================
        // 1. Every required session appears exactly once.
        // =========================================================
        foreach (var group in schedule.GroupBy(s => s.SessionKey))
        {
            if (!sessionLookup.ContainsKey(group.Key))
                errors.Add($"Unknown session {group.Key}.");
            else if (group.Count() > 1)
                errors.Add($"Session {group.Key} is scheduled {group.Count()} times.");
        }

        foreach (var session in sessions)
        {
            if (!schedule.Any(s => s.SessionKey == session.SessionKey))
                errors.Add($"Session {session.SessionKey} is not scheduled.");
        }

        var teacherSlotsUsed = new Dictionary<(int TeacherId, int SlotId), string>();
        var roomSlotsUsed = new Dictionary<(int RoomId, int SlotId), string>();
        var sectionSlotsUsed = new Dictionary<(int SectionId, int SlotId), string>();
        var theoryDays = new Dictionary<(int SectionId, int CourseId, string Day), string>();
        var newPeriods = new Dictionary<int, int>();

        foreach (var entry in schedule)
        {
            if (!sessionLookup.TryGetValue(entry.SessionKey, out var session))
                continue;

            string key = entry.SessionKey;

            // =====================================================
            // 2. The entry matches its required session.
            // =====================================================
            if (entry.OfferingId != session.OfferingId ||
                entry.CourseId != session.CourseId ||
                entry.SectionId != session.SectionId ||
                !string.Equals(entry.SessionType, session.SessionType,
                    StringComparison.OrdinalIgnoreCase) ||
                !string.Equals(entry.TeacherMode, session.TeacherMode,
                    StringComparison.OrdinalIgnoreCase))
            {
                errors.Add(
                    $"{key}: offering, course, section, type or teacher " +
                    "mode does not match the required session.");
            }

            // =====================================================
            // 3. Time slots: known, not breaks, same day,
            //    consecutive, and the required duration.
            // =====================================================
            var slots = new List<TimeSlotInput>();

            foreach (int slotId in entry.TimeSlotIds)
            {
                if (!slotLookup.TryGetValue(slotId, out var slot))
                    errors.Add($"{key}: unknown time slot {slotId}.");
                else if (slot.IsBreak)
                    errors.Add($"{key}: uses break slot {slotId}.");
                else
                    slots.Add(slot);
            }

            if (entry.TimeSlotIds.Distinct().Count() != entry.TimeSlotIds.Count)
                errors.Add($"{key}: repeats a time slot.");

            if (entry.TimeSlotIds.Count != session.DurationSlots)
            {
                errors.Add(
                    $"{key}: occupies {entry.TimeSlotIds.Count} slot(s) " +
                    $"but requires {session.DurationSlots}.");
            }

            slots = slots.OrderBy(s => s.SlotNumber).ToList();

            if (slots.Select(s => s.Day.ToUpperInvariant()).Distinct().Count() > 1)
                errors.Add($"{key}: spans more than one day.");

            for (int i = 1; i < slots.Count; i++)
            {
                if (slots[i].SlotNumber != slots[i - 1].SlotNumber + 1)
                {
                    errors.Add($"{key}: time slots are not consecutive.");
                    break;
                }
            }

            // =====================================================
            // 4. Room: exists, fits the section, suits the session.
            // =====================================================
            if (!roomLookup.TryGetValue(entry.RoomId, out var room))
            {
                errors.Add($"{key}: unknown room {entry.RoomId}.");
            }
            else if (sectionLookup.TryGetValue(session.SectionId, out var section) &&
                     !PlacementCandidateBuilder.RoomCanHost(
                         room, session, section.StudentCount))
            {
                errors.Add(
                    $"{key}: room {room.RoomCode} cannot host this " +
                    "session (type, capacity or lab eligibility).");
            }

            // =====================================================
            // 5. Teachers: count per mode, eligible, available.
            // =====================================================
            var teacherIds = entry.TeacherIds.Distinct().ToList();
            int expectedTeachers =
                SchedulerSolverService.IsJoint(session.TeacherMode) ? 2 : 1;

            if (teacherIds.Count != entry.TeacherIds.Count ||
                teacherIds.Count != expectedTeachers)
            {
                errors.Add(
                    $"{key}: has {entry.TeacherIds.Count} teacher(s); " +
                    $"{session.TeacherMode} sessions need {expectedTeachers} " +
                    "distinct teacher(s).");
            }

            foreach (int teacherId in teacherIds)
            {
                if (!teacherLookup.TryGetValue(teacherId, out var teacher))
                {
                    errors.Add($"{key}: unknown teacher {teacherId}.");
                    continue;
                }

                if (!teacher.SuitabilityScores.ContainsKey(session.CourseId))
                {
                    errors.Add(
                        $"{key}: teacher {teacher.EmployeeCode} is not " +
                        $"eligible for {session.CourseCode}.");
                }

                foreach (int slotId in entry.TimeSlotIds)
                {
                    if (!teacher.Availability.Any(a =>
                            a.TimeSlotId == slotId && a.IsAvailable))
                    {
                        errors.Add(
                            $"{key}: teacher {teacher.EmployeeCode} is " +
                            $"not available in slot {slotId}.");
                    }
                }

                newPeriods[teacherId] =
                    newPeriods.GetValueOrDefault(teacherId) + session.DurationSlots;
            }

            // =====================================================
            // 6. No double booking, among new sessions or with
            //    existing published entries.
            // =====================================================
            foreach (int slotId in entry.TimeSlotIds)
            {
                foreach (int teacherId in teacherIds)
                {
                    CheckFree(teacherSlotsUsed, (teacherId, slotId), key,
                        $"teacher {teacherId}", slotId, errors);

                    if (existing.TeacherSlots.Contains((teacherId, slotId)))
                        errors.Add($"{key}: teacher {teacherId} already teaches another batch in slot {slotId}.");
                }

                CheckFree(roomSlotsUsed, (entry.RoomId, slotId), key,
                    $"room {entry.RoomId}", slotId, errors);

                if (existing.RoomSlots.Contains((entry.RoomId, slotId)))
                    errors.Add($"{key}: room {entry.RoomId} is already used by another batch in slot {slotId}.");

                // A room hosting a published exam cannot hold a class
                // during the exam window on that weekday.
                if (slotLookup.TryGetValue(slotId, out var occupiedSlot))
                {
                    foreach (var exam in input.ExamRoomBlocks.Where(b =>
                                 b.RoomId == entry.RoomId && b.Blocks(occupiedSlot)))
                    {
                        errors.Add(
                            $"{key}: room {entry.RoomId} hosts the {exam.Label} exam " +
                            $"on {exam.ExamDate} ({exam.StartTime}–{exam.EndTime}).");
                    }
                }

                CheckFree(sectionSlotsUsed, (session.SectionId, slotId), key,
                    $"section {session.SectionId}", slotId, errors);

                if (existing.SectionSlots.Contains((session.SectionId, slotId)))
                    errors.Add($"{key}: section {session.SectionId} already has a class in slot {slotId}.");
            }

            // =====================================================
            // 7. A theory course meets a section at most once a day.
            // =====================================================
            if (SchedulerSolverService.IsTheory(session.SessionType) && slots.Count > 0)
            {
                string day = slots[0].Day.ToUpperInvariant();
                var dayKey = (session.SectionId, session.CourseId, day);

                if (theoryDays.TryGetValue(dayKey, out var other))
                    errors.Add($"{key}: {session.CourseCode} already meets on {day} ({other}).");
                else
                    theoryDays[dayKey] = key;

                if (existing.TheoryCoursesByDay.Contains(dayKey))
                    errors.Add($"{key}: {session.CourseCode} already meets on {day} in the published schedule.");
            }
        }

        // =========================================================
        // 8. Teacher-pair rule: each course has exactly two
        //    teachers across all sections. In every offering each
        //    pair teacher takes an equal share of the single
        //    sessions, and joint sessions use the full pair.
        // =========================================================
        var scheduled = schedule
            .Where(s => sessionLookup.ContainsKey(s.SessionKey))
            .ToList();

        foreach (var course in scheduled.GroupBy(s => s.CourseId))
        {
            var pair = course
                .SelectMany(s => s.TeacherIds)
                .Distinct()
                .OrderBy(id => id)
                .ToList();

            string courseCode = course.First().CourseCode;

            if (pair.Count != 2)
            {
                errors.Add(
                    $"{courseCode}: taught by {pair.Count} teacher(s) " +
                    "across its sections; exactly 2 are required.");
                continue;
            }

            foreach (var offering in course.GroupBy(s => s.OfferingId))
            {
                foreach (var joint in offering.Where(s =>
                             SchedulerSolverService.IsJoint(s.TeacherMode)))
                {
                    if (!joint.TeacherIds.OrderBy(id => id).SequenceEqual(pair))
                        errors.Add($"{joint.SessionKey}: joint session must use both pair teachers.");
                }

                var singles = offering
                    .Where(s => !SchedulerSolverService.IsJoint(s.TeacherMode))
                    .ToList();

                if (singles.Count == 0)
                    continue;

                foreach (int teacherId in pair)
                {
                    int taken = singles.Count(s => s.TeacherIds.Contains(teacherId));

                    if (taken != singles.Count / 2)
                    {
                        errors.Add(
                            $"Offering {offering.Key} ({courseCode}): teacher " +
                            $"{teacherId} has {taken} single session(s); " +
                            $"expected {singles.Count / 2}.");
                    }
                }
            }
        }

        // =========================================================
        // 9. Weekly workload limit, including existing periods.
        // =========================================================
        foreach (var (teacherId, periods) in newPeriods)
        {
            if (!teacherLookup.TryGetValue(teacherId, out var teacher))
                continue;

            int total = periods +
                existing.TeacherPeriods.GetValueOrDefault(teacherId);

            if (total > teacher.MaxWeeklyClasses)
            {
                errors.Add(
                    $"Teacher {teacher.EmployeeCode} has {total} periods, " +
                    $"above the maximum of {teacher.MaxWeeklyClasses}.");
            }
        }

        return result;
    }

    private static void CheckFree<TKey>(
        Dictionary<TKey, string> used,
        TKey resourceSlot,
        string sessionKey,
        string resourceLabel,
        int slotId,
        List<string> errors) where TKey : notnull
    {
        if (used.TryGetValue(resourceSlot, out var other))
        {
            errors.Add(
                $"{sessionKey}: {resourceLabel} is double-booked in " +
                $"slot {slotId} (also used by {other}).");
        }
        else
        {
            used[resourceSlot] = sessionKey;
        }
    }
}
