
using Scheduler.Models;

namespace Scheduler.Services;

public class SchedulerInputValidator
{
    public List<string> Validate(SchedulerInput input)
    {
        var errors = new List<string>();

        if (input.AcademicTerm.Id <= 0)
            errors.Add("A valid academic term is required.");

        if (input.Batch.Id <= 0)
            errors.Add("A valid batch is required.");

        if (input.Sections.Count == 0)
            errors.Add("At least one section is required.");

        if (input.Offerings.Count == 0)
            errors.Add("At least one course offering is required.");

        if (input.Teachers.Count == 0)
            errors.Add("At least one teacher is required.");

        if (input.Rooms.Count == 0)
            errors.Add("At least one room is required.");

        if (input.TimeSlots.Count == 0)
            errors.Add("At least one time slot is required.");

        // Section IDs must be unique.
        var sectionIds = input.Sections
            .Select(s => s.Id)
            .ToList();

        if (sectionIds.Distinct().Count() != sectionIds.Count)
            errors.Add("Duplicate section IDs were found.");

        var validSectionIds = sectionIds.ToHashSet();

        // Offering IDs must be unique.
        var offeringIds = input.Offerings
            .Select(o => o.Id)
            .ToList();

        if (offeringIds.Distinct().Count() != offeringIds.Count)
            errors.Add("Duplicate offering IDs were found.");

        // Each offering must reference an existing section.
        foreach (var offering in input.Offerings)
        {
            if (!validSectionIds.Contains(offering.SectionId))
            {
                errors.Add(
                    $"Offering {offering.Id} references unknown " +
                    $"section ID {offering.SectionId}.");
            }

            if (offering.CourseId <= 0)
                errors.Add(
                    $"Offering {offering.Id} has an invalid course ID.");

            if (offering.WeeklyTheoryClasses < 0 ||
                offering.WeeklyLabClasses < 0)
            {
                errors.Add(
                    $"Offering {offering.Id} has negative weekly class counts.");
            }

            if (offering.WeeklyTheoryClasses == 0 &&
                offering.WeeklyLabClasses == 0)
            {
                errors.Add(
                    $"Offering {offering.Id} has no scheduled classes.");
            }

            // Two pair teachers take one theory class each and
            // share the third, so a theory course needs exactly 3.
            if (offering.WeeklyTheoryClasses != 0 &&
                offering.WeeklyTheoryClasses != 3)
            {
                errors.Add(
                    $"Offering {offering.Id} ({offering.CourseCode}) has " +
                    $"{offering.WeeklyTheoryClasses} weekly theory classes; " +
                    "theory courses must have exactly 3.");
            }
        }

        // Every course is taught by a pair of teachers. A teacher is
        // eligible only if they have a suitability score for the course.
        foreach (var course in input.Offerings
                     .GroupBy(o => o.CourseId)
                     .Select(g => g.First()))
        {
            int eligibleTeachers = input.Teachers.Count(t =>
                t.SuitabilityScores.ContainsKey(course.CourseId));

            if (eligibleTeachers < 2)
            {
                errors.Add(
                    $"Course {course.CourseCode} has {eligibleTeachers} " +
                    "eligible teacher(s); a teacher pair (2) is required.");
            }
        }

        return errors;
    }
}
