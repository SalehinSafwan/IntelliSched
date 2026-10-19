
using Google.OrTools.Sat;
using Scheduler.Models;

namespace Scheduler.Services;

public class SchedulerSolverService
{
    private sealed class AssignmentOption
    {
        public PlacementCandidate Candidate { get; init; } = new();
        public ClassSession Session { get; init; } = new();
        public TeacherInput Teacher { get; init; } = new();
        public BoolVar Variable { get; init; } = null!;
        public double SuitabilityScore { get; init; }
    }

    public object Solve(
        SchedulerInput input,
        List<ClassSession> sessions,
        List<PlacementCandidate> candidates)
    {
        var model = new CpModel();
        var options = new List<AssignmentOption>();

        // Create a possible assignment for every valid combination
        // of session, placement candidate, and available teacher.
        foreach (var session in sessions)
        {
            var sessionCandidates = candidates
                .Where(c => c.SessionKey == session.SessionKey)
                .ToList();

            foreach (var candidate in sessionCandidates)
            {
                foreach (var teacher in input.Teachers)
                {
                    bool available = candidate.TimeSlotIds.All(slotId =>
                        teacher.Availability.Any(a =>
                            a.TimeSlotId == slotId && a.IsAvailable));

                    if (!available)
                        continue;

                    double score = teacher.SuitabilityScores.TryGetValue(
                        session.CourseId, out var suitability)
                            ? suitability
                            : 0;

                    var variable = model.NewBoolVar(
                        $"assign_{options.Count}");

                    options.Add(new AssignmentOption
                    {
                        Candidate = candidate,
                        Session = session,
                        Teacher = teacher,
                        Variable = variable,
                        SuitabilityScore = score
                    });
                }
            }
        }

        // Every session must have at least one possible assignment.
        var impossibleSessions = sessions
            .Where(session => !options.Any(o =>
                o.Session.SessionKey == session.SessionKey))
            .Select(session => session.SessionKey)
            .ToList();

        if (impossibleSessions.Count > 0)
        {
            return new
            {
                success = false,
                status = "NO_CANDIDATES",
                message = "Some sessions have no eligible teacher, room, and time placement.",
                sessions = impossibleSessions
            };
        }

        // Each session must be assigned exactly once.
        foreach (var session in sessions)
        {
            var sessionOptions = options
                .Where(o => o.Session.SessionKey == session.SessionKey)
                .Select(o => o.Variable);

            model.Add(LinearExpr.Sum(sessionOptions) == 1);
        }

        // A section cannot attend two classes in the same time slot.
        AddAtMostOneConstraint(
            model,
            options,
            o => o.Candidate.SectionId,
            o => o.Candidate.TimeSlotIds);

        // A room cannot host two classes in the same time slot.
        AddAtMostOneConstraint(
            model,
            options,
            o => o.Candidate.RoomId,
            o => o.Candidate.TimeSlotIds);

        // A teacher cannot teach two classes in the same time slot.
        AddAtMostOneConstraint(
            model,
            options,
            o => o.Teacher.Id,
            o => o.Candidate.TimeSlotIds);

        // Do not exceed each teacher's weekly class limit.
        foreach (var teacher in input.Teachers)
        {
            var teacherOptions = options
                .Where(o => o.Teacher.Id == teacher.Id)
                .Select(o => o.Variable);

            model.Add(
                LinearExpr.Sum(teacherOptions)
                <= teacher.MaxWeeklyClasses);
        }

        // Optimize suitability first, with room priority as a
        // secondary preference. Lower room priority numbers are better.
        var objectiveVariables = new List<LinearExpr>();
        var objectiveCoefficients = new List<long>();

        foreach (var option in options)
        {
            long score = (long)Math.Round(
                option.SuitabilityScore * 1000);

            long coefficient = score - option.Candidate.RoomPriority;

            objectiveVariables.Add(option.Variable);
            objectiveCoefficients.Add(coefficient);
        }

        model.Maximize(
            LinearExpr.WeightedSum(
                objectiveVariables,
                objectiveCoefficients));

        var solver = new CpSolver
        {
            StringParameters = "max_time_in_seconds:30"
        };

        var status = solver.Solve(model);

        if (status != CpSolverStatus.Optimal &&
            status != CpSolverStatus.Feasible)
        {
            return new
            {
                success = false,
                status = status.ToString(),
                message = "No feasible timetable was found under the current constraints."
            };
        }

        var chosen = options
            .Where(o => solver.Value(o.Variable) == 1)
            .ToList();

        var slotLookup = input.TimeSlots.ToDictionary(s => s.Id);

        var schedule = chosen.Select(o => new
        {
            session_key = o.Session.SessionKey,
            course_id = o.Session.CourseId,
            course_code = o.Session.CourseCode,
            course_name = o.Session.CourseName,
            session_type = o.Session.SessionType,
            section_id = o.Session.SectionId,
            section_name = o.Session.SectionName,
            teacher_id = o.Teacher.Id,
            teacher_code = o.Teacher.EmployeeCode,
            room_id = o.Candidate.RoomId,
            room_code = o.Candidate.RoomCode,
            suitability_score = o.SuitabilityScore,
            day = slotLookup[o.Candidate.StartTimeSlotId].Day,
            start_time = slotLookup[o.Candidate.StartTimeSlotId].StartTime,
            end_time = slotLookup[
                o.Candidate.TimeSlotIds.Last()].EndTime,
            time_slot_ids = o.Candidate.TimeSlotIds
        }).ToList();

        return new
        {
            success = true,
            status = status.ToString(),
            total_sessions = sessions.Count,
            scheduled_sessions = schedule.Count,
            objective_value = solver.ObjectiveValue,
            schedule
        };
    }

    private static void AddAtMostOneConstraint(
        CpModel model,
        List<AssignmentOption> options,
        Func<AssignmentOption, int> resourceSelector,
        Func<AssignmentOption, List<int>> slotsSelector)
    {
        var groups = options
            .SelectMany(option =>
                slotsSelector(option).Select(slotId => new
                {
                    Option = option,
                    ResourceId = resourceSelector(option),
                    SlotId = slotId
                }))
            .GroupBy(x => new { x.ResourceId, x.SlotId });

        foreach (var group in groups)
        {
            model.Add(
                LinearExpr.Sum(
                    group.Select(x => x.Option.Variable)) <= 1);
        }
    }
}
