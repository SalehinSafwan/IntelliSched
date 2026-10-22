using System.Text.Json.Serialization;

namespace Scheduler.Models;

public class SolveResult
{
    [JsonPropertyName("success")]
    public bool Success { get; set; }

    [JsonPropertyName("status")]
    public string Status { get; set; } = string.Empty;

    [JsonPropertyName("message")]
    [JsonIgnore(Condition = JsonIgnoreCondition.WhenWritingNull)]
    public string? Message { get; set; }

    // Session keys that have no possible placement.
    [JsonPropertyName("sessions")]
    [JsonIgnore(Condition = JsonIgnoreCondition.WhenWritingNull)]
    public List<string>? Sessions { get; set; }

    [JsonPropertyName("total_sessions")]
    [JsonIgnore(Condition = JsonIgnoreCondition.WhenWritingNull)]
    public int? TotalSessions { get; set; }

    [JsonPropertyName("scheduled_sessions")]
    [JsonIgnore(Condition = JsonIgnoreCondition.WhenWritingNull)]
    public int? ScheduledSessions { get; set; }

    [JsonPropertyName("objective_value")]
    [JsonIgnore(Condition = JsonIgnoreCondition.WhenWritingNull)]
    public double? ObjectiveValue { get; set; }

    [JsonPropertyName("course_teacher_pairs")]
    [JsonIgnore(Condition = JsonIgnoreCondition.WhenWritingNull)]
    public List<CourseTeacherPairResult>? CourseTeacherPairs { get; set; }

    [JsonPropertyName("teacher_workload_periods")]
    [JsonIgnore(Condition = JsonIgnoreCondition.WhenWritingNull)]
    public List<TeacherWorkloadResult>? TeacherWorkloadPeriods { get; set; }

    [JsonPropertyName("schedule")]
    [JsonIgnore(Condition = JsonIgnoreCondition.WhenWritingNull)]
    public List<ScheduledSessionResult>? Schedule { get; set; }

    [JsonPropertyName("quality")]
    [JsonIgnore(Condition = JsonIgnoreCondition.WhenWritingNull)]
    public SolutionQualityResult? Quality { get; set; }

    [JsonPropertyName("validation")]
    [JsonIgnore(Condition = JsonIgnoreCondition.WhenWritingNull)]
    public ScheduleValidationResult? Validation { get; set; }

    public static SolveResult Failure(string status, string message) =>
        new() { Success = false, Status = status, Message = message };
}

public class CourseTeacherPairResult
{
    [JsonPropertyName("course_id")]
    public int CourseId { get; set; }

    [JsonPropertyName("course_code")]
    public string CourseCode { get; set; } = string.Empty;

    [JsonPropertyName("teacher_ids")]
    public List<int> TeacherIds { get; set; } = new();

    [JsonPropertyName("teacher_codes")]
    public List<string> TeacherCodes { get; set; } = new();
}

public class TeacherWorkloadResult
{
    [JsonPropertyName("teacher_id")]
    public int TeacherId { get; set; }

    [JsonPropertyName("teacher_code")]
    public string TeacherCode { get; set; } = string.Empty;

    [JsonPropertyName("existing_periods")]
    public int ExistingPeriods { get; set; }

    [JsonPropertyName("new_periods")]
    public int NewPeriods { get; set; }

    [JsonPropertyName("total_periods")]
    public int TotalPeriods { get; set; }

    [JsonPropertyName("maximum_periods")]
    public int MaximumPeriods { get; set; }
}

public class ScheduleValidationResult
{
    [JsonPropertyName("is_valid")]
    public bool IsValid => Errors.Count == 0;

    [JsonPropertyName("errors")]
    public List<string> Errors { get; set; } = new();
}

// How well the soft constraints were met by the chosen schedule.
public class SolutionQualityResult
{
    [JsonPropertyName("student_gaps")]
    public int StudentGaps { get; set; }

    [JsonPropertyName("single_period_days")]
    public int SinglePeriodDays { get; set; }

    [JsonPropertyName("teacher_gaps")]
    public int TeacherGaps { get; set; }

    [JsonPropertyName("daily_theory_excess")]
    public int DailyTheoryExcess { get; set; }

    [JsonPropertyName("daily_period_excess")]
    public int DailyPeriodExcess { get; set; }

    [JsonPropertyName("preferred_periods")]
    public int PreferredPeriods { get; set; }

    [JsonPropertyName("avoided_periods")]
    public int AvoidedPeriods { get; set; }

    [JsonPropertyName("morning_periods")]
    public int MorningPeriods { get; set; }

    [JsonPropertyName("total_periods")]
    public int TotalPeriods { get; set; }
}
