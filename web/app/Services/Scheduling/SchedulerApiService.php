<?php

namespace App\Services\Scheduling;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class SchedulerApiService
{
    /**
     * Solve a timetable: POST /api/scheduler/solve.
     *
     * The C# API validates its own result, so a successful
     * response always carries validation.is_valid = true.
     */
    public function generate(array $input): array
    {
        $result = $this->post('/api/scheduler/solve', $input);

        if ($result['success'] !== true) {
            $message = $result['message']
                ?? $result['error']
                ?? $result['status']
                ?? 'Unknown scheduling failure';

            $details = $result['validation']['errors']
                ?? $result['errors']
                ?? $result['sessions']
                ?? [];

            if (is_array($details) && count($details) > 0) {
                $message .= ' (' . implode('; ', $details) . ')';
            }

            throw new RuntimeException(
                'Scheduler failed to generate a schedule: ' . $message
            );
        }

        return $result;
    }

    /**
     * Re-check a generated schedule against the current input:
     * POST /api/scheduler/validate-schedule.
     *
     * Returns the list of violations (empty when valid).
     */
    public function validateSchedule(array $input, array $schedule): array
    {
        $result = $this->post('/api/scheduler/validate-schedule', [
            'input' => $input,
            'schedule' => $schedule,
        ]);

        if (!isset($result['validation']['is_valid'])) {
            throw new RuntimeException(
                'Scheduler API returned no validation result.'
            );
        }

        return $result['validation']['errors'] ?? [];
    }

    private function post(string $path, array $payload): array
    {
        $baseUrl = config('services.scheduler.url');

        if (!is_string($baseUrl) || trim($baseUrl) === '') {
            throw new RuntimeException(
                'Scheduler API URL is not configured. Check config/services.php.'
            );
        }

        $url = rtrim($baseUrl, '/') . $path;

        try {
            $response = Http::acceptJson()
                ->asJson()
                ->timeout(120)
                ->post($url, $payload);
        } catch (ConnectionException $e) {
            throw new RuntimeException(
                'Could not connect to the C# Scheduler API at ' . $url,
                0,
                $e
            );
        }

        if ($response->failed()) {
            throw new RuntimeException(
                'Scheduler API returned HTTP ' . $response->status()
                . ': ' . $response->body()
            );
        }

        $result = $response->json();

        if (!is_array($result)) {
            throw new RuntimeException(
                'Scheduler API returned an empty or invalid JSON response.'
            );
        }

        if (!array_key_exists('success', $result)) {
            throw new RuntimeException(
                'Scheduler API response is missing the required "success" field.'
            );
        }

        return $result;
    }
}
