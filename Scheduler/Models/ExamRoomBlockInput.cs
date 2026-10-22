using System.Text.Json.Serialization;

namespace Scheduler.Models;

// A room reserved by a published exam: no class may use it on that
// weekday between StartTime and EndTime.
public class ExamRoomBlockInput
{
    [JsonPropertyName("room_id")]
    public int RoomId { get; set; }

    [JsonPropertyName("day")]
    public string Day { get; set; } = string.Empty;

    [JsonPropertyName("start_time")]
    public string StartTime { get; set; } = string.Empty;

    [JsonPropertyName("end_time")]
    public string EndTime { get; set; } = string.Empty;

    [JsonPropertyName("exam_date")]
    public string ExamDate { get; set; } = string.Empty;

    [JsonPropertyName("label")]
    public string Label { get; set; } = string.Empty;

    // True when a slot on this weekday overlaps the exam window.
    public bool Blocks(TimeSlotInput slot) =>
        string.Equals(slot.Day, Day, StringComparison.OrdinalIgnoreCase)
        && string.CompareOrdinal(slot.StartTime, EndTime) < 0
        && string.CompareOrdinal(slot.EndTime, StartTime) > 0;
}
