<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ClassMessage;
use App\Services\AuditLogger;
use App\Services\Chatbot\ChatbotService;
use App\Services\Student\ClassPortalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Student portal: the section's daily routine for every student, and
 * the notice board where a CR/ACR picks up approved notices to post
 * in the class WhatsApp group.
 */
class ClassPortalController extends Controller
{
    public function __construct(private ClassPortalService $portal)
    {
    }

    public function routine(Request $request): View
    {
        $student = $request->user()->loadMissing('section.batch');

        return view('class.routine', [
            'student' => $student,
            'plan' => $this->portal->dayPlan($student, $request->query('day'), ChatbotService::now()),
            'portal' => $this->portal,
        ]);
    }

    public function notices(Request $request): View
    {
        $rep = $this->rep($request);

        return view('class.notices', [
            'rep' => $rep,
            'board' => $this->portal->noticeBoard($rep),
        ]);
    }

    /**
     * Mark an approved notice as posted (or undo it).
     */
    public function posted(Request $request, ClassMessage $message): RedirectResponse
    {
        $rep = $this->rep($request);

        abort_unless((int) $message->section_id === (int) $rep->section_id, 403, 'This notice is not for your section.');

        if (!$message->isCompleted()) {
            return back()->with('error', 'Only approved notices can be marked as posted.');
        }

        $undo = $request->boolean('undo');
        $message->forceFill([
            'posted_at' => $undo ? null : now(),
            'posted_by' => $undo ? null : $rep->id,
        ])->save();

        AuditLogger::log($undo ? 'CLASS_NOTICE_UNPOSTED' : 'CLASS_NOTICE_POSTED', $message);

        return redirect()->route('class.notices')->with('success', $undo
            ? 'Moved back to "Ready to post".'
            : 'Marked as posted. Nice work!');
    }

    private function rep(Request $request)
    {
        $user = $request->user();

        abort_unless($user->isClassRep(), 403, 'The notice board is for your section\'s CR and ACR.');

        return $user->loadMissing('section.batch');
    }
}
