using System.Text.Json.Serialization;


namespace Scheduler.Models;

public class TeacherInput
{
    [JsonPropertyName("id")]
    public int Id { get; set; }
    [JsonPropertyName("employee_code")]
    public string EmployeeCode { get; set; } = string.Empty;
    [JsonPropertyName("max_weekly_classes")]
    public int MaxWeeklyClasses { get; set; }
    [JsonPropertyName("suitability_scores")]
    public Dictionary<int, double> SuitabilityScores { get; set; }
        = new();
    [JsonPropertyName("availability")]
    public List<TeacherAvailabilityInput> Availability { get; set; }
        = new();
}