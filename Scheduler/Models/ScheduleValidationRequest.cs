using System.Text.Json.Serialization;

namespace Scheduler.Models;

// Body of POST api/scheduler/validate-schedule: the current
// scheduler input plus a previously generated schedule.
public class ScheduleValidationRequest
{
    [JsonPropertyName("input")]
    public SchedulerInput Input { get; set; } = new();

    [JsonPropertyName("schedule")]
    public List<ScheduledSessionResult> Schedule { get; set; } = new();
}
