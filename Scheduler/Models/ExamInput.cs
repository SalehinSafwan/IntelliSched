using System.Text.Json.Serialization;

namespace Scheduler.Models;

public class ExamInput
{
    [JsonPropertyName("id")]
    public int Id { get; set; }
    [JsonPropertyName("course_id")]
    public int CourseId { get; set; }
    [JsonPropertyName("section_id")]
    public int SectionId { get; set; }
    [JsonPropertyName("academic_term_id")]
    public int AcademicTermId { get; set; }
    [JsonPropertyName("room_id")]
    public int? RoomId { get; set; }
    [JsonPropertyName("time_slot_id")]
    public int? TimeSlotId { get; set; }
    [JsonPropertyName("exam_type")]
    public string ExamType { get; set; } = string.Empty;
    [JsonPropertyName("exam_date")]
    public DateTime ExamDate { get; set; }
    [JsonPropertyName("status")]
    public string Status { get; set; } = string.Empty;
}