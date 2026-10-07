<?php

namespace App\Http\Controllers;

use App\Services\Scheduling\SchedulerInputService;
use Illuminate\Http\Request;

class SchedulerInputController extends Controller
{
    public function show(
        Request $request,
        SchedulerInputService $service
    ) {
        $batchId = (int) $request->query('batch_id');
        $academicTermId = (int) $request->query('academic_term_id');

        return response()->json(
            $service->build(
                $batchId,
                $academicTermId
            )
        );
    }
}