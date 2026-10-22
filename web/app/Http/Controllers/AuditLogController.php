<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $entity = $request->query('entity');

        $logs = AuditLog::with('user')
            ->when($entity, fn ($query) => $query->where('entity_type', $entity))
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $entities = AuditLog::whereNotNull('entity_type')
            ->distinct()
            ->orderBy('entity_type')
            ->pluck('entity_type');

        return view('audit.index', compact('logs', 'entities', 'entity'));
    }
}
