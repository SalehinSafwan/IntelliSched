
using Scheduler.Models;

namespace Scheduler.Services;

public class ExistingScheduleIndex
{
    public HashSet<(int TeacherId, int SlotId)> TeacherSlots
        { get; } = new();

    public HashSet<(int RoomId, int SlotId)> RoomSlots
        { get; } = new();

    public HashSet<(int SectionId, int SlotId)> SectionSlots
        { get; } = new();

    public Dictionary<int, int> TeacherPeriods
        { get; } = new();

    // A theory course already scheduled for a section on a day.
    public HashSet<(int SectionId, int CourseId, string Day)>
        TheoryCoursesByDay { get; } = new();

    public ExistingScheduleIndex(
        IEnumerable<ExistingScheduleEntryInput> entries,
        IReadOnlyDictionary<int, TimeSlotInput> slotLookup)
    {
        foreach (var entry in entries.Where(e => e.IsActive))
        {
            var slots = entry.TimeSlotIds.Distinct().ToList();

            if (slots.Count == 0)
                continue;

            foreach (var slotId in slots)
            {
                if (!slotLookup.TryGetValue(slotId, out var slot))
                {
                    throw new ArgumentException(
                        $"Existing entry {entry.Id} references " +
                        $"unknown time slot {slotId}.");
                }

                if (slot.IsBreak)
                {
                    throw new ArgumentException(
                        $"Existing entry {entry.Id} occupies " +
                        $"break slot {slotId}.");
                }

                foreach (var teacherId in entry.TeacherIds.Distinct())
                {
                    TeacherSlots.Add((teacherId, slotId));
                }

                if (entry.RoomId.HasValue)
                {
                    RoomSlots.Add(
                        (entry.RoomId.Value, slotId));
                }

                SectionSlots.Add((entry.SectionId, slotId));
            }

            // Count occupied teaching periods, not meetings.
            foreach (var teacherId in entry.TeacherIds.Distinct())
            {
                TeacherPeriods[teacherId] =
                    TeacherPeriods.GetValueOrDefault(teacherId)
                    + slots.Count;
            }

            if (entry.CourseId.HasValue &&
                string.Equals(
                    entry.SessionType,
                    "Theory",
                    StringComparison.OrdinalIgnoreCase))
            {
                var days = slots
                    .Select(id => slotLookup[id].Day)
                    .Distinct(StringComparer.OrdinalIgnoreCase)
                    .ToList();

                // An existing meeting should not span multiple days.
                if (days.Count != 1)
                {
                    throw new ArgumentException(
                        $"Existing entry {entry.Id} has slots " +
                        "from more than one day.");
                }

                TheoryCoursesByDay.Add((
                    entry.SectionId,
                    entry.CourseId.Value,
                    days[0].ToUpperInvariant()));
            }
        }
    }
}
