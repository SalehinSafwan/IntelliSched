<?php

namespace App\Http\Controllers;

use App\Models\AcademicTerm;
use App\Models\ClassMessage;
use App\Models\ClassMessageReply;
use App\Models\CourseOffering;
use App\Models\Teacher;
use App\Models\TimeSlot;
use App\Services\AuditLogger;
use App\Services\Messaging\ClassMessageService;
use App\Services\Teacher\TeacherSectionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Teacher → CR/ACR messages about class reschedules, cancellations
 * and other notices. A message reaches only the CR and ACR of the one
 * section it was sent to; see ClassMessageService for the rules.
 */
class ClassMessageController extends Controller
{
    public function __construct(
        private ClassMessageService $messages,
        private TeacherSectionService $sectionService
    ) {
    }

    public function index(Request $request): View
    {
        $user = $request->user();
        $teacher = $this->messages->teacherOf($user);
        $sectionFilter = (int) $request->query('section');
        $statusFilter = in_array($request->query('status'), ['open', 'completed'], true) ? $request->query('status') : null;

        $messages = $this->messages->visibleTo($user)
            ->with(['section.batch', 'course', 'teacher.user', 'timeSlot', 'newTimeSlot'])
            ->withCount('replies')
            ->when($teacher && $sectionFilter, fn ($query) => $query->where('section_id', $sectionFilter))
            ->when($statusFilter === 'open', fn ($query) => $query->where('status', '!=', ClassMessage::COMPLETED))
            ->when($statusFilter === 'completed', fn ($query) => $query->where('status', ClassMessage::COMPLETED))
            ->orderByDesc('last_activity_at')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('messages.index', [
            'messages' => $messages,
            'teacher' => $teacher,
            'unread' => $this->messages->unreadIds($user),
            'sections' => $teacher ? $this->sectionService->reachableSections($teacher) : collect(),
            'sectionFilter' => $sectionFilter,
            'statusFilter' => $statusFilter,
            'service' => $this->messages,
            'isRep' => $user->isClassRep(),
            'student' => $user->loadMissing('section.batch'),
        ]);
    }

    public function create(Request $request): View
    {
        $teacher = $this->ownTeacher($request);
        $sections = $this->sectionService->reachableSections($teacher);
        $sectionIds = $sections->pluck('id')->all();

        $term = AcademicTerm::where('status', 'ACTIVE')->orderByDesc('id')->first();

        // Courses each section studies, for the optional course field.
        $courses = CourseOffering::with('course')
            ->whereIn('section_id', $sectionIds ?: [0])
            ->when($term, fn ($query) => $query->where('academic_term_id', $term->id))
            ->get()
            ->groupBy('section_id')
            ->map(fn ($offerings) => $offerings
                ->filter(fn ($offering) => $offering->course)
                ->map(fn ($offering) => [
                    'id' => (int) $offering->course->id,
                    'label' => $offering->course->course_code . ' — ' . $offering->course->course_name,
                ])
                ->unique('id')
                ->values());

        $recipients = $sections->mapWithKeys(fn ($section) => [
            $section->id => $this->messages->recipients((int) $section->id)
                ->map(fn ($user) => "{$user->name} ({$user->class_rep})")
                ->values(),
        ]);

        $slots = $this->slotOptions();

        // Prefill from the free-slot finder or a supervision card.
        $prefill = [
            'section_id' => (int) $request->query('section'),
            'category' => $request->query('category'),
            'new_time_slot_id' => (int) $request->query('slot'),
        ];

        return view('messages.create', compact('teacher', 'sections', 'courses', 'recipients', 'slots', 'prefill'));
    }

