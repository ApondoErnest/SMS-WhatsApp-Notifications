<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Support\CurrentCenter;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $centerId = app(CurrentCenter::class)->id();
        $action = $request->string('action')->toString();

        $logs = AuditLog::query()
            ->with('user')
            ->where(function ($q) use ($centerId) {
                $q->where('center_id', $centerId)->orWhereNull('center_id');
            })
            ->when($action !== '', fn ($q) => $q->where('action', $action))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(30)
            ->withQueryString();

        $actions = AuditLog::query()
            ->where(function ($q) use ($centerId) {
                $q->where('center_id', $centerId)->orWhereNull('center_id');
            })
            ->distinct()
            ->orderBy('action')
            ->pluck('action');

        return view('app.audit-logs.index', compact('logs', 'actions', 'action'));
    }
}
