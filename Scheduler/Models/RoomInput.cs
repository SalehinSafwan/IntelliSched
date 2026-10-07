using System.Text.Json.Serialization;

namespace Scheduler.Models;

public class RoomInput
{
    [JsonPropertyName("id")]
    public int Id { get; set; }
    [JsonPropertyName("room_code")]
    public string RoomCode { get; set; } = string.Empty;
    [JsonPropertyName("room_type")]
    public string RoomType { get; set; } = string.Empty;
    [JsonPropertyName("capacity")]
    public int Capacity { get; set; }
    [JsonPropertyName("priority")]
    public int Priority { get; set; }
    [JsonPropertyName("eligible_courses")]
    public List<int> EligibleCourses { get; set; } = new();
}