<?php

namespace Tests\Feature;

use App\Models\ClassMessage;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\ClassRepSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Student portal: daily routine with WhatsApp text, the CR notice
 * board (ready → posted), and the CR seeder.
 */
class ClassPortalTest extends TestCase
{
    use RefreshDatabase;

    private User $cr;
    private User $otherCr;
    private User $classmate;
    private User $teacher;
    private int $secA;
    private int $secB;
    private array $slot = [];

    protected function setUp(): void
    {
        parent::setUp();

        config(['intellisched.chatbot.timezone' => 'Asia/Dhaka']);
        // Sunday 22 Nov 2026, 08:30 local: first period is running.
        $this->travelTo(CarbonImmutable::parse('2026-11-22 08:30', 'Asia/Dhaka'));

        $now = now();
        $ins = fn (string $table, array $row) => DB::table($table)->insertGetId($row + ['created_at' => $now, 'updated_at' => $now]);

        $term = $ins('academic_terms', ['name' => 'Fall 2026', 'academic_year' => '2026', 'term' => 'Fall', 'start_date' => '2026-09-01', 'end_date' => '2026-12-31', 'status' => 'ACTIVE']);
        $batch = $ins('batches', ['name' => 'CSE 47', 'department' => 'CSE', 'program' => 'BSc', 'admission_year' => 2022, 'seniority_order' => 1]);
        $batch2 = $ins('batches', ['name' => 'CSE 48', 'department' => 'CSE', 'program' => 'BSc', 'admission_year' => 2023, 'seniority_order' => 2]);
        $this->secA = $ins('sections', ['batch_id' => $batch, 'name' => 'A', 'student_count' => 40]);
        $this->secB = $ins('sections', ['batch_id' => $batch, 'name' => 'B', 'student_count' => 40]);
        $ins('sections', ['batch_id' => $batch2, 'name' => 'A', 'student_count' => 40]);

        foreach (['SUNDAY', 'MONDAY'] as $day) {
            foreach ([[1, '08:00', '08:50'], [2, '09:00', '09:50'], [3, '10:00', '10:50']] as [$n, $s, $e]) {
                $this->slot[$day][$n] = $ins('time_slots', ['day' => $day, 'slot_number' => $n, 'start_time' => $s, 'end_time' => $e, 'is_break' => 0]);
            }
        }
        $room = $ins('rooms', ['room_code' => 'R301', 'room_name' => 'Room 301', 'room_type' => 'THEORY', 'capacity' => 60]);
        $ds = $ins('courses', ['course_code' => 'CSE 3201', 'course_name' => 'Data Structures', 'department' => 'CSE', 'course_type' => 'THEORY', 'credit_hours' => 3]);
        $os = $ins('courses', ['course_code' => 'CSE 3203', 'course_name' => 'Operating Systems', 'department' => 'CSE', 'course_type' => 'THEORY', 'credit_hours' => 3]);

        $admin = User::create(['name' => 'Admin', 'email' => 'admin@test.local', 'password' => 'secret', 'role' => 'admin']);
        $this->teacher = User::create(['name' => 'Dr. Abdur Rahman', 'email' => 't1@test.local', 'password' => 'secret', 'role' => 'teacher']);
        $teacherId = $ins('teachers', ['user_id' => $this->teacher->id, 'employee_code' => 'T001', 'department' => 'CSE', 'max_weekly_classes' => 12]);

        $offer = fn ($course) => $ins('course_offerings', ['course_id' => $course, 'section_id' => $this->secA, 'academic_term_id' => $term, 'weekly_theory_classes' => 3, 'weekly_lab_classes' => 0]);
        $oDs = $offer($ds);
        $oOs = $offer($os);
        $run = $ins('schedule_runs', ['batch_id' => $batch, 'academic_term_id' => $term, 'generated_by' => $admin->id, 'version' => 1, 'status' => 'PUBLISHED']);
        $entry = fn ($offering, $slotId) => $ins('schedule_entries', [
            'schedule_run_id' => $run, 'course_offering_id' => $offering, 'teacher_id' => $teacherId, 'co_teacher_id' => null,
            'room_id' => $room, 'time_slot_id' => $slotId, 'session_type' => 'THEORY', 'session_group_id' => null,
        ]);
        $entry($oDs, $this->slot['SUNDAY'][1]);
        $entry($oOs, $this->slot['SUNDAY'][3]);
        $entry($oDs, $this->slot['MONDAY'][2]);

        $student = fn ($name, $email, $section, $rep) => User::create([
            'name' => $name, 'email' => $email, 'password' => 'secret', 'role' => 'student',
            'section_id' => $section, 'class_rep' => $rep, 'is_active' => 1,
        ]);
        $this->cr = $student('Rafi', 'cr@test.local', $this->secA, 'CR');
        $this->otherCr = $student('Nila', 'crb@test.local', $this->secB, 'CR');
        $this->classmate = $student('Sami', 'sami@test.local', $this->secA, null);
    }

