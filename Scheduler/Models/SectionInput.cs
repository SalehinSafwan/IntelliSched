using System.Text.Json.Serialization;


namespace Scheduler.Models;

public class SectionInput
{
    [JsonPropertyName("id")]
    public int Id { get; set; }
    [JsonPropertyName("batch_id")]
    public int BatchId { get; set; }
    [JsonPropertyName("name")]
    public string Name { get; set; } = string.Empty;
    [JsonPropertyName("student_count")]
    public int StudentCount { get; set; }
    [JsonPropertyName("status")]
    public string Status { get; set; } = string.Empty;
}