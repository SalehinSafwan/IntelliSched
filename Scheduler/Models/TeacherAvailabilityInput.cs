using System.Text.Json.Serialization;


namespace Scheduler.Models;

public class TeacherAvailabilityInput
{
    [JsonPropertyName("time_slot_id")]
    public int TimeSlotId { get; set; }
    [JsonPropertyName("is_available")]
    public bool IsAvailable { get; set; }
}