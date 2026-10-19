<?php

namespace App\Services\Scheduling;

use Illuminate\Support\Facades\Http;

class SchedulerApiService
{
    public function generate(array $input): array
    {
        $url = config('services.scheduler.url');

        $response = Http::timeout(120)
            ->post($url . '/api/scheduler/generate', $input);

        

        if ($response->failed()) {
    dd([
        'status' => $response->status(),
        'response' => $response->json(),
        'sent_input' => $input,
    ]);
}
        $response->throw();
        return $response->json();
    }
}