    public function store(Request $request): RedirectResponse
    {
        $teacher = $this->ownTeacher($request);
        $categories = array_keys(config('intellisched.message_categories'));

        $data = $request->validate([
            'section_id' => ['required', 'integer', Rule::exists('sections', 'id')],
            'category' => ['required', Rule::in($categories)],
            'course_id' => ['nullable', 'integer', Rule::exists('courses', 'id')],
            'subject' => ['required', 'string', 'max:150'],
            'body' => ['required', 'string', 'max:2000'],
            'class_date' => ['nullable', 'date', 'required_if:category,RESCHEDULE,CANCEL'],
            'time_slot_id' => ['nullable', 'integer', Rule::exists('time_slots', 'id')],
            'new_date' => ['nullable', 'date', 'required_if:category,RESCHEDULE,EXTRA_CLASS'],
            'new_time_slot_id' => ['nullable', 'integer', Rule::exists('time_slots', 'id')],
        ], [
            'class_date.required_if' => 'Give the date of the class being rescheduled or cancelled.',
            'new_date.required_if' => 'Give the date the class now takes place.',
        ]);

        $sectionId = (int) $data['section_id'];

        if (!$this->sectionService->canReach($teacher, $sectionId)) {
            throw ValidationException::withMessages([
                'section_id' => 'You can only message sections you teach or supervise.',
            ]);
        }

        if ($this->messages->recipients($sectionId)->isEmpty()) {
            throw ValidationException::withMessages([
                'section_id' => 'This section has no active CR or ACR yet. Ask the administrator to assign one.',
            ]);
        }

        if (!empty($data['course_id'])
            && !CourseOffering::where('section_id', $sectionId)->where('course_id', $data['course_id'])->exists()) {
            throw ValidationException::withMessages([
                'course_id' => 'This section does not study the selected course.',
            ]);
        }

        // Dates and periods only apply to the categories that use them.
        $category = $data['category'];

        if (!in_array($category, ['RESCHEDULE', 'CANCEL', 'ROOM_CHANGE', 'ASSESSMENT'], true)) {
            $data['class_date'] = null;
            $data['time_slot_id'] = null;
        }

        if (!in_array($category, ['RESCHEDULE', 'EXTRA_CLASS'], true)) {
            $data['new_date'] = null;
            $data['new_time_slot_id'] = null;
        }

        $message = ClassMessage::create($data + [
            'teacher_id' => $teacher->id,
            'last_activity_at' => now(),
            'teacher_seen_at' => now(),
        ]);

        AuditLogger::log('CLASS_MESSAGE_SENT', $message, null, $message->only(['section_id', 'category', 'subject']));

        return redirect()->route('messages.show', $message)
            ->with('success', 'Message sent to the CR and ACR of ' . $message->section->label . '. They will reply with a formal notice for your approval.');
    }

    public function show(Request $request, ClassMessage $message): View
    {
        $user = $request->user();

        abort_unless($this->messages->canView($user, $message), 403, 'This message was not sent to you.');

        $message->load(['section.batch', 'course', 'teacher.user', 'timeSlot', 'newTimeSlot', 'replies.user']);
        $this->messages->markSeen($user, $message);

        $notices = $message->replies->where('kind', ClassMessageReply::NOTICE)->values();
        $reviews = $message->replies->filter->isReview()->keyBy(fn ($review) => (int) $review->notice_reply_id);
        $latestNotice = $notices->last();

        // Composer text: the corrected notice starts from the last
        // version; the first one from the WhatsApp-style template.
        $canSubmitNotice = $this->messages->canSubmitNotice($user, $message);
        $noticeTemplate = $canSubmitNotice ? $this->messages->noticeTemplate($message, $user) : null;
        $noticeDraft = $canSubmitNotice ? ($latestNotice?->body ?? $noticeTemplate) : null;

        return view('messages.show', [
            'message' => $message,
            'isSender' => $this->messages->isSender($user, $message),
            'recipients' => $this->messages->recipients((int) $message->section_id),
            'readers' => $message->reads()->with('user')->get(),
            'notices' => $notices,
            'reviews' => $reviews,
            'latestNotice' => $latestNotice,
            'lastReview' => $latestNotice ? $reviews->get((int) $latestNotice->id) : null,
            'approvedNotice' => $message->isCompleted() ? $latestNotice : null,
            'canComment' => $this->messages->canComment($user, $message),
            'canSubmitNotice' => $canSubmitNotice,
            'canReview' => $this->messages->canReview($user, $message),
            'noticeDraft' => $noticeDraft,
            'noticeTemplate' => $noticeTemplate,
        ]);
    }

    /**
     * A plain comment from either side while the session is open.
     */
    public function reply(Request $request, ClassMessage $message): RedirectResponse
    {
        $user = $request->user();

        abort_unless($this->messages->canView($user, $message), 403, 'This message was not sent to you.');

        if (!$this->messages->canComment($user, $message)) {
            return back()->with('error', 'This session is completed. No more messages can be sent.');
        }

        $data = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $message->replies()->create([
            'user_id' => $user->id,
            'kind' => ClassMessageReply::COMMENT,
            'body' => $data['body'],
        ]);
        $message->forceFill(['last_activity_at' => now()])->save();

        // The author has obviously seen their own reply.
        $this->messages->markSeen($user, $message);

        return redirect()->to(route('messages.show', $message) . '#latest')->with('success', 'Comment sent.');
    }

