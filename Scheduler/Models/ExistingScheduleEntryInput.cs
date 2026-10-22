
using System.Text.Json.Serialization;

namespace Scheduler.Models;

public class ExistingScheduleEntryInput
{
    [JsonPropertyName("id")]
    public int Id { get; set; }

    [JsonPropertyName("batch_id")]
    public int BatchId { get; set; }

    [JsonPropertyName("section_id")]
    public int SectionId { get; set; }

    [JsonPropertyName("course_id")]
    public int? CourseId { get; set; }

    [JsonPropertyName("session_type")]
    public string SessionType { get; set; } = string.Empty;

    // One teacher for single sessions, both pair teachers
    // for combined theory classes and labs.
    [JsonPropertyName("teacher_ids")]
    public List<int> TeacherIds { get; set; } = new();

    [JsonPropertyName("room_id")]
    public int? RoomId { get; set; }

    [JsonPropertyName("time_slot_ids")]
    public List<int> TimeSlotIds { get; set; } = new();

    [JsonPropertyName("is_active")]
    public bool IsActive { get; set; } = true;
}
