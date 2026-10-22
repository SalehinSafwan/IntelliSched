
using Google.OrTools.Sat;
using Scheduler.Models;

namespace Scheduler.Services;

public class SchedulerSolverService
{
    private sealed class AssignmentOption
    {
        public PlacementCandidate Candidate { get; init; } = new();
        public ClassSession Session { get; init; } = new();

        // One teacher for "Single" sessions, the full pair for
        // "Joint" sessions (combined theory class and labs).
        public List<TeacherInput> Teachers { get; init; } = new();
        public BoolVar Variable { get; init; } = null!;
    }

    public SolveResult Solve(
        SchedulerInput input,
        List<ClassSession> sessions,
        List<PlacementCandidate> candidates)
    {
        var slotLookup = input.TimeSlots.ToDictionary(s => s.Id);

        ExistingScheduleIndex existingIndex;

        try
        {
            existingIndex = new ExistingScheduleIndex(
                input.ExistingScheduleEntries,
                slotLookup);
        }
        catch (ArgumentException ex)
        {
            return SolveResult.Failure(
                "INVALID_EXISTING_SCHEDULE", ex.Message);
        }

        // MaxWeeklyClasses is temporarily interpreted as a limit
        // on teaching periods. Rename it to MaxWeeklyPeriods later.
        foreach (var teacher in input.Teachers)
        {
            int existingPeriods =
                existingIndex.TeacherPeriods.GetValueOrDefault(
                    teacher.Id, 0);

            if (existingPeriods > teacher.MaxWeeklyClasses)
            {
                return SolveResult.Failure(
                    "EXISTING_WORKLOAD_EXCEEDS_LIMIT",
                    $"Teacher {teacher.EmployeeCode} has " +
                    $"{existingPeriods} existing teaching periods, " +
                    $"above the configured maximum of " +
                    $"{teacher.MaxWeeklyClasses}.");
            }
        }

        var model = new CpModel();
        var options = new List<AssignmentOption>();

        var availableSlots = input.Teachers.ToDictionary(
            t => t.Id,
            t => t.Availability
                .Where(a => a.IsAvailable)
                .Select(a => a.TimeSlotId)
                .ToHashSet());

        // =========================================================
        // 1. Teacher pairs.
        //
        //    pair[course, teacher] = 1 iff the teacher belongs to the
        //    pair teaching this course. Every course has exactly two
        //    teachers, shared by all sections of the batch. Only
        //    teachers with a suitability score are eligible.
        // =========================================================
        var eligibleTeachers = sessions
            .Select(s => s.CourseId)
            .Distinct()
            .ToDictionary(
                courseId => courseId,
                courseId => input.Teachers
                    .Where(t => t.SuitabilityScores.ContainsKey(courseId))
                    .OrderBy(t => t.Id)
                    .ToList());

        var pairVariables = new Dictionary<(int CourseId, int TeacherId), BoolVar>();

        foreach (var (courseId, teachers) in eligibleTeachers)
        {
            foreach (var teacher in teachers)
            {
                pairVariables[(courseId, teacher.Id)] = model.NewBoolVar(
                    $"pair_course_{courseId}_teacher_{teacher.Id}");
            }

            model.Add(
                LinearExpr.Sum(teachers.Select(
                    t => pairVariables[(courseId, t.Id)])) == 2);
        }

        // =========================================================
        // 2. Create valid assignment options.
        // =========================================================
        var candidatesBySession = candidates.ToLookup(c => c.SessionKey);

        foreach (var session in sessions)
        {
            var courseTeachers = eligibleTeachers[session.CourseId];

            var teacherGroups = IsJoint(session.TeacherMode)
                ? TeacherPairs(courseTeachers)
                : courseTeachers.Select(t => new List<TeacherInput> { t }).ToList();

            foreach (var candidate in candidatesBySession[session.SessionKey])
            {
                if (!IsStructurallyValidCandidate(
                        session, candidate, slotLookup))
                {
                    continue;
                }

                foreach (var group in teacherGroups)
                {
                    // IMPORTANT: this check is inside the teacher loop.
                    // A teacher's existing commitment blocks only that
                    // teacher, not every possible teacher.
                    if (ConflictsWithExistingSchedule(
                            session,
                            candidate,
                            group,
                            slotLookup,
                            existingIndex))
                    {
                        continue;
                    }

                    // Every teacher of the group must be free for
                    // every occupied slot.
                    bool available = group.All(teacher =>
                        candidate.TimeSlotIds.All(slotId =>
                            availableSlots[teacher.Id].Contains(slotId)));

                    if (!available)
                        continue;

                    var variable = model.NewBoolVar(
                        $"assign_{options.Count}");

                    // An option may only use teachers of the course pair.
                    foreach (var teacher in group)
                    {
                        model.AddImplication(
                            variable,
                            pairVariables[(session.CourseId, teacher.Id)]);
                    }

                    options.Add(new AssignmentOption
                    {
                        Candidate = candidate,
                        Session = session,
                        Teachers = group,
                        Variable = variable
                    });
                }
            }
        }

        // =========================================================
        // 3. Every required session must have at least one option.
        // =========================================================
        var impossibleSessions = sessions
            .Where(session => !options.Any(o =>
                o.Session.SessionKey == session.SessionKey))
            .Select(session => session.SessionKey)
            .ToList();

        if (impossibleSessions.Count > 0)
        {
            var failure = SolveResult.Failure(
                "NO_CANDIDATES",
                "Some sessions have no eligible teacher, room, " +
                "and time placement after applying availability " +
                "and existing-schedule restrictions.");

            failure.Sessions = impossibleSessions;
            return failure;
        }

        var optionsBySession = options.ToLookup(o => o.Session.SessionKey);

        var optionsByTeacher = input.Teachers.ToDictionary(
            t => t.Id,
            t => options
                .Where(o => o.Teachers.Any(x => x.Id == t.Id))
                .ToList());

        // =========================================================
        // 4. Hard constraint: every session is assigned exactly once.
        // =========================================================
        foreach (var session in sessions)
        {
            model.Add(LinearExpr.Sum(
                optionsBySession[session.SessionKey]
                    .Select(o => o.Variable)) == 1);
        }

        // =========================================================
        // 5. Hard constraint: within each offering, each pair teacher
        //    takes an equal share of the "Single" sessions.
        //
        //    Theory: T1 and T2 are single, so each pair teacher
        //    teaches exactly one of them; T3 is combined (Joint).
        // =========================================================
        foreach (var offeringSessions in sessions.GroupBy(s => s.OfferingId))
        {
            var singleKeys = offeringSessions
                .Where(s => !IsJoint(s.TeacherMode))
                .Select(s => s.SessionKey)
                .ToHashSet();

            if (singleKeys.Count == 0)
                continue;

            int courseId = offeringSessions.First().CourseId;
            long sharePerTeacher = singleKeys.Count / 2;

            foreach (var teacher in eligibleTeachers[courseId])
            {
                var teacherSingles = optionsByTeacher[teacher.Id]
                    .Where(o => singleKeys.Contains(o.Session.SessionKey))
                    .Select(o => o.Variable);

                model.Add(
                    LinearExpr.Sum(teacherSingles) ==
                    LinearExpr.Term(
                        pairVariables[(courseId, teacher.Id)],
                        sharePerTeacher));
            }
        }

        // =========================================================
        // 6. Hard constraint: a section cannot have overlapping
        //    new classes.
        // =========================================================
        AddAtMostOneConstraint(
            model,
            options,
            o => new[] { o.Candidate.SectionId },
            o => o.Candidate.TimeSlotIds);

        // =========================================================
        // 7. Hard constraint: a room cannot host overlapping
        //    new classes.
        // =========================================================
        AddAtMostOneConstraint(
            model,
            options,
            o => new[] { o.Candidate.RoomId },
            o => o.Candidate.TimeSlotIds);

        // =========================================================
        // 8. Hard constraint: a teacher cannot teach overlapping
        //    new classes. Joint options block both teachers.
        // =========================================================
        AddAtMostOneConstraint(
            model,
            options,
            o => o.Teachers.Select(t => t.Id),
            o => o.Candidate.TimeSlotIds);

        // =========================================================
        // 9. Hard constraint: no theory course repeats for the same
        //    section on the same day.
        // =========================================================
        var theoryGroups = options
            .Where(o => IsTheory(o.Session.SessionType))
            .GroupBy(o => new
            {
                o.Session.SectionId,
                o.Session.CourseId,
                Day = slotLookup[
                    o.Candidate.StartTimeSlotId]
                    .Day.ToUpperInvariant()
            });

        foreach (var group in theoryGroups)
        {
            model.Add(
                LinearExpr.Sum(
                    group.Select(o => o.Variable)) <= 1);
        }

        // =========================================================
        // 10. Hard constraint: maximum weekly teaching periods.
        //
        //    Theory meeting = 1 period.
        //    Three-period lab = 3 periods.
        //    Joint sessions count fully for both teachers.
        //    Existing assignments count toward the same limit.
        // =========================================================
        foreach (var teacher in input.Teachers)
        {
            var teacherOptions = optionsByTeacher[teacher.Id];

            int existingPeriods =
                existingIndex.TeacherPeriods.GetValueOrDefault(
                    teacher.Id, 0);

            int remainingPeriods =
                teacher.MaxWeeklyClasses - existingPeriods;

            if (teacherOptions.Count == 0)
                continue;

            var variables = teacherOptions
                .Select(o => (LinearExpr)o.Variable)
                .ToArray();

            var durations = teacherOptions
                .Select(o => (long)o.Session.DurationSlots)
                .ToArray();

            model.Add(
                LinearExpr.WeightedSum(variables, durations)
                <= remainingPeriods);
        }


        // =========================================================
        // 11. SOFT OBJECTIVE
        //
        // Goal 1: Prefer suitable teacher pairs.
        // Goal 2: Prefer a more balanced teacher workload.
        //
        // Existing workload is included in the balancing penalty.
        // All hard constraints above remain unchanged.
        // =========================================================

        var objectiveVariables = new List<LinearExpr>();
        var objectiveCoefficients = new List<long>();

        // Indicator variables, read back after solving to report
        // how well each soft constraint was met.
        var studentGapVars = new List<BoolVar>();
        var teacherGapVars = new List<BoolVar>();
        var singlePeriodDayVars = new List<BoolVar>();
        var dailyTheoryExcessVars = new List<IntVar>();
        var dailyPeriodExcessVars = new List<IntVar>();


        // 11A. TEACHER-PAIR SUITABILITY + ROOM PRIORITY
        //
        // Suitability is rewarded once per (course, pair teacher).
        // The weight is deliberately modest so that the workload
        // penalty below can spread courses across qualified teachers.

        const long suitabilityWeight = 100;
        const long roomPriorityWeight = 100;

        foreach (var ((courseId, teacherId), pairVariable) in pairVariables)
        {
            double suitability = input.Teachers
                .First(t => t.Id == teacherId)
                .SuitabilityScores[courseId];

            objectiveVariables.Add(pairVariable);
            objectiveCoefficients.Add(
                (long)Math.Round(suitability * suitabilityWeight));
        }

        foreach (var option in options)
        {
            // Smaller room priority numbers represent higher-priority rooms.
            // Example: priority 1 is preferred over priority 5.
            long roomPenalty =
                (long)option.Candidate.RoomPriority * roomPriorityWeight;

            objectiveVariables.Add(option.Variable);
            objectiveCoefficients.Add(-roomPenalty);
        }


        // ---------------------------------------------------------
        // 11B. Workload-balancing penalty.
        //
        // Each teacher's total workload includes:
        //   existing published periods + newly assigned periods.
        //
        // We penalize each occupied workload level with an
        // increasing cost. This creates a convex, piecewise-linear
        // penalty that discourages concentrating every class on
        // the same teacher.
        //
        // Example marginal costs when BASE = 500:
        //   Period 1  ->  500
        //   Period 2  -> 1000
        //   Period 3  -> 1500
        //   ...
        //   Period 12 -> 6000
        //
        // Tune BASE after observing schedule quality.
        // ---------------------------------------------------------

        const long workloadPenaltyBase = 500;
        // If assignments remain too concentrated, increase 500 to 750 or 1000.
        // If teachers with substantially lower suitability scores receive assignments too readily, reduce it to 250 or 350.

        foreach (var teacher in input.Teachers)
        {
            int existingPeriods =
                existingIndex.TeacherPeriods.GetValueOrDefault(
                    teacher.Id, 0);

            var teacherOptions = optionsByTeacher[teacher.Id];

            // Total workload variable includes existing commitments.
            var totalPeriods = model.NewIntVar(
                existingPeriods,
                teacher.MaxWeeklyClasses,
                $"total_periods_teacher_{teacher.Id}");

            LinearExpr newPeriodsExpr =
                teacherOptions.Count == 0
                    ? LinearExpr.Constant(0)
                    : LinearExpr.WeightedSum(
                        teacherOptions
                            .Select(o => (LinearExpr)o.Variable)
                            .ToArray(),
                        teacherOptions
                            .Select(o => (long)o.Session.DurationSlots)
                            .ToArray());

            model.Add(
                totalPeriods == existingPeriods + newPeriodsExpr);

            // Add one Boolean indicator per workload level.
            //
            // levelOccupied = 1 iff totalPeriods >= level.
            //
            // Increasing the penalty for higher levels encourages
            // spreading assignments rather than overloading one
            // teacher while other qualified teachers remain free.
            for (int level = 1;
                 level <= teacher.MaxWeeklyClasses;
                 level++)
            {
                var levelOccupied = model.NewBoolVar(
                    $"teacher_{teacher.Id}_workload_level_{level}");

                model.Add(totalPeriods >= level)
                    .OnlyEnforceIf(levelOccupied);

                model.Add(totalPeriods <= level - 1)
                    .OnlyEnforceIf(levelOccupied.Not());

                long penalty = workloadPenaltyBase * level;

                // The model maximizes its objective, so penalties
                // have negative coefficients.
                objectiveVariables.Add(levelOccupied);
                objectiveCoefficients.Add(-penalty);
            }
        }


        // 11C. STUDENT TIMETABLE GAP MINIMIZATION
        // A gap is an empty TEACHING slot with at least one class
        // before it and at least one class after it on the same day.
        // Break slots are excluded.

        const long studentGapPenalty = 2500;
        //1000/2500/5000 - Weak/medium/compact timetable!

        // Key: (section ID, time slot ID)
        var sectionSlotOccupied = new Dictionary<(int SectionId, int SlotId), BoolVar>();

        // Build a Boolean occupancy variable for every section and
        // every non-break slot.
        foreach (var section in input.Sections)
        {
            foreach (var slot in input.TimeSlots.Where(s => !s.IsBreak))
            {
                var occupied = model.NewBoolVar(
                    $"section_{section.Id}_slot_{slot.Id}_occupied");

                var coveringVariables = options
                    .Where(o =>
                        o.Session.SectionId == section.Id &&
                        o.Candidate.TimeSlotIds.Contains(slot.Id))
                    .Select(o => (LinearExpr)o.Variable)
                    .ToArray();

                if (coveringVariables.Length == 0)
                {
                    model.Add(occupied == 0);
                }
                else
                {
                    // Exactly reflects whether a selected class occupies
                    // this section's slot. Existing section-slot constraints
                    // prevent overlapping classes.
                    model.Add(occupied == LinearExpr.Sum(coveringVariables));
                }

                sectionSlotOccupied[(section.Id, slot.Id)] = occupied;
            }
        }

        // Evaluate each section independently for each day.
        foreach (var section in input.Sections)
        {
            var dayGroups = input.TimeSlots
                .Where(s => !s.IsBreak)
                .GroupBy(s => s.Day);

            foreach (var dayGroup in dayGroups)
            {
                var teachingSlots = dayGroup
                    .OrderBy(s => s.SlotNumber)
                    .ToList();

                for (int i = 0; i < teachingSlots.Count; i++)
                {
                    var currentSlot = teachingSlots[i];

                    var currentOccupied =
                        sectionSlotOccupied[(section.Id, currentSlot.Id)];

                    var gap = model.NewBoolVar(
                        $"section_{section.Id}_day_{dayGroup.Key}_gap_before_slot_{currentSlot.Id}");

                    // A slot cannot be an internal gap unless there is
                    // at least one occupied teaching slot before it.
                    var hasBefore = model.NewBoolVar(
                        $"section_{section.Id}_day_{dayGroup.Key}_has_before_{currentSlot.Id}");

                    // Nor can it be an internal gap unless there is
                    // at least one occupied teaching slot after it.
                    var hasAfter = model.NewBoolVar(
                        $"section_{section.Id}_day_{dayGroup.Key}_has_after_{currentSlot.Id}");

                    var beforeVariables = teachingSlots
                        .Take(i)
                        .Select(s =>
                            (LinearExpr)sectionSlotOccupied[(section.Id, s.Id)])
                        .ToArray();

                    var afterVariables = teachingSlots
                        .Skip(i + 1)
                        .Select(s =>
                            (LinearExpr)sectionSlotOccupied[(section.Id, s.Id)])
                        .ToArray();

                    // hasBefore = OR(all earlier teaching-slot occupancies)
                    if (beforeVariables.Length == 0)
                    {
                        model.Add(hasBefore == 0);
                    }
                    else
                    {
                        var beforeSum = LinearExpr.Sum(beforeVariables);

                        model.Add(beforeSum >= 1).OnlyEnforceIf(hasBefore);
                        model.Add(beforeSum == 0).OnlyEnforceIf(hasBefore.Not());
                    }

                    // hasAfter = OR(all later teaching-slot occupancies)
                    if (afterVariables.Length == 0)
                    {
                        model.Add(hasAfter == 0);
                    }
                    else
                    {
                        var afterSum = LinearExpr.Sum(afterVariables);

                        model.Add(afterSum >= 1).OnlyEnforceIf(hasAfter);
                        model.Add(afterSum == 0).OnlyEnforceIf(hasAfter.Not());
                    }

                    // gap = hasBefore AND hasAfter AND NOT(currentOccupied)
                    model.Add(gap <= hasBefore);
                    model.Add(gap <= hasAfter);
                    model.Add(gap + currentOccupied <= 1);

                    model.Add(
                        gap >= hasBefore + hasAfter - currentOccupied - 1);

                    // Penalize each internal empty teaching slot.
                    objectiveVariables.Add(gap);
                    objectiveCoefficients.Add(-studentGapPenalty);
                    studentGapVars.Add(gap);
                }
            }
        }


        // 11F. AVOID SINGLE-PERIOD DAYS FOR STUDENTS
        // A day on which a section has exactly one teaching period
        // means coming to campus for a single 50-minute class.
        //
        // single >= occupied(s) - sum(other periods of that day)
        // forces single = 1 when only period s is occupied; with
        // two or more periods the right side is <= 0.

        const long singlePeriodDayPenalty = 5000;

        foreach (var section in input.Sections)
        {
            foreach (var dayGroup in input.TimeSlots
                         .Where(s => !s.IsBreak)
                         .GroupBy(s => s.Day))
            {
                var daySlots = dayGroup.ToList();
                var single = model.NewBoolVar(
                    $"section_{section.Id}_day_{dayGroup.Key}_single_period");

                foreach (var slot in daySlots)
                {
                    var others = daySlots
                        .Where(other => other.Id != slot.Id)
                        .Select(other => (LinearExpr)sectionSlotOccupied[(section.Id, other.Id)])
                        .ToArray();

                    model.Add(
                        single >= sectionSlotOccupied[(section.Id, slot.Id)]
                        - LinearExpr.Sum(others));
                }

                objectiveVariables.Add(single);
                objectiveCoefficients.Add(-singlePeriodDayPenalty);
                singlePeriodDayVars.Add(single);
            }
        }


        // 11D. TEACHER TIMETABLE GAP MINIMIZATION
        // Include both:
        //   1. Existing published classes in other batches.
        //   2. Classes selected by this solve.
        //
        // Break slots are excluded. An internal empty teaching slot
        // is penalized only if the teacher has a class before and after it.

        const long teacherGapPenalty = 1500;
        //500/1500/3000 - low/moderate/strong compact schedules.

        const int dailyTheoryLimit = 4;
        const int dailyPeriodLimit = 6;
        const long dailyLimitPenalty = 3000;

        // Theory classes a teacher already has per weekday in
        // published routines of other batches.
        var existingTheoryByTeacherDay = new Dictionary<(int TeacherId, string Day), int>();

        foreach (var entry in input.ExistingScheduleEntries.Where(e =>
                     e.IsActive && IsTheory(e.SessionType) && e.TimeSlotIds.Count > 0))
        {
            string day = slotLookup[entry.TimeSlotIds[0]].Day.ToUpperInvariant();

            foreach (var teacherId in entry.TeacherIds.Distinct())
            {
                existingTheoryByTeacherDay[(teacherId, day)] =
                    existingTheoryByTeacherDay.GetValueOrDefault((teacherId, day)) + 1;
            }
        }

        foreach (var teacher in input.Teachers)
        {
            // Existing published classes are fixed occupancy.
            var existingTeacherSlots = input.ExistingScheduleEntries
                .Where(e => e.IsActive && e.TeacherIds.Contains(teacher.Id))
                .SelectMany(e => e.TimeSlotIds)
                .ToHashSet();

            var teacherSlotOccupied = new Dictionary<int, BoolVar>();

            // Create occupancy variables for all teaching slots.
            foreach (var slot in input.TimeSlots.Where(s => !s.IsBreak))
            {
                var occupied = model.NewBoolVar(
                    $"teacher_{teacher.Id}_slot_{slot.Id}_occupied");

                // Existing published classes cannot be moved by this solve.
                if (existingTeacherSlots.Contains(slot.Id))
                {
                    model.Add(occupied == 1);
                }
                else
                {
                    // Assignment options that would give this teacher
                    // a class in this time slot.
                    var coveringVariables = optionsByTeacher[teacher.Id]
                        .Where(o => o.Candidate.TimeSlotIds.Contains(slot.Id))
                        .Select(o => (LinearExpr)o.Variable)
                        .ToArray();

                    if (coveringVariables.Length == 0)
                    {
                        model.Add(occupied == 0);
                    }
                    else
                    {
                        // The existing teacher-slot hard constraint must
                        // ensure at most one selected assignment per slot.
                        model.Add(
                            occupied == LinearExpr.Sum(coveringVariables));
                    }
                }

                teacherSlotOccupied[slot.Id] = occupied;
            }

            // Evaluate each weekday separately.
            var teacherDayGroups = input.TimeSlots
                .Where(s => !s.IsBreak)
                .GroupBy(s => s.Day);

            foreach (var dayGroup in teacherDayGroups)
            {
                var teachingSlots = dayGroup
                    .OrderBy(s => s.SlotNumber)
                    .ToList();

                // 11H. SOFT DAILY LIMITS
                // At most 4 theory classes and 6 periods (a lab is 3)
                // per teacher per day. Each unit above the limit is
                // penalized; existing published classes count too.
                string dayKey = dayGroup.Key.ToUpperInvariant();

                var dayTheoryOptions = optionsByTeacher[teacher.Id]
                    .Where(o => IsTheory(o.Session.SessionType) &&
                        string.Equals(
                            slotLookup[o.Candidate.StartTimeSlotId].Day,
                            dayGroup.Key,
                            StringComparison.OrdinalIgnoreCase))
                    .Select(o => (LinearExpr)o.Variable)
                    .ToArray();

                int existingTheory = existingTheoryByTeacherDay
                    .GetValueOrDefault((teacher.Id, dayKey));

                var theoryExcess = model.NewIntVar(
                    0, 50, $"teacher_{teacher.Id}_day_{dayGroup.Key}_theory_excess");

                model.Add(theoryExcess >=
                    LinearExpr.Sum(dayTheoryOptions) + existingTheory - dailyTheoryLimit);

                var periodExcess = model.NewIntVar(
                    0, 50, $"teacher_{teacher.Id}_day_{dayGroup.Key}_period_excess");

                model.Add(periodExcess >=
                    LinearExpr.Sum(teachingSlots
                        .Select(s => (LinearExpr)teacherSlotOccupied[s.Id])
                        .ToArray())
                    - dailyPeriodLimit);

                objectiveVariables.Add(theoryExcess);
                objectiveCoefficients.Add(-dailyLimitPenalty);
                objectiveVariables.Add(periodExcess);
                objectiveCoefficients.Add(-dailyLimitPenalty);
                dailyTheoryExcessVars.Add(theoryExcess);
                dailyPeriodExcessVars.Add(periodExcess);

                for (int i = 0; i < teachingSlots.Count; i++)
                {
                    var currentSlot = teachingSlots[i];
                    var currentOccupied =
                        teacherSlotOccupied[currentSlot.Id];

                    var hasBefore = model.NewBoolVar(
                        $"teacher_{teacher.Id}_day_{dayGroup.Key}_has_before_{currentSlot.Id}");

                    var hasAfter = model.NewBoolVar(
                        $"teacher_{teacher.Id}_day_{dayGroup.Key}_has_after_{currentSlot.Id}");

                    var gap = model.NewBoolVar(
                        $"teacher_{teacher.Id}_day_{dayGroup.Key}_gap_{currentSlot.Id}");

                    // Check for at least one occupied teaching slot earlier
                    // in the same day.
                    var beforeVariables = teachingSlots
                        .Take(i)
                        .Select(s =>
                            (LinearExpr)teacherSlotOccupied[s.Id])
                        .ToArray();

                    if (beforeVariables.Length == 0)
                    {
                        model.Add(hasBefore == 0);
                    }
                    else
                    {
                        var beforeSum = LinearExpr.Sum(beforeVariables);

                        model.Add(beforeSum >= 1)
                            .OnlyEnforceIf(hasBefore);

                        model.Add(beforeSum == 0)
                            .OnlyEnforceIf(hasBefore.Not());
                    }

                    // Check for at least one occupied teaching slot later
                    // in the same day.
                    var afterVariables = teachingSlots
                        .Skip(i + 1)
                        .Select(s =>
                            (LinearExpr)teacherSlotOccupied[s.Id])
                        .ToArray();

                    if (afterVariables.Length == 0)
                    {
                        model.Add(hasAfter == 0);
                    }
                    else
                    {
                        var afterSum = LinearExpr.Sum(afterVariables);

                        model.Add(afterSum >= 1)
                            .OnlyEnforceIf(hasAfter);

                        model.Add(afterSum == 0)
                            .OnlyEnforceIf(hasAfter.Not());
                    }

                    // gap = hasBefore AND hasAfter AND NOT(currentOccupied)
                    model.Add(gap <= hasBefore);
                    model.Add(gap <= hasAfter);
                    model.Add(gap + currentOccupied <= 1);

                    model.Add(
                        gap >= hasBefore + hasAfter - currentOccupied - 1);

                    // Add this soft penalty to the existing objective.
                    objectiveVariables.Add(gap);
                    objectiveCoefficients.Add(-teacherGapPenalty);
                    teacherGapVars.Add(gap);
                }
            }
        }


        // 11E. LAB TIMING PREFERENCE
        // Prefer labs that start in the morning or afternoon.
        // This is a soft preference; it does not remove valid candidates.

        const long labTimingReward = 3000;

        foreach (var option in options)
        {
            if (!IsLab(option.Session.SessionType))
                continue;

            var startSlot = slotLookup[option.Candidate.StartTimeSlotId];

            // Preferred start times:
            // Morning: before 11:30
            // Afternoon: 14:30 or later
            bool preferredStart =
                TimeSpan.TryParse(startSlot.StartTime, out var startTime)
                && (
                    startTime < new TimeSpan(11, 30, 0)
                    || startTime >= new TimeSpan(14, 30, 0)
                );

            if (preferredStart)
            {
                objectiveVariables.Add(option.Variable);
                objectiveCoefficients.Add(labTimingReward);
            }
        }

        // 11G. TEACHER TIME-OF-DAY PREFERENCES
        // Reward periods a teacher marked "preferred" and penalize
        // periods marked "avoid". Unavailable periods are already
        // excluded as a hard constraint.

        const long preferredPeriodReward = 400;
        const long avoidedPeriodPenalty = 800;

        var slotPreference = input.Teachers.ToDictionary(
            t => t.Id,
            t => t.Availability
                .Where(a => a.IsAvailable)
                .GroupBy(a => a.TimeSlotId)
                .ToDictionary(g => g.Key, g => g.First().Preference));

        foreach (var option in options)
        {
            long coefficient = 0;

            foreach (var teacher in option.Teachers)
            {
                foreach (int slotId in option.Candidate.TimeSlotIds)
                {
                    int preference = slotPreference[teacher.Id].GetValueOrDefault(slotId);

                    if (preference > 0)
                        coefficient += preferredPeriodReward;
                    else if (preference < 0)
                        coefficient -= avoidedPeriodPenalty;
                }
            }

            if (coefficient != 0)
            {
                objectiveVariables.Add(option.Variable);
                objectiveCoefficients.Add(coefficient);
            }
        }


        // 11I. SENIOR BATCHES GET MORNING SLOTS
        // Each morning period (starting before 12:00) is rewarded in
        // proportion to seniority: the most senior batch gets the full
        // reward, the most junior the smallest share.

        const long seniorMorningReward = 600;

        int batchCount = Math.Max(1, input.Batch.ActiveBatchCount);
        int seniority = Math.Clamp(input.Batch.SeniorityOrder, 1, batchCount);
        long morningReward =
            seniorMorningReward * (batchCount - seniority + 1) / batchCount;

        foreach (var option in options)
        {
            int morningPeriods = option.Candidate.TimeSlotIds
                .Count(id => IsMorning(slotLookup[id]));

            if (morningPeriods > 0)
            {
                objectiveVariables.Add(option.Variable);
                objectiveCoefficients.Add(morningReward * morningPeriods);
            }
        }

        // Keep this as the single final objective definition.
        model.Maximize(
            LinearExpr.WeightedSum(
                objectiveVariables,
                objectiveCoefficients));


        // =========================================================
        // 12. Solve the model.
        // =========================================================
        var solver = new CpSolver
        {
            StringParameters = "max_time_in_seconds:30"
        };

        var status = solver.Solve(model);

        if (status != CpSolverStatus.Optimal &&
            status != CpSolverStatus.Feasible)
        {
            return SolveResult.Failure(
                status.ToString(),
                "No feasible timetable was found under the " +
                "current hard constraints.");
        }

        // =========================================================
        // 13. Extract the selected assignments.
        // =========================================================
        var chosen = options
            .Where(o => solver.Value(o.Variable) == 1)
            .ToList();

        var schedule = chosen.Select(o => new ScheduledSessionResult
        {
            SessionKey = o.Session.SessionKey,
            OfferingId = o.Session.OfferingId,
            CourseId = o.Session.CourseId,
            CourseCode = o.Session.CourseCode,
            CourseName = o.Session.CourseName,
            SessionType = o.Session.SessionType,
            TeacherMode = o.Session.TeacherMode,
            SectionId = o.Session.SectionId,
            SectionName = o.Session.SectionName,
            TeacherIds = o.Teachers.Select(t => t.Id).ToList(),
            TeacherCodes = o.Teachers.Select(t => t.EmployeeCode).ToList(),
            RoomId = o.Candidate.RoomId,
            RoomCode = o.Candidate.RoomCode,
            Day = slotLookup[o.Candidate.StartTimeSlotId].Day,
            StartTime = slotLookup[o.Candidate.StartTimeSlotId].StartTime,
            EndTime = slotLookup[o.Candidate.TimeSlotIds.Last()].EndTime,
            DurationSlots = o.Session.DurationSlots,
            TimeSlotIds = o.Candidate.TimeSlotIds
        }).ToList();

        var coursePairs = eligibleTeachers.Select(entry =>
        {
            var pair = entry.Value
                .Where(t => solver.Value(
                    pairVariables[(entry.Key, t.Id)]) == 1)
                .ToList();

            return new CourseTeacherPairResult
            {
                CourseId = entry.Key,
                CourseCode = sessions
                    .First(s => s.CourseId == entry.Key).CourseCode,
                TeacherIds = pair.Select(t => t.Id).ToList(),
                TeacherCodes = pair.Select(t => t.EmployeeCode).ToList()
            };
        }).ToList();

        var workload = input.Teachers.Select(teacher =>
        {
            int existingPeriods =
                existingIndex.TeacherPeriods.GetValueOrDefault(
                    teacher.Id, 0);

            int newPeriods = chosen
                .Where(o => o.Teachers.Any(t => t.Id == teacher.Id))
                .Sum(o => o.Session.DurationSlots);

            return new TeacherWorkloadResult
            {
                TeacherId = teacher.Id,
                TeacherCode = teacher.EmployeeCode,
                ExistingPeriods = existingPeriods,
                NewPeriods = newPeriods,
                TotalPeriods = existingPeriods + newPeriods,
                MaximumPeriods = teacher.MaxWeeklyClasses
            };
        }).ToList();

        var quality = new SolutionQualityResult
        {
            StudentGaps = studentGapVars.Count(v => solver.Value(v) == 1),
            SinglePeriodDays = singlePeriodDayVars.Count(v => solver.Value(v) == 1),
            TeacherGaps = teacherGapVars.Count(v => solver.Value(v) == 1),
            DailyTheoryExcess = dailyTheoryExcessVars.Sum(v => (int)solver.Value(v)),
            DailyPeriodExcess = dailyPeriodExcessVars.Sum(v => (int)solver.Value(v)),
            PreferredPeriods = chosen.Sum(o => o.Teachers.Sum(t =>
                o.Candidate.TimeSlotIds.Count(id =>
                    slotPreference[t.Id].GetValueOrDefault(id) > 0))),
            AvoidedPeriods = chosen.Sum(o => o.Teachers.Sum(t =>
                o.Candidate.TimeSlotIds.Count(id =>
                    slotPreference[t.Id].GetValueOrDefault(id) < 0))),
            MorningPeriods = chosen.Sum(o =>
                o.Candidate.TimeSlotIds.Count(id => IsMorning(slotLookup[id]))),
            TotalPeriods = chosen.Sum(o => o.Candidate.TimeSlotIds.Count)
        };

        return new SolveResult
        {
            Success = true,
            Status = status.ToString(),
            Quality = quality,
            TotalSessions = sessions.Count,
            ScheduledSessions = schedule.Count,
            ObjectiveValue = solver.ObjectiveValue,
            CourseTeacherPairs = coursePairs,
            TeacherWorkloadPeriods = workload,
            Schedule = schedule
        };
    }

