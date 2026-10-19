
using Scheduler.Models;

namespace Scheduler.Services;

public class PlacementCandidateBuilder
{
    public List<PlacementCandidate> Build(
        SchedulerInput input,
        List<ClassSession> sessions)
    {
        var candidates = new List<PlacementCandidate>();

        var teachingSlots = input.TimeSlots
            .Where(s => !s.IsBreak)
            .GroupBy(s => s.Day)
            .ToDictionary(
                g => g.Key,
                g => g.OrderBy(s => s.SlotNumber).ToList());

        foreach (var session in sessions)
        {
            var section = input.Sections
                .FirstOrDefault(s => s.Id == session.SectionId);

            if (section == null)
                continue;

            foreach (var room in input.Rooms)
            {
                if (!RoomCanHost(room, session, section.StudentCount))
                    continue;

                foreach (var daySlots in teachingSlots.Values)
                {
                    for (int i = 0;
                         i + session.DurationSlots <= daySlots.Count;
                         i++)
                    {
                        var block = daySlots
                            .Skip(i)
                            .Take(session.DurationSlots)
                            .ToList();

                        // A lab must use consecutive slot numbers.
                        bool consecutive = true;

                        for (int j = 1; j < block.Count; j++)
                        {
                            if (block[j].SlotNumber !=
                                block[j - 1].SlotNumber + 1)
                            {
                                consecutive = false;
                                break;
                            }
                        }

                        if (!consecutive)
                            continue;

                        candidates.Add(new PlacementCandidate
                        {
                            SessionKey = session.SessionKey,
                            SectionId = session.SectionId,
                            CourseId = session.CourseId,
                            SessionType = session.SessionType,
                            DurationSlots = session.DurationSlots,
                            RoomId = room.Id,
                            RoomCode = room.RoomCode,
                            TimeSlotIds = block.Select(s => s.Id).ToList(),
                            StartTimeSlotId = block[0].Id,
                            RoomPriority = room.Priority
                        });
                    }
                }
            }
        }

        return candidates;
    }

    private static bool RoomCanHost(
        RoomInput room,
        ClassSession session,
        int studentCount)
    {
        if (room.Capacity < studentCount)
            return false;

        bool isLab = session.SessionType == "Lab";

        if (isLab)
        {
            bool isLabRoom =
                room.RoomType.Equals(
                    "lab", StringComparison.OrdinalIgnoreCase)
                || room.RoomType.Equals(
                    "multipurpose", StringComparison.OrdinalIgnoreCase);

            return isLabRoom &&
                   room.EligibleCourses.Contains(session.CourseId);
        }

        // Theory may use ordinary theory rooms or multipurpose rooms.
        return room.RoomType.Equals(
                   "theory", StringComparison.OrdinalIgnoreCase)
               || room.RoomType.Equals(
                   "multipurpose", StringComparison.OrdinalIgnoreCase);
    }
}
