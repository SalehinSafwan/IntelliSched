
namespace Scheduler.Models;

public class ClassSession
{
    public string SessionKey { get; set; } = string.Empty;

    public int OfferingId { get; set; }

    public int CourseId { get; set; }

    public string CourseCode { get; set; } = string.Empty;

    public string CourseName { get; set; } = string.Empty;

    public int SectionId { get; set; }

    public string SectionName { get; set; } = string.Empty;

    // "Theory" or "Lab"
    public string SessionType { get; set; } = string.Empty;

    // Number of consecutive teaching slots required.
    public int DurationSlots { get; set; }

    // "Single": taught by one teacher of the course pair.
    // "Joint": both pair teachers are blocked (combined theory, labs).
    public string TeacherMode { get; set; } = string.Empty;
}