    // =============================================================
    // Existing schedule conflict checks.
    // =============================================================
    private static bool ConflictsWithExistingSchedule(
        ClassSession session,
        PlacementCandidate candidate,
        IEnumerable<TeacherInput> teachers,
        IReadOnlyDictionary<int, TimeSlotInput> slotLookup,
        ExistingScheduleIndex existing)
    {
        foreach (int slotId in candidate.TimeSlotIds)
        {
            if (teachers.Any(teacher =>
                    existing.TeacherSlots.Contains((teacher.Id, slotId))))
            {
                return true;
            }

            if (existing.RoomSlots.Contains(
                    (candidate.RoomId, slotId)))
            {
                return true;
            }

            if (existing.SectionSlots.Contains(
                    (session.SectionId, slotId)))
            {
                return true;
            }
        }

        if (IsTheory(session.SessionType))
        {
            string day = slotLookup[
                candidate.StartTimeSlotId].Day.ToUpperInvariant();

            if (existing.TheoryCoursesByDay.Contains((
                    session.SectionId,
                    session.CourseId,
                    day)))
            {
                return true;
            }
        }

        return false;
    }

    // =============================================================
    // Validate candidate slot count, day, breaks, and continuity.
    // =============================================================
    private static bool IsStructurallyValidCandidate(
        ClassSession session,
        PlacementCandidate candidate,
        IReadOnlyDictionary<int, TimeSlotInput> slotLookup)
    {
        if (session.DurationSlots <= 0)
            return false;

        if (candidate.TimeSlotIds.Count != session.DurationSlots)
            return false;

        if (candidate.TimeSlotIds.Distinct().Count()
            != candidate.TimeSlotIds.Count)
        {
            return false;
        }

        var ordered = new List<TimeSlotInput>();

        foreach (int slotId in candidate.TimeSlotIds)
        {
            if (!slotLookup.TryGetValue(slotId, out var slot))
                return false;

            if (slot.IsBreak)
                return false;

            ordered.Add(slot);
        }

        ordered = ordered
            .OrderBy(s => s.SlotNumber)
            .ToList();

        string day = ordered[0].Day;

        if (ordered.Any(s => !string.Equals(
                s.Day, day, StringComparison.OrdinalIgnoreCase)))
        {
            return false;
        }

        for (int i = 1; i < ordered.Count; i++)
        {
            if (ordered[i].SlotNumber !=
                ordered[i - 1].SlotNumber + 1)
            {
                return false;
            }
        }

        if (candidate.StartTimeSlotId != ordered[0].Id)
            return false;

        if (IsLab(session.SessionType) &&
            session.DurationSlots != 3)
        {
            return false;
        }

        return true;
    }

