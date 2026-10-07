using Scheduler.Models;
using Microsoft.AspNetCore.Mvc;

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
            message = "Scheduler input received successfully.",
            batch_id = input.Batch.Id,
            section_count = input.Sections.Count,
            offering_count = input.Offerings.Count,
            teacher_count = input.Teachers.Count,
            room_count = input.Rooms.Count,
            time_slot_count = input.TimeSlots.Count
        });
    }
}