    private function approvedMessage(array $attributes = []): ClassMessage
    {
        $message = ClassMessage::create($attributes + [
            'teacher_id' => DB::table('teachers')->value('id'),
            'section_id' => $this->secA,
            'category' => 'GENERAL',
            'subject' => 'Assignment deadline',
            'body' => 'Submit by Monday.',
            'last_activity_at' => now(),
        ]);
        $message->replies()->create(['user_id' => $this->cr->id, 'kind' => 'NOTICE', 'body' => "*NOTICE*\nSubmit by Monday."]);
        $message->forceFill(['status' => ClassMessage::COMPLETED, 'completed_at' => now()])->save();

        return $message;
    }

    public function test_daily_routine_shows_today_with_live_state_and_share_text(): void
    {
        $response = $this->actingAs($this->classmate)->get(route('class.routine'))->assertOk();

        $plan = $response->viewData('plan');
        $this->assertSame('SUNDAY', $plan['day']);
        $this->assertTrue($plan['is_today']);
        $this->assertSame(['now', 'next'], $plan['sessions']->pluck('state')->all());

        $this->assertStringContainsString('*CSE 47 · Section A — Class Routine*', $plan['day_text']);
        $this->assertStringContainsString('1. 8:00 AM–8:50 AM · *CSE 3201* — Data Structures', $plan['day_text']);
        $this->assertStringContainsString('2. 10:00 AM–10:50 AM · *CSE 3203*', $plan['day_text']);
        $this->assertStringContainsString('— Sami', $plan['day_text']);
        $this->assertStringContainsString('*Monday*', $plan['week_text']);

        $response->assertSee('In progress')->assertSee('Up next')->assertSee('Share on WhatsApp');
    }

    public function test_other_days_and_unknown_days(): void
    {
        $plan = $this->actingAs($this->cr)->get(route('class.routine', ['day' => 'monday']))->assertOk()->viewData('plan');
        $this->assertSame('MONDAY', $plan['day']);
        $this->assertSame('2026-11-23', $plan['date']->toDateString());
        $this->assertSame(['later'], $plan['sessions']->pluck('state')->all());

        $this->assertSame('SUNDAY', $this->actingAs($this->cr)->get(route('class.routine', ['day' => 'xyz']))->viewData('plan')['day']);

        // Section B has no routine: empty, not an error.
        $this->actingAs($this->otherCr)->get(route('class.routine'))->assertOk()->assertSee('no routine has been published');
    }

    public function test_approved_cancellation_shows_on_that_day(): void
    {
        $this->approvedMessage([
            'category' => 'CANCEL', 'course_id' => DB::table('courses')->where('course_code', 'CSE 3203')->value('id'),
            'class_date' => '2026-11-22', 'time_slot_id' => $this->slot['SUNDAY'][3], 'subject' => 'No OS class',
        ]);

        $plan = $this->actingAs($this->cr)->get(route('class.routine'))->assertOk()->assertSee('is <strong>cancelled</strong>', false)->viewData('plan');
        $this->assertStringContainsString('*Changes for this day:*', $plan['day_text']);
        $this->assertStringContainsString('CSE 3203 class (10:00 AM–10:50 AM) is *cancelled*.', $plan['day_text']);

        // Not on Monday.
        $this->assertCount(0, $this->actingAs($this->cr)->get(route('class.routine', ['day' => 'MONDAY']))->viewData('plan')['changes']);
    }

