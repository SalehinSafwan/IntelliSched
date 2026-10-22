<?php

namespace App\Services\Scheduling;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class ScheduleRunService
{
    /**
     * Save an approved solver result as the PUBLISHED timetable
     * of a batch in a term. Earlier PUBLISHED runs of the same
     * batch and term become ARCHIVED.
     *
     * Returns the new schedule_runs.id.
     */
    public function publish(
        int $batchId,
        int $academicTermId,
        int $userId,
        array $input,
        array $result
    ): int {
        // Validate every scheduled session before opening a transaction.
        $entries = $this->buildEntries($input, $result);

        return DB::transaction(function () use (
            $batchId,
            $academicTermId,
            $userId,
            $result,
            $entries
        ) {
            // Serialize publishing per batch. Oracle does not allow
            // FOR UPDATE together with MAX(), so lock the batch row.
            DB::table('batches')
                ->where('id', $batchId)
                ->lockForUpdate()
                ->first();

            $latestVersion = DB::table('schedule_runs')
                ->where('batch_id', $batchId)
                ->where('academic_term_id', $academicTermId)
                ->max('version');

            $now = now();

            DB::table('schedule_runs')
                ->where('batch_id', $batchId)
                ->where('academic_term_id', $academicTermId)
                ->where('status', 'PUBLISHED')
                ->update([
                    'status' => 'ARCHIVED',
                    'updated_at' => $now,
                ]);

            $runId = DB::table('schedule_runs')->insertGetId([
                'batch_id' => $batchId,
                'academic_term_id' => $academicTermId,
                'generated_by' => $userId,
                'version' => ((int) $latestVersion) + 1,
                'status' => 'PUBLISHED',
                'objective_score' => $result['objective_value'] ?? null,
                'generated_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            foreach (array_chunk($entries, 500) as $chunk) {
                $rows = [];

                foreach ($chunk as $entry) {
                    $rows[] = $entry + [
                        'schedule_run_id' => $runId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                DB::table('schedule_entries')->insert($rows);
            }

            return $runId;
        }, 3);
    }

    /**
     * Turn the solver schedule into one schedule_entries row per
     * occupied time slot, rejecting anything inconsistent with
     * the scheduler input.
     */
    private function buildEntries(array $input, array $result): array
    {
        $schedule = $result['schedule'] ?? null;

        if (
            !is_array($schedule) ||
            !isset($result['total_sessions']) ||
            count($schedule) !== (int) $result['total_sessions']
        ) {
            throw new RuntimeException(
                'The solver result does not contain a complete schedule.'
            );
        }

        $offerings = collect($input['offerings'] ?? [])
            ->keyBy(fn ($offering) => (int) $offering['id']);

        if ($offerings->isEmpty()) {
            throw new RuntimeException(
                'No course offerings were present in the scheduler input.'
            );
        }

        $entries = [];
        $seenSessions = [];

        foreach ($schedule as $session) {
            $sessionKey = $session['session_key'] ?? '';

            // C# key format: O2-L1, O1-T1, etc.
            if (
                !is_string($sessionKey) ||
                !preg_match('/^O([0-9]+)-(?:L|T)[0-9]+$/', $sessionKey, $matches)
            ) {
                throw new RuntimeException(
                    'Unexpected session_key returned by the solver: ' .
                    (string) $sessionKey
                );
            }

            $offeringId = (int) $matches[1];

            if (!$offerings->has($offeringId)) {
                throw new RuntimeException(
                    "Solver session {$sessionKey} references an unknown offering."
                );
            }

            if (
                (int) ($session['section_id'] ?? 0) !==
                (int) ($offerings->get($offeringId)['section_id'] ?? 0)
            ) {
                throw new RuntimeException(
                    "Section mismatch for solver session {$sessionKey}."
                );
            }

            if (isset($seenSessions[$sessionKey])) {
                throw new RuntimeException(
                    "Duplicate solver session: {$sessionKey}."
                );
            }

            $seenSessions[$sessionKey] = true;

            $slotIds = $session['time_slot_ids'] ?? [];
            $duration = (int) ($session['duration_slots'] ?? 0);
            $type = strtolower((string) ($session['session_type'] ?? ''));
            $mode = strtolower((string) ($session['teacher_mode'] ?? ''));

            if (
                !is_array($slotIds) ||
                $duration < 1 ||
                count($slotIds) !== $duration ||
                count(array_unique($slotIds)) !== count($slotIds)
            ) {
                throw new RuntimeException(
                    "Invalid time-slot list for session {$sessionKey}."
                );
            }

            if (!in_array($type, ['theory', 'lab'], true)) {
                throw new RuntimeException(
                    "Unknown session type for {$sessionKey}."
                );
            }

            if ($type === 'lab' && $duration !== 3) {
                throw new RuntimeException(
                    "Lab session {$sessionKey} must occupy exactly three periods."
                );
            }

            // Single sessions have one teacher; joint sessions
            // (labs and the combined theory class) have the pair.
            $teacherIds = array_map('intval', $session['teacher_ids'] ?? []);
            $expectedTeachers = $mode === 'joint' ? 2 : 1;

            if (
                !in_array($mode, ['single', 'joint'], true) ||
                count($teacherIds) !== $expectedTeachers ||
                count(array_unique($teacherIds)) !== $expectedTeachers ||
                min($teacherIds) < 1
            ) {
                throw new RuntimeException(
                    "Invalid teachers for session {$sessionKey}."
                );
            }

            $roomId = (int) ($session['room_id'] ?? 0);

            if ($roomId < 1) {
                throw new RuntimeException(
                    "Missing room for session {$sessionKey}."
                );
            }

            foreach ($slotIds as $slotId) {
                if (!is_numeric($slotId) || (int) $slotId < 1) {
                    throw new RuntimeException(
                        "Invalid time-slot ID for session {$sessionKey}."
                    );
                }

                $entries[] = [
                    'course_offering_id' => $offeringId,
                    'teacher_id' => $teacherIds[0],
                    'co_teacher_id' => $teacherIds[1] ?? null,
                    'room_id' => $roomId,
                    'time_slot_id' => (int) $slotId,
                    'session_type' => ucfirst($type),
                    'session_group_id' => $type === 'lab'
                        ? $sessionKey
                        : null,
                ];
            }
        }

        return $entries;
    }
}
