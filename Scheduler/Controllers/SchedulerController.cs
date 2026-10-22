using Scheduler.Models;
using Microsoft.AspNetCore.Mvc;
using Scheduler.Services;

namespace Scheduler.Controllers;

[ApiController]
[Route("api/scheduler")]
public class SchedulerController : ControllerBase
{
    private readonly ILogger<SchedulerController> _logger;

    public SchedulerController(ILogger<SchedulerController> logger)
    {
        _logger = logger;
    }

[HttpPost("validate")]
public IActionResult ValidateInput([FromBody] SchedulerInput input)
{
    var validator = new SchedulerInputValidator();
    var errors = validator.Validate(input);

    if (errors.Count > 0)
    {
        return BadRequest(new
        {
            success = false,
            message = "Scheduler input validation failed.",
            errors
        });
    }

    return Ok(new
    {
        success = true,
        message = "Scheduler input passed basic validation.",
        section_count = input.Sections.Count,
        offering_count = input.Offerings.Count,
        teacher_count = input.Teachers.Count,
        room_count = input.Rooms.Count,
        time_slot_count = input.TimeSlots.Count
    });
}



[HttpPost("sessions")]
public IActionResult BuildSessions([FromBody] SchedulerInput input)
{
    var validator = new SchedulerInputValidator();
    var errors = validator.Validate(input);

    if (errors.Count > 0)
    {
        return BadRequest(new
        {
            success = false,
            errors
        });
    }

    var builder = new ClassSessionBuilder();
    var sessions = builder.Build(input);

    return Ok(new
    {
        success = true,
        total_sessions = sessions.Count,
        sessions
    });
}


[HttpPost("candidates")]
public IActionResult BuildCandidates([FromBody] SchedulerInput input)
{
    var validator = new SchedulerInputValidator();
    var errors = validator.Validate(input);

    if (errors.Count > 0)
    {
        return BadRequest(new { success = false, errors });
    }

    var sessionBuilder = new ClassSessionBuilder();
    var sessions = sessionBuilder.Build(input);

    var candidateBuilder = new PlacementCandidateBuilder();
    var candidates = candidateBuilder.Build(input, sessions);

    return Ok(new
    {
        success = true,
        session_count = sessions.Count,
        candidate_count = candidates.Count,
        candidates
    });
}



[HttpPost("solve")]
public IActionResult Solve([FromBody] SchedulerInput input)
{
    try
    {
        var validator = new SchedulerInputValidator();
        var errors = validator.Validate(input);

        if (errors.Count > 0)
        {
            return BadRequest(new
            {
                success = false,
                status = "InvalidInput",
                errors
            });
        }

        var sessions = new ClassSessionBuilder().Build(input);

        var candidates = new PlacementCandidateBuilder()
            .Build(input, sessions);

        var result = new SchedulerSolverService()
            .Solve(input, sessions, candidates);

        // Independently validate the returned schedule before
        // allowing Laravel to display or publish it.
        if (result.Success && result.Schedule != null)
        {
            result.Validation = new ScheduleResultValidator()
                .Validate(input, sessions, result.Schedule);

            if (!result.Validation.IsValid)
            {
                _logger.LogError(
                    "Solver produced an invalid schedule: {Errors}",
                    string.Join("; ", result.Validation.Errors));

                result.Success = false;
                result.Status = "INVALID_SOLUTION";
                result.Message =
                    "The solver returned a schedule that failed validation.";
            }
        }

        return Ok(result);
    }
    catch (Exception ex)
    {
        _logger.LogError(ex, "Timetable generation failed.");

        return StatusCode(500, new
        {
            success = false,
            status = "SolverError",
            message = "Timetable generation failed unexpectedly."
        });
    }
}



// Re-checks a previously generated schedule against the current
// input, e.g. right before Laravel publishes it.
[HttpPost("validate-schedule")]
public IActionResult ValidateSchedule([FromBody] ScheduleValidationRequest request)
{
    var inputErrors = new SchedulerInputValidator().Validate(request.Input);

    if (inputErrors.Count > 0)
    {
        return BadRequest(new
        {
            success = false,
            status = "InvalidInput",
            errors = inputErrors
        });
    }

    var sessions = new ClassSessionBuilder().Build(request.Input);

    var validation = new ScheduleResultValidator()
        .Validate(request.Input, sessions, request.Schedule);

    return Ok(new
    {
        success = true,
        validation
    });
}



}
