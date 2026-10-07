using System.Text.Json.Serialization;

namespace Scheduler.Models;

public class BatchInput
{
    [JsonPropertyName("id")]
    public int Id { get; set; }
    [JsonPropertyName("name")]
    public string Name { get; set; } = string.Empty;
    [JsonPropertyName("department")]
    public string Department { get; set; } = string.Empty;
    [JsonPropertyName("program")]
    public string Program { get; set; } = string.Empty;
    [JsonPropertyName("admission_year")]
    public int AdmissionYear { get; set; }
    [JsonPropertyName("seniority_order")]
    public int SeniorityOrder { get; set; }
    [JsonPropertyName("status")]
    public string Status { get; set; } = string.Empty;
}