<?php

namespace App\Services\Audit;

use App\Models\AuditLog;
use App\Support\CurrentCenter;
use Illuminate\Http\Request;

class AuditLogService
{
    public function record(
        string $action,
        ?string $description = null,
        ?int $centerId = null,
        ?int $userId = null,
        ?string $ipAddress = null,
    ): AuditLog {
        $request = request();

        if ($centerId === null && app()->bound(CurrentCenter::class)) {
            $centerId = app(CurrentCenter::class)->center()?->id;
        }

        return AuditLog::create([
            'center_id' => $centerId,
            'user_id' => $userId ?? auth()->id(),
            'action' => $action,
            'description' => $description,
            'ip_address' => $ipAddress ?? ($request instanceof Request ? $request->ip() : null),
        ]);
    }
}