    public function test_notice_board_ready_then_posted_then_undo(): void
    {
        $message = $this->approvedMessage();

        $this->actingAs($this->cr)->get(route('class.notices'))->assertOk()
            ->assertSee('Assignment deadline')
            ->assertSee('https://wa.me/?text=' . rawurlencode("*NOTICE*\nSubmit by Monday."), false)
            ->assertSee('Mark as posted');

        $this->assertSame(1, app(\App\Services\Student\ClassPortalService::class)->pendingCount($this->cr));

        $this->actingAs($this->cr)->post(route('class.notices.posted', $message))->assertRedirect(route('class.notices'));
        $message->refresh();
        $this->assertNotNull($message->posted_at);
        $this->assertSame($this->cr->id, (int) $message->posted_by);
        $this->assertSame(0, app(\App\Services\Student\ClassPortalService::class)->pendingCount($this->cr));

        $board = $this->actingAs($this->cr)->get(route('class.notices'))->viewData('board');
        $this->assertCount(0, $board['ready']);
        $this->assertCount(1, $board['posted']);

        $this->actingAs($this->cr)->post(route('class.notices.posted', $message), ['undo' => 1]);
        $this->assertNull($message->fresh()->posted_at);
    }

    public function test_notice_board_is_for_the_sections_reps_only(): void
    {
        $message = $this->approvedMessage();
        $open = ClassMessage::create([
            'teacher_id' => DB::table('teachers')->value('id'), 'section_id' => $this->secA, 'category' => 'GENERAL',
            'subject' => 'Open one', 'body' => 'x', 'last_activity_at' => now(),
        ]);

        $this->actingAs($this->classmate)->get(route('class.notices'))->assertForbidden();
        $this->actingAs($this->teacher)->get(route('class.notices'))->assertForbidden();
        $this->actingAs($this->otherCr)->post(route('class.notices.posted', $message))->assertForbidden();
        $this->actingAs($this->cr)->post(route('class.notices.posted', $open))->assertSessionHas('error');

        // The open session shows under "Need your notice" for this CR only.
        $this->assertCount(1, $this->actingAs($this->cr)->get(route('class.notices'))->viewData('board')['todo']);
        $this->assertCount(0, $this->actingAs($this->otherCr)->get(route('class.notices'))->viewData('board')['todo']);
    }

    public function test_notice_editor_has_typing_shortcuts(): void
    {
        $message = ClassMessage::create([
            'teacher_id' => DB::table('teachers')->value('id'), 'section_id' => $this->secA, 'category' => 'GENERAL',
            'subject' => 'Quiz', 'body' => 'Quiz on Sunday.', 'last_activity_at' => now(),
        ]);

        $this->actingAs($this->cr)->get(route('messages.show', $message))->assertOk()
            ->assertSee('Reset to template')
            ->assertSee('+ Attendance is mandatory.')
            ->assertSee('data-wrap="*"', false);
    }

    public function test_menu_and_dashboard_for_students(): void
    {
        $this->approvedMessage();

        $this->actingAs($this->cr)->get(route('dashboard'))->assertOk()
            ->assertSee('My Routine')->assertSee('Notice Board')->assertSee("Today's classes", false)->assertSee('CSE 3201');

        $this->actingAs($this->classmate)->get(route('dashboard'))->assertOk()
            ->assertSee('My Routine')->assertDontSee('Notice Board');
    }

    public function test_seeder_adds_one_cr_per_section_and_is_rerunnable(): void
    {
        $this->seed(ClassRepSeeder::class);
        $this->seed(ClassRepSeeder::class);

        $crs = User::where('class_rep', 'CR')->where('is_active', 1)->get()->groupBy('section_id');

        $this->assertSame(3, DB::table('sections')->count());
        $this->assertCount(3, $crs);
        $crs->each(fn ($group) => $this->assertCount(1, $group));

        // Existing CRs kept; the new one gets a predictable login.
        $this->assertSame('Rafi', $crs[$this->secA]->first()->name);
        $this->assertDatabaseHas('users', ['email' => 'cr.cse48.a@intellisched.edu', 'role' => 'student', 'class_rep' => 'CR']);
    }
}
