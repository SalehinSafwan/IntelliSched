<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ChatbotTest extends TestCase
{
    use RefreshDatabase;

    private User $student;
    private User $rahman;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        config(['intellisched.chatbot.timezone' => 'Asia/Dhaka']);

        // Sunday 08:30 local time, during the first period.
        $this->travelTo(CarbonImmutable::parse('2026-11-22 08:30', 'Asia/Dhaka'));

        $this->seedRoutine();
    }

    private function seedRoutine(): void
    {
        $now = now();
        $ins = fn (string $table, array $row) => DB::table($table)->insertGetId($row + ['created_at' => $now, 'updated_at' => $now]);

        $term = $ins('academic_terms', ['name' => 'Fall 2026', 'academic_year' => '2026', 'term' => 'Fall', 'start_date' => '2026-09-01', 'end_date' => '2026-12-31', 'status' => 'ACTIVE']);
        $batch = $ins('batches', ['name' => 'Batch 21', 'department' => 'CSE', 'program' => 'BSc', 'admission_year' => 2021, 'seniority_order' => 1]);
        $secA = $ins('sections', ['batch_id' => $batch, 'name' => 'A', 'student_count' => 40]);
        $secB = $ins('sections', ['batch_id' => $batch, 'name' => 'B', 'student_count' => 40]);

        $slot = [];
        foreach (['SUNDAY', 'MONDAY'] as $day) {
            foreach ([[1, '08:00', '08:50', 0], [2, '09:00', '09:50', 0], [3, '10:00', '10:50', 0], [4, '11:00', '11:20', 1], [5, '11:20', '12:10', 0]] as [$n, $s, $e, $break]) {
                $slot[$day][$n] = $ins('time_slots', ['day' => $day, 'slot_number' => $n, 'start_time' => $s, 'end_time' => $e, 'is_break' => $break]);
            }
        }

        $r301 = $ins('rooms', ['room_code' => 'R301', 'room_name' => 'Room 301', 'room_type' => 'THEORY', 'capacity' => 60]);
        $lab1 = $ins('rooms', ['room_code' => 'LAB1', 'room_name' => 'Lab 1', 'room_type' => 'LAB', 'capacity' => 40]);

        $course = fn ($code, $name, $type) => $ins('courses', ['course_code' => $code, 'course_name' => $name, 'department' => 'CSE', 'course_type' => $type, 'credit_hours' => 3]);
        $ds = $course('CSE 3201', 'Data Structures', 'THEORY');
        $dsLab = $course('CSE 3202', 'Data Structures Lab', 'LAB');
        $la = $course('MATH 3203', 'Linear Algebra', 'THEORY');
        $draftOnly = $course('CSE 3299', 'Draft Topics', 'THEORY');

        $this->admin = User::create(['name' => 'Admin', 'email' => 'admin@test.local', 'password' => 'secret', 'role' => 'admin']);
        $this->student = User::create(['name' => 'Student', 'email' => 'student@test.local', 'password' => 'secret', 'role' => 'student', 'section_id' => $secA]);

        $teacher = function (string $name, string $code) use ($ins) {
            $user = User::create(['name' => $name, 'email' => strtolower($code) . '@test.local', 'password' => 'secret', 'role' => 'teacher']);
            return [$user, $ins('teachers', ['user_id' => $user->id, 'employee_code' => $code, 'department' => 'CSE', 'max_weekly_classes' => 12])];
        };
        [$this->rahman, $tRahman] = $teacher('Dr. Abdur Rahman', 'T001');
        [, $tNusrat] = $teacher('Nusrat Jahan', 'T002');
        [, $tKamal] = $teacher('Kamal Hossain', 'T003');

        $offer = fn ($courseId, $sectionId) => $ins('course_offerings', ['course_id' => $courseId, 'section_id' => $sectionId, 'academic_term_id' => $term, 'weekly_theory_classes' => 3, 'weekly_lab_classes' => 0]);
        $aDs = $offer($ds, $secA);
        $aLab = $offer($dsLab, $secA);
        $aLa = $offer($la, $secA);
        $aDraft = $offer($draftOnly, $secA);
        $bDs = $offer($ds, $secB);

        $published = $ins('schedule_runs', ['batch_id' => $batch, 'academic_term_id' => $term, 'generated_by' => $this->admin->id, 'version' => 1, 'status' => 'PUBLISHED']);
        $draft = $ins('schedule_runs', ['batch_id' => $batch, 'academic_term_id' => $term, 'generated_by' => $this->admin->id, 'version' => 2, 'status' => 'DRAFT']);

        $entry = fn ($run, $offering, $teacherId, $co, $room, $slotId, $type = 'THEORY', $group = null) => $ins('schedule_entries', [
            'schedule_run_id' => $run, 'course_offering_id' => $offering, 'teacher_id' => $teacherId, 'co_teacher_id' => $co,
            'room_id' => $room, 'time_slot_id' => $slotId, 'session_type' => $type, 'session_group_id' => $group,
        ]);

        // Section A: Sunday 8:00 DS, Sunday 9:00–10:50 DS lab, Monday 8:00 LA, Monday 9:00 DS.
        $entry($published, $aDs, $tRahman, $tNusrat, $r301, $slot['SUNDAY'][1]);
        $entry($published, $aLab, $tNusrat, null, $lab1, $slot['SUNDAY'][2], 'LAB', 'lab-1');
        $entry($published, $aLab, $tNusrat, null, $lab1, $slot['SUNDAY'][3], 'LAB', 'lab-1');
        $entry($published, $aLa, $tKamal, null, $r301, $slot['MONDAY'][1]);
        $entry($published, $aDs, $tRahman, $tNusrat, $r301, $slot['MONDAY'][2]);
        // Section B: Kamal teaches DS.
        $entry($published, $bDs, $tKamal, null, $r301, $slot['MONDAY'][3]);
        // Draft only: must never be visible.
        $entry($draft, $aDraft, $tRahman, null, $r301, $slot['SUNDAY'][5]);
    }

    private function ask(User $user, string $question)
    {
        return $this->actingAs($user)->postJson(route('assistant.ask'), ['question' => $question])->assertOk();
    }

    public function test_student_todays_classes_merge_lab_periods(): void
    {
        $res = $this->ask($this->student, "Today's class")->json();

        $this->assertTrue($res['ok']);
        $this->assertSame('DAY_SCHEDULE', $res['intent']);
        $this->assertCount(2, $res['items']);
        $this->assertStringContainsString('CSE 3201', $res['items'][0]['title']);
        $this->assertStringContainsString('(Lab)', $res['items'][1]['title']);
        $this->assertStringContainsString('9:00 AM – 10:50 AM', $res['items'][1]['subtitle']);
    }

    public function test_student_course_when_and_teacher(): void
    {
        $when = $this->ask($this->student, 'CSE 3201 class when')->json();
        $this->assertSame('COURSE_WHEN', $when['intent']);
        $this->assertCount(2, $when['items']);

        $who = $this->ask($this->student, 'CSE 3201 class by which teacher')->json();
        $this->assertSame('COURSE_TEACHER', $who['intent']);
        $this->assertStringContainsString('Dr. Abdur Rahman and Nusrat Jahan', $who['answer']);
        // Section B's teacher is not mixed in.
        $this->assertStringNotContainsString('Kamal', $who['answer']);
    }

    public function test_student_room_next_now_and_free(): void
    {
        $this->assertStringContainsString('LAB1', $this->ask($this->student, 'Where is CSE 3202 class?')->json('answer'));

        $next = $this->ask($this->student, 'What is my next class?')->json('answer');
        $this->assertStringContainsString('CSE 3202', $next);
        $this->assertStringContainsString('today at 9:00 AM', $next);

        $now = $this->ask($this->student, 'Class now')->json('answer');
        $this->assertStringContainsString('CSE 3201', $now);

        $free = $this->ask($this->student, 'Am I free on Sunday?')->json();
        $this->assertSame('FREE_SLOTS', $free['intent']);
        $this->assertCount(1, $free['items']); // period 5 only; the break is not a period
        $this->assertSame('Period 5', $free['items'][0]['subtitle']);
    }

    public function test_student_teacher_questions_are_limited_to_their_section(): void
    {
        $res = $this->ask($this->student, "When is Kamal sir's class?")->json();

        $this->assertSame('TEACHER_CLASSES', $res['intent']);
        $this->assertCount(1, $res['items']);
        $this->assertStringContainsString('MATH 3203', $res['items'][0]['title']);
    }

    public function test_draft_routines_are_ignored(): void
    {
        $res = $this->ask($this->student, 'When is CSE 3299 class?')->json();

        $this->assertFalse($res['ok']);
        $this->assertSame('semantic', $res['stage']);
        $this->assertStringContainsString('not in your published routine', $res['answer']);
    }

    public function test_lexical_and_syntax_errors_are_reported(): void
    {
        $lexical = $this->ask($this->student, 'blah blah class')->json();
        $this->assertSame('lexical', $lexical['stage']);

        $syntax = $this->ask($this->student, 'Who teaches CSE 3201 MATH 3203')->json();
        $this->assertSame('syntax', $syntax['stage']);
    }

    public function test_teacher_sees_own_classes_and_co_teachers(): void
    {
        $today = $this->ask($this->rahman, "Today's class")->json();
        $this->assertCount(1, $today['items']); // draft CSE 3299 is hidden
        $this->assertStringContainsString('CSE 3201', $today['items'][0]['title']);

        $co = $this->ask($this->rahman, 'Who are my co-teachers?')->json();
        $this->assertSame('MY_TEACHERS', $co['intent']);
        $this->assertSame('Nusrat Jahan', $co['items'][0]['subtitle']);

        // Not taught by Rahman, but in a section he teaches.
        $la = $this->ask($this->rahman, 'Who teaches Linear Algebra?')->json('answer');
        $this->assertStringContainsString('Kamal Hossain', $la);
    }

    public function test_question_catalog_uses_real_examples(): void
    {
        foreach ([$this->student, $this->rahman] as $user) {
            $res = $this->actingAs($user)->getJson(route('assistant.questions'))->assertOk()->json();

            $this->assertNotEmpty($res['groups']);
            $this->assertContains('When is CSE 3201 class?', $res['quick']);

            // Every example in the catalog is answered without a lexical,
            // syntax or server error.
            foreach ($res['groups'] as $group) {
                foreach ($group['formats'] as $format) {
                    $reply = $this->ask($user, $format['example'])->json();
                    $this->assertNotContains($reply['stage'], ['lexical', 'syntax', 'error'], $user->role . ' / ' . $format['example'] . ': ' . $reply['answer']);
                }
            }
        }
    }

    public function test_only_teachers_and_students_can_use_the_assistant(): void
    {
        $this->postJson(route('assistant.ask'), ['question' => 'help'])->assertUnauthorized();
        $this->actingAs($this->admin)->postJson(route('assistant.ask'), ['question' => 'help'])->assertForbidden();
    }

    public function test_panel_renders_in_portal_layout_for_students_and_teachers_only(): void
    {
        $this->actingAs($this->student)->get(route('dashboard'))->assertOk()->assertSee('id="chatbotPanel"', false);
        $this->actingAs($this->rahman)->get(route('dashboard'))->assertOk()->assertSee('id="chatbotPanel"', false);
        $this->actingAs($this->admin)->get(route('dashboard'))->assertOk()->assertDontSee('id="chatbotPanel"', false);
    }

    public function test_question_length_is_limited(): void
    {
        $this->actingAs($this->student)
            ->postJson(route('assistant.ask'), ['question' => str_repeat('a', 300)])
            ->assertStatus(422);
    }
}
