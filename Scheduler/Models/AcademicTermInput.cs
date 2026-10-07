using System.Text.Json.Serialization;

namespace Scheduler.Models;

public class AcademicTermInput
{
    [JsonPropertyName("id")]
    public int Id { get; set; }
    [JsonPropertyName("name")]
    public string Name { get; set; } = string.Empty;
    [JsonPropertyName("academic_year")]
    public string AcademicYear { get; set; } = string.Empty;
    [JsonPropertyName("term")]
    public string Term { get; set; } = string.Empty;
    [JsonPropertyName("start_date")]
    public DateTime StartDate { get; set; }
    [JsonPropertyName("end_date")]
    public DateTime EndDate { get; set; }
    [JsonPropertyName("status")]
    public string Status { get; set; } = string.Empty;
}