    // All unordered pairs of the eligible teachers.
    private static List<List<TeacherInput>> TeacherPairs(
        List<TeacherInput> teachers)
    {
        var pairs = new List<List<TeacherInput>>();

        for (int i = 0; i < teachers.Count; i++)
        {
            for (int j = i + 1; j < teachers.Count; j++)
            {
                pairs.Add(new List<TeacherInput> { teachers[i], teachers[j] });
            }
        }

        return pairs;
    }

    internal static bool IsTheory(string sessionType) =>
        string.Equals(
            sessionType, "Theory",
            StringComparison.OrdinalIgnoreCase);

    internal static bool IsLab(string sessionType) =>
        string.Equals(
            sessionType, "Lab",
            StringComparison.OrdinalIgnoreCase);

    // Morning = a period starting before 12:00.
    private static bool IsMorning(TimeSlotInput slot) =>
        string.CompareOrdinal(slot.StartTime, "12:00") < 0;

    internal static bool IsJoint(string teacherMode) =>
        string.Equals(
            teacherMode, "Joint",
            StringComparison.OrdinalIgnoreCase);

    // =============================================================
    // At most one selected assignment per resource and time slot.
    // =============================================================
    private static void AddAtMostOneConstraint(
        CpModel model,
        List<AssignmentOption> options,
        Func<AssignmentOption, IEnumerable<int>> resourcesSelector,
        Func<AssignmentOption, List<int>> slotsSelector)
    {
        var groups = options
            .SelectMany(option =>
                resourcesSelector(option).SelectMany(resourceId =>
                    slotsSelector(option).Select(slotId => new
                    {
                        Option = option,
                        ResourceId = resourceId,
                        SlotId = slotId
                    })))
            .GroupBy(x => new
            {
                x.ResourceId,
                x.SlotId
            });

        foreach (var group in groups)
        {
            model.Add(
                LinearExpr.Sum(
                    group.Select(x => x.Option.Variable)) <= 1);
        }
    }
}
