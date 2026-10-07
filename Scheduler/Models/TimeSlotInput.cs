using System.Text.Json.Serialization;

namespace Scheduler.Models;

public class TimeSlotInput
{
    [JsonPropertyName("id")]
    public int Id { get; set; }
    [JsonPropertyName("day")]
    public string Day { get; set; } = string.Empty;
    [JsonPropertyName("slot_number")]
    public int SlotNumber { get; set; }
    [JsonPropertyName("start_time")]
    public string StartTime { get; set; } = string.Empty;
    [JsonPropertyName("end_time")]
    public string EndTime { get; set; } = string.Empty;
    [JsonPropertyName("is_break")]
    public bool IsBreak { get; set; }
}