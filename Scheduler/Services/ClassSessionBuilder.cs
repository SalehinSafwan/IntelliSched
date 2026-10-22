
using Scheduler.Models;

namespace Scheduler.Services;

public class ClassSessionBuilder
{
    public List<ClassSession> Build(SchedulerInput input)
    {
        var sessions = new List<ClassSession>();

        foreach (var offering in input.Offerings)
        {
            // Theory courses have exactly three weekly classes:
            // T1 and T2 go to one pair teacher each, T3 is combined.
            for (int i = 1; i <= offering.WeeklyTheoryClasses; i++)
            {
                sessions.Add(new ClassSession
                {
                    SessionKey = $"O{offering.Id}-T{i}",
                    OfferingId = offering.Id,
                    CourseId = offering.CourseId,
                    CourseCode = offering.CourseCode,
                    CourseName = offering.CourseName,
                    SectionId = offering.SectionId,
                    SectionName = offering.SectionName,
                    SessionType = "Theory",
                    DurationSlots = 1,
                    TeacherMode = i == offering.WeeklyTheoryClasses
                        ? "Joint"
                        : "Single"
                });
            }

            // Each lab session requires three consecutive teaching slots
            // and is conducted jointly by both pair teachers.
            for (int i = 1; i <= offering.WeeklyLabClasses; i++)
            {
                sessions.Add(new ClassSession
                {
                    SessionKey = $"O{offering.Id}-L{i}",
                    OfferingId = offering.Id,
                    CourseId = offering.CourseId,
                    CourseCode = offering.CourseCode,
                    CourseName = offering.CourseName,
                    SectionId = offering.SectionId,
                    SectionName = offering.SectionName,
                    SessionType = "Lab",
                    DurationSlots = 3,
                    TeacherMode = "Joint"
                });
            }
        }

        return sessions;
    }
}
