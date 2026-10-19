using Scheduler.Models;
using Microsoft.AspNetCore.Mvc;
using System.Linq;
using System.Text.Json;
using Scheduler.Services;

namespace Scheduler.Controllers;

[ApiController]
[Route("api/scheduler")]
public class SchedulerController : ControllerBase
{
    
[HttpPost("generate")]
public IActionResult Generate([FromBody] SchedulerInput input)
{
    return Ok(new
    {
        success = true,
        message = "Updated controller is running",
        offering_count = input.Offerings.Count,
        offering_ids = input.Offerings.Select(o => o.Id).ToList(),
        offering_course_codes = input.Offerings
            .Select(o => o.CourseCode)
            .ToList()
    });
}




[HttpPost("test-offerings")]
public IActionResult TestOfferings([FromBody] JsonElement json)
{
    if (!json.TryGetProperty("offerings", out var offerings))
    {
        return BadRequest(new
        {
            error = "Top-level offerings property was not found."
        });
    }

    var result = JsonSerializer.Deserialize<List<CourseOfferingInput>>(
        offerings.GetRawText(),
        new JsonSerializerOptions
        {
            PropertyNameCaseInsensitive = true,
            NumberHandling =
                System.Text.Json.Serialization.JsonNumberHandling
                    .AllowReadingFromString
        });

    return Ok(new
    {
        raw_offering_count = offerings.GetArrayLength(),
        deserialized_count = result?.Count ?? 0,
        ids = result?.Select(o => o.Id).ToList()
    });
}



// Add this method inside SchedulerController.

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

    var sessions = new ClassSessionBuilder().Build(input);

    var candidates = new PlacementCandidateBuilder()
        .Build(input, sessions);

    var result = new SchedulerSolverService()
        .Solve(input, sessions, candidates);

    return Ok(result);
}



}