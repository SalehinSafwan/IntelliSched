
namespace Scheduler.Models;

public class PlacementCandidate
{
    public string SessionKey { get; set; } = string.Empty;

    public int SectionId { get; set; }

    public int CourseId { get; set; }

    public string SessionType { get; set; } = string.Empty;

    public int DurationSlots { get; set; }

    public int RoomId { get; set; }

    public string RoomCode { get; set; } = string.Empty;

    // IDs of all teaching slots occupied by this placement.
    public List<int> TimeSlotIds { get; set; } = new();

    public int StartTimeSlotId { get; set; }

    public int RoomPriority { get; set; }
}
