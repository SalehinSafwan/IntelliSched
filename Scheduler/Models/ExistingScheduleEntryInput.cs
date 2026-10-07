using System.Text.Json.Serialization;

namespace Scheduler.Models;

public class ExistingScheduleEntryInput
{
    [JsonPropertyName("schedule_run_id")]
    public int ScheduleRunId { get; set; }
    [JsonPropertyName("batch_id")]
    public int BatchId { get; set; }
    [JsonPropertyName("course_offering_id")]
    public int CourseOfferingId { get; set; }
    [JsonPropertyName("teacher_id")]
    public int TeacherId { get; set; }
    [JsonPropertyName("room_id")]
    public int RoomId { get; set; }
    [JsonPropertyName("time_slot_id")]
    public int TimeSlotId { get; set; }
    [JsonPropertyName("session_type")]
    public string SessionType { get; set; } = string.Empty;
}