    /**
     * The CR/ACR's formal notice (first version or a correction).
     */
    public function notice(Request $request, ClassMessage $message): RedirectResponse
    {
        $user = $request->user();

        abort_unless($this->messages->canView($user, $message), 403, 'This message was not sent to you.');
        abort_if($this->messages->isSender($user, $message), 403, 'Only the CR or ACR writes the notice.');

        $data = $request->validate([
            'notice' => ['required', 'string', 'max:2000'],
        ], [
            'notice.required' => 'Write the notice for your classmates.',
        ]);

        $resubmission = $message->status === ClassMessage::CHANGES_REQUESTED;
        $notice = $this->messages->submitNotice($user, $message, $data['notice']);
        $this->messages->markSeen($user, $message);

        AuditLogger::log($resubmission ? 'CLASS_NOTICE_RESUBMITTED' : 'CLASS_NOTICE_SUBMITTED', $message, null, ['notice_id' => $notice->id]);

        return redirect()->to(route('messages.show', $message) . '#latest')
            ->with('success', ($resubmission ? 'Corrected notice' : 'Notice') . ' sent to ' . $message->teacher?->display_name . ' for approval.');
    }

    /**
     * The teacher's decision on the notice under review.
     */
    public function review(Request $request, ClassMessage $message): RedirectResponse
    {
        $user = $request->user();

        abort_unless($this->messages->isSender($user, $message), 403, 'Only the teacher who sent this message reviews its notice.');

        $data = $request->validate([
            'notice_id' => ['required', 'integer'],
            'decision' => ['required', Rule::in(['APPROVE', 'REJECT', 'CORRECT'])],
            'correction' => ['nullable', 'string', 'max:2000', 'required_if:decision,CORRECT'],
        ], [
            'correction.required_if' => 'Write the correction you want in the notice.',
        ]);

        $approve = $data['decision'] === 'APPROVE';
        $correction = $data['decision'] === 'CORRECT' ? $data['correction'] : null;

        $this->messages->review($user, $message, (int) $data['notice_id'], $approve, $correction);
        $this->messages->markSeen($user, $message);

        AuditLogger::log($approve ? 'CLASS_NOTICE_APPROVED' : 'CLASS_NOTICE_REJECTED', $message, null, [
            'notice_id' => (int) $data['notice_id'],
            'correction' => $correction,
        ]);

        return redirect()->to(route('messages.show', $message) . '#latest')->with('success', $approve
            ? 'Notice approved. The session is completed and closed.'
            : 'Notice rejected. The CR/ACR has been asked for a corrected version.');
    }

    public function destroy(Request $request, ClassMessage $message): RedirectResponse
    {
        abort_unless($this->messages->isSender($request->user(), $message), 403, 'Only the sender can delete this message.');

        $old = $message->only(['section_id', 'category', 'subject']);
        $message->delete();

        AuditLogger::log('CLASS_MESSAGE_DELETED', null, $old);

        return redirect()->route('messages.index')->with('success', 'Message deleted.');
    }

    private function ownTeacher(Request $request): Teacher
    {
        $teacher = $this->messages->teacherOf($request->user());

        abort_unless($teacher, 404, 'Your account is not linked to a teacher profile.');

        return $teacher;
    }

    /**
     * Teaching periods as "Sunday · P2 (09:00–09:50)", in week order.
     */
    private function slotOptions(): array
    {
        $dayOrder = array_flip(config('intellisched.days'));

        return TimeSlot::where('is_break', 0)
            ->get()
            ->sortBy(fn ($slot) => [$dayOrder[strtoupper($slot->day)] ?? 99, (int) $slot->slot_number])
            ->map(fn ($slot) => [
                'id' => (int) $slot->id,
                'day' => strtoupper($slot->day),
                'label' => ucfirst(strtolower($slot->day)) . " · P{$slot->slot_number} ({$slot->start_time}–{$slot->end_time})",
            ])
            ->values()
            ->all();
    }
}
