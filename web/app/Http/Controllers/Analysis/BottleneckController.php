<?php

namespace App\Http\Controllers\Analysis;

use App\Http\Controllers\Controller;
use App\Services\Analysis\BottleneckPropagationService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * "What if this resource fails?" for the published routines.
 */
class BottleneckController extends Controller
{
    public function index(Request $request, BottleneckPropagationService $service): View
    {
        $options = $service->options();
        $result = null;
        $scan = null;

        if ($request->filled('type')) {
            $data = $request->validate([
                'type' => ['required', Rule::in(['room', 'teacher', 'slot', 'day'])],
                'room' => ['nullable', 'required_if:type,room', Rule::in(array_column($options['rooms'], 'id'))],
                'teacher' => ['nullable', 'required_if:type,teacher', Rule::in(array_column($options['teachers'], 'id'))],
                'slot' => ['nullable', 'required_if:type,slot', Rule::in(array_column($options['slots'], 'id'))],
                'closed_day' => ['nullable', 'required_if:type,day', Rule::in($options['days'])],
                'on_day' => ['nullable', Rule::in(config('intellisched.days'))],
            ], [
                'required_if' => 'Choose which resource fails.',
            ]);

            $failure = match ($data['type']) {
                'room' => ['type' => 'room', 'id' => (int) $data['room'], 'day' => $data['on_day'] ?? null],
                'teacher' => ['type' => 'teacher', 'id' => (int) $data['teacher'], 'day' => $data['on_day'] ?? null],
                'slot' => ['type' => 'slot', 'id' => (int) $data['slot'], 'day' => null],
                'day' => ['type' => 'day', 'id' => $data['closed_day'], 'day' => null],
            };

            $result = $service->analyze($failure);
        }

        if ($request->boolean('scan')) {
            $scan = $service->scan();
        }

        return view('analysis.bottleneck', compact('options', 'result', 'scan'));
    }

    /**
     * Query string that re-opens one failure, used by the scan table.
     */
    public static function queryFor(array $failure): array
    {
        return match ($failure['type']) {
            'room' => ['type' => 'room', 'room' => $failure['id'], 'on_day' => $failure['day']],
            'teacher' => ['type' => 'teacher', 'teacher' => $failure['id'], 'on_day' => $failure['day']],
            'slot' => ['type' => 'slot', 'slot' => $failure['id']],
            'day' => ['type' => 'day', 'closed_day' => $failure['id']],
        };
    }
}
