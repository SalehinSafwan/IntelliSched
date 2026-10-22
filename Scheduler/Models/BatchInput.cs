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

    // Number of active batches; with SeniorityOrder (1 = most senior)
    // it sets how strongly this batch prefers morning classes.
    [JsonPropertyName("active_batch_count")]
    public int ActiveBatchCount { get; set; } = 1;
    [JsonPropertyName("status")]
    public string Status { get; set; } = string.Empty;
}