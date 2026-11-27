<?php

namespace Tests\Feature;

use App\Models\ClassMessage;
use App\Models\ClassMessageReply;
use App\Models\User;
use App\Services\Messaging\ClassMessageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Teacher message → CR/ACR formal notice → approve / reject / reject
 * with correction → corrected notice → approve closes the session.
 */
class ClassNoticeWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;
    private User $otherTeacher;
    private User $cr;
    private User $acr;
    private User $classmate;
    private User $admin;
    private int $sectionId;

    protected function setUp(): void
    {
        parent::setUp();

        $now = now();
        $ins = fn (string $table, array $row) => DB::table($table)->insertGetId($row + ['created_at' => $now, 'updated_at' => $now]);

        $ins('academic_terms', ['name' => 'Fall 2026', 'academic_year' => '2026', 'term' => 'Fall', 'start_date' => '2026-09-01', 'end_date' => '2026-12-31', 'status' => 'ACTIVE']);
        $batch = $ins('batches', ['name' => 'Batch 21', 'department' => 'CSE', 'program' => 'BSc', 'admission_year' => 2021, 'seniority_order' => 1]);
        $this->sectionId = $ins('sections', ['batch_id' => $batch, 'name' => 'A', 'student_count' => 40]);

        $this->admin = User::create(['name' => 'Admin', 'email' => 'admin@test.local', 'password' => 'secret', 'role' => 'admin']);
        $this->teacher = User::create(['name' => 'Dr. Abdur Rahman', 'email' => 't1@test.local', 'password' => 'secret', 'role' => 'teacher']);
        $this->otherTeacher = User::create(['name' => 'Nusrat Jahan', 'email' => 't2@test.local', 'password' => 'secret', 'role' => 'teacher']);
        $teacherId = $ins('teachers', ['user_id' => $this->teacher->id, 'employee_code' => 'T001', 'department' => 'CSE', 'max_weekly_classes' => 12]);
        $ins('teachers', ['user_id' => $this->otherTeacher->id, 'employee_code' => 'T002', 'department' => 'CSE', 'max_weekly_classes' => 12]);
        $ins('teacher_supervisions', ['teacher_id' => $teacherId, 'section_id' => $this->sectionId]);

        $student = fn ($name, $email, $rep) => User::create([
            'name' => $name, 'email' => $email, 'password' => 'secret', 'role' => 'student',
            'section_id' => $this->sectionId, 'class_rep' => $rep, 'is_active' => 1,
        ]);
        $this->cr = $student('Rafi', 'cr@test.local', 'CR');
        $this->acr = $student('Mim', 'acr@test.local', 'ACR');
        $this->classmate = $student('Sami', 'sami@test.local', null);
    }

    private function sendMessage(): ClassMessage
    {
        $this->actingAs($this->teacher)->post(route('messages.store'), [
            'section_id' => $this->sectionId,
            'category' => 'GENERAL',
            'subject' => 'Quiz on Sunday',
            'body' => 'There will be a quiz on linked lists on Sunday.',
        ])->assertRedirect();

        return ClassMessage::firstOrFail();
    }

    public function test_full_session_reject_with_correction_then_approve(): void
    {
        $message = $this->sendMessage();
        $this->assertSame(ClassMessage::AWAITING_NOTICE, $message->status);

        // CR sees the WhatsApp-style template prefilled.
        $this->actingAs($this->cr)->get(route('messages.show', $message))
            ->assertOk()
            ->assertSee('*NOTICE — GENERAL NOTICE*', false)
            ->assertSee('Send for Approval');

        // Teacher cannot review before a notice exists.
        $this->actingAs($this->teacher)->get(route('messages.show', $message))
            ->assertOk()
            ->assertSee('Waiting for the CR/ACR')
            ->assertDontSee('Review notice');

        // v1 from the CR.
        $this->actingAs($this->cr)->post(route('messages.notice', $message), ['notice' => '*Quiz* on Sunday.'])->assertRedirect();
        $message->refresh();
        $this->assertSame(ClassMessage::PENDING_REVIEW, $message->status);
        $v1 = $message->notices()->firstOrFail();

        // While under review the ACR cannot submit another version.
        $this->actingAs($this->acr)->post(route('messages.notice', $message), ['notice' => 'Another'])
            ->assertSessionHasErrors('notice');
        $this->assertSame(1, $message->notices()->count());

        // Correction text is required for "reject with correction".
        $this->actingAs($this->teacher)->post(route('messages.review', $message), ['notice_id' => $v1->id, 'decision' => 'CORRECT'])
            ->assertSessionHasErrors('correction');

        $this->actingAs($this->teacher)->get(route('messages.show', $message))->assertSee('Review notice v1');
        $this->actingAs($this->teacher)->post(route('messages.review', $message), [
            'notice_id' => $v1->id, 'decision' => 'CORRECT', 'correction' => 'Mention the topic: linked lists.',
        ])->assertRedirect();

        $message->refresh();
        $this->assertSame(ClassMessage::CHANGES_REQUESTED, $message->status);
        $this->assertNull($message->completed_at);

        // The ACR sees the correction and the last version prefilled.
        $this->actingAs($this->acr)->get(route('messages.show', $message))
            ->assertOk()
            ->assertSee('Mention the topic: linked lists.')
            ->assertSee('Corrected notice (v2)');

        $this->actingAs($this->acr)->post(route('messages.notice', $message), ['notice' => '*Quiz* on linked lists, Sunday.'])->assertRedirect();
        $message->refresh();
        $v2 = $message->notices()->get()->last();
        $this->assertNotSame($v1->id, $v2->id);
        $this->assertSame(ClassMessage::PENDING_REVIEW, $message->status);

        // A stale page cannot approve the old version.
        $this->actingAs($this->teacher)->post(route('messages.review', $message), ['notice_id' => $v1->id, 'decision' => 'APPROVE'])
            ->assertSessionHasErrors('review');

        $this->actingAs($this->teacher)->post(route('messages.review', $message), ['notice_id' => $v2->id, 'decision' => 'APPROVE'])->assertRedirect();
        $message->refresh();
        $this->assertTrue($message->isCompleted());
        $this->assertNotNull($message->completed_at);

        $kinds = $message->replies()->pluck('kind')->all();
        $this->assertSame([ClassMessageReply::NOTICE, ClassMessageReply::REJECTED, ClassMessageReply::NOTICE, ClassMessageReply::APPROVED], $kinds);

        // Closed: no comments, notices or reviews from anyone.
        $this->actingAs($this->teacher)->post(route('messages.reply', $message), ['body' => 'one more thing'])->assertSessionHas('error');
        $this->actingAs($this->cr)->post(route('messages.reply', $message), ['body' => 'ok'])->assertSessionHas('error');
        $this->actingAs($this->cr)->post(route('messages.notice', $message), ['notice' => 'v3'])->assertSessionHasErrors('notice');
        $this->actingAs($this->teacher)->post(route('messages.review', $message), ['notice_id' => $v2->id, 'decision' => 'REJECT'])->assertSessionHasErrors('review');
        $this->assertSame(4, $message->replies()->count());

        $this->actingAs($this->cr)->get(route('messages.show', $message))
            ->assertOk()
            ->assertSee('Session completed')
            ->assertSee('Copy notice')
            ->assertSee('<strong>Quiz</strong>', false)
            ->assertDontSee('Send for Approval')
            ->assertDontSee('Send Comment');
    }

    public function test_plain_reject_needs_no_correction(): void
    {
        $message = $this->sendMessage();
        $this->actingAs($this->cr)->post(route('messages.notice', $message), ['notice' => 'Draft'])->assertRedirect();
        $notice = $message->notices()->firstOrFail();

        $this->actingAs($this->teacher)->post(route('messages.review', $message), ['notice_id' => $notice->id, 'decision' => 'REJECT'])
            ->assertSessionHasNoErrors();

        $review = $message->replies()->where('kind', ClassMessageReply::REJECTED)->firstOrFail();
        $this->assertNull($review->correction);
        $this->assertSame(ClassMessage::CHANGES_REQUESTED, $message->fresh()->status);
    }

    public function test_only_the_right_people_can_act(): void
    {
        $message = $this->sendMessage();

        // The teacher does not write notices; students do not review.
        $this->actingAs($this->teacher)->post(route('messages.notice', $message), ['notice' => 'x'])->assertForbidden();
        $this->actingAs($this->cr)->post(route('messages.notice', $message), ['notice' => 'Draft'])->assertRedirect();
        $notice = $message->notices()->firstOrFail();

        $this->actingAs($this->cr)->post(route('messages.review', $message), ['notice_id' => $notice->id, 'decision' => 'APPROVE'])->assertForbidden();
        $this->actingAs($this->otherTeacher)->post(route('messages.review', $message), ['notice_id' => $notice->id, 'decision' => 'APPROVE'])->assertForbidden();
        $this->actingAs($this->classmate)->post(route('messages.notice', $message), ['notice' => 'x'])->assertForbidden();
        $this->actingAs($this->classmate)->get(route('messages.show', $message))->assertForbidden();

        $this->assertSame(ClassMessage::PENDING_REVIEW, $message->fresh()->status);
    }

    public function test_comments_still_work_while_open_and_inbox_filters_by_state(): void
    {
        $message = $this->sendMessage();
        $this->actingAs($this->cr)->post(route('messages.reply', $message), ['body' => 'Sir, which room?'])->assertRedirect();
        $this->assertSame(ClassMessageReply::COMMENT, $message->replies()->firstOrFail()->kind);
        $this->assertSame(ClassMessage::AWAITING_NOTICE, $message->fresh()->status);

        $this->actingAs($this->cr)->get(route('messages.index'))->assertOk()->assertSee('Notice needed')->assertSee('Quiz on Sunday');
        $this->actingAs($this->cr)->get(route('messages.index', ['status' => 'completed']))->assertOk()->assertDontSee('Quiz on Sunday');
        $this->actingAs($this->cr)->get(route('messages.index', ['status' => 'open']))->assertOk()->assertSee('Quiz on Sunday');
    }

    public function test_notice_markup_is_escaped(): void
    {
        $html = (string) ClassMessageService::formatNotice("*Bold* and _it_ <script>x</script> 2*3*4 snake_case_name");

        $this->assertStringContainsString('<strong>Bold</strong>', $html);
        $this->assertStringContainsString('<em>it</em>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringContainsString('2*3*4', $html);
        $this->assertStringContainsString('snake_case_name', $html);
    }

    public function test_top_menu_renders_for_every_role(): void
    {
        $this->actingAs($this->admin)->get(route('dashboard'))->assertOk()
            ->assertSee('class="topbar"', false)->assertSee('Academic Setup')->assertSee('Administration')->assertDontSee('class="sidebar"', false);

        $this->actingAs($this->teacher)->get(route('dashboard'))->assertOk()
            ->assertSee('Teacher Portal')->assertSee('CR/ACR Messages')->assertDontSee('Academic Setup');

        $this->actingAs($this->cr)->get(route('dashboard'))->assertOk()
            ->assertSee('Teacher Messages')->assertSee('Class Routines')->assertDontSee('Teacher Portal');

        $this->actingAs($this->classmate)->get(route('dashboard'))->assertOk()
            ->assertDontSee('Teacher Messages')->assertSee('Exam Schedules');
    }
}
