using System.Text.Json.Serialization;

namespace Scheduler.Models;

public class SchedulerInput
{
    [JsonPropertyName("academic_term")]
    public AcademicTermInput AcademicTerm { get; set; } = new();
    [JsonPropertyName("batch")]
    public BatchInput Batch { get; set; } = new();
    [JsonPropertyName("sections")]
    public List<SectionInput> Sections { get; set; } = new();
    [JsonPropertyName("course_offering_input")]
    public List<CourseOfferingInput> Offerings { get; set; } = new();
    [JsonPropertyName("teachers")]
    public List<TeacherInput> Teachers { get; set; } = new();
    [JsonPropertyName("rooms")]
    public List<RoomInput> Rooms { get; set; } = new();
    [JsonPropertyName("time_slots")]
    public List<TimeSlotInput> TimeSlots { get; set; } = new();
    [JsonPropertyName("exams")]
    public List<ExamInput> Exams { get; set; } = new();
    [JsonPropertyName("existing_schedule_entries")]
    public List<ExistingScheduleEntryInput> ExistingScheduleEntries
        { get; set; } = new();
}