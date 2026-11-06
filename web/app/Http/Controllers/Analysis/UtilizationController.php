<?php

namespace App\Http\Controllers\Analysis;

use App\Http\Controllers\Controller;
use App\Services\Analysis\UtilizationService;
use Illuminate\View\View;

/**
 * Utilization heatmaps: where the published routines have slack.
 */
class UtilizationController extends Controller
{
    public function index(UtilizationService $service): View
    {
        $data = $service->build();

        return view('analysis.utilization', [
            'data' => $data,
            'insights' => $this->insights($data),
        ]);
    }

    /**
     * Short findings about spare capacity, most useful first.
     */
    private function insights(array $data): array
    {
        $insights = [];
        $dayLabel = fn ($day) => ucfirst(strtolower((string) $day));

        $rooms = collect($data['heatmaps']['rooms'])->filter(fn ($row) => $row['week']['capacity'] > 0);

        if ($idle = $rooms->firstWhere('week.used', 0)) {
            $insights[] = ['icon' => 'bi-door-open', 'text' => "{$idle['label']} is never used. Check its lab eligibility, or plan to give it classes."];
        } elseif ($least = $rooms->sortBy('week.pct')->first()) {
            $insights[] = ['icon' => 'bi-door-open', 'text' => "{$least['label']} is the least used room at {$least['week']['pct']}% of its periods."];
        }

        $campus = $data['campus'];

        if ($campus['teaching_periods']) {
            $insights[] = ['icon' => 'bi-calendar-day', 'text' => "{$campus['idle_periods']} of {$campus['teaching_periods']} teaching periods have every room empty. "
                . $dayLabel($campus['quietest_day']) . ' is the quietest day and ' . $dayLabel($campus['busiest_day']) . ' the busiest.'];
        }

        foreach ($data['room_types'] as $type) {
            if ($type['more_labs']) {
                $insights[] = ['icon' => 'bi-pc-display', 'text' => "{$type['label']} rooms have {$type['more_labs']} free 3-period blocks, room for that many more lab sessions if the rooms are eligible for the course."];
            }
        }

        $workload = $data['workload'];

        if ($most = collect($workload['teachers'])->whereNotNull('spare')->sortByDesc('spare')->first()) {
            $insights[] = ['icon' => 'bi-person-check', 'text' => "{$most['name']} has the most room to take on classes: {$most['spare']} periods below their weekly cap of {$most['cap']}."];
        }

        if ($workload['over_cap']) {
            $insights[] = ['icon' => 'bi-exclamation-triangle', 'text' => "{$workload['over_cap']} teacher(s) teach more than their weekly cap. Move classes to the teachers with spare periods."];
        }

        return $insights;
    }
}
