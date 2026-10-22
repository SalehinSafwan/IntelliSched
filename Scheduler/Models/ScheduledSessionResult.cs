using System.Text.Json.Serialization;

namespace Scheduler.Models;

// One placed class meeting returned by the solver.
public class ScheduledSessionResult
{
    [JsonPropertyName("session_key")]
    public string SessionKey { get; set; } = string.Empty;

    [JsonPropertyName("offering_id")]
    public int OfferingId { get; set; }

    [JsonPropertyName("course_id")]
    public int CourseId { get; set; }

    [JsonPropertyName("course_code")]
    public string CourseCode { get; set; } = string.Empty;

    [JsonPropertyName("course_name")]
    public string CourseName { get; set; } = string.Empty;

    // "Theory" or "Lab"
    [JsonPropertyName("session_type")]
    public string SessionType { get; set; } = string.Empty;

    // "Single" or "Joint"
    [JsonPropertyName("teacher_mode")]
    public string TeacherMode { get; set; } = string.Empty;

    [JsonPropertyName("section_id")]
    public int SectionId { get; set; }

    [JsonPropertyName("section_name")]
    public string SectionName { get; set; } = string.Empty;

    [JsonPropertyName("teacher_ids")]
    public List<int> TeacherIds { get; set; } = new();

    [JsonPropertyName("teacher_codes")]
    public List<string> TeacherCodes { get; set; } = new();

    [JsonPropertyName("room_id")]
    public int RoomId { get; set; }

    [JsonPropertyName("room_code")]
    public string RoomCode { get; set; } = string.Empty;

    [JsonPropertyName("day")]
    public string Day { get; set; } = string.Empty;

    [JsonPropertyName("start_time")]
    public string StartTime { get; set; } = string.Empty;

    [JsonPropertyName("end_time")]
    public string EndTime { get; set; } = string.Empty;

    [JsonPropertyName("duration_slots")]
    public int DurationSlots { get; set; }

    [JsonPropertyName("time_slot_ids")]
    public List<int> TimeSlotIds { get; set; } = new();
}
