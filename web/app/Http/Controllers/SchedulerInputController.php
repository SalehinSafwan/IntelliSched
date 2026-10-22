<?php

namespace App\Http\Controllers;

use App\Services\Scheduling\SchedulerInputService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SchedulerInputController extends Controller
{
    public function show(
        Request $request,
        SchedulerInputService $service
    ) {
        // Without this, missing IDs reach findOrFail(0) and
        // surface as a misleading 404.
        $validator = Validator::make($request->query(), [
            'batch_id' => ['required', 'integer', 'exists:batches,id'],
            'academic_term_id' => ['required', 'integer', 'exists:academic_terms,id'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

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
