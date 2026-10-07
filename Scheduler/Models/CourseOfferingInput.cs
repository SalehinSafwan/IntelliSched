using System.Text.Json.Serialization;

namespace Scheduler.Models;

public class CourseOfferingInput
{
    [JsonPropertyName("id")]
    public int Id { get; set; }

    [JsonPropertyName("course_id")]
    public int CourseId { get; set; }

    [JsonPropertyName("course_code")]
    public string CourseCode { get; set; } = string.Empty;

    [JsonPropertyName("course_name")]
    public string CourseName { get; set; } = string.Empty;

    [JsonPropertyName("course_type")]
    public string CourseType { get; set; } = string.Empty;

    [JsonPropertyName("section_id")]
    public int SectionId { get; set; }

    [JsonPropertyName("section_name")]
    public string SectionName { get; set; } = string.Empty;

    [JsonPropertyName("weekly_theory_classes")]
    public int WeeklyTheoryClasses { get; set; }

    [JsonPropertyName("weekly_lab_classes")]
    public int WeeklyLabClasses { get; set; }
}