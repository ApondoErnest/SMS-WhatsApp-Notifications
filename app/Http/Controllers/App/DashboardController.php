<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\ImportBatch;
use App\Models\InspectionRecord;
use App\Models\NotificationLog;
use App\Models\NotificationSchedule;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $centerId = auth()->user()->center_id;

        $totalRecords = InspectionRecord::where('center_id', $centerId)->count();
        $importedToday = InspectionRecord::where('center_id', $centerId)
            ->whereDate('created_at', today())
            ->count();

        $totalDuplicates = ImportBatch::where('center_id', $centerId)->sum('duplicate_rows');
        $totalFailed = ImportBatch::where('center_id', $centerId)->sum('failed_rows');

        $expiringThisWeek = InspectionRecord::where('center_id', $centerId)
            ->whereBetween('expiration_date', [now(), now()->addDays(7)])
            ->count();
        $expiringThisMonth = InspectionRecord::where('center_id', $centerId)
            ->whereBetween('expiration_date', [now(), now()->addDays(30)])
            ->count();

        $smsSentToday = NotificationLog::where('center_id', $centerId)
            ->where('channel', 'sms')
            ->whereDate('created_at', today())
            ->whereIn('delivery_status', ['sent', 'delivered'])
            ->count();
        $whatsappSentToday = NotificationLog::where('center_id', $centerId)
            ->where('channel', 'whatsapp')
            ->whereDate('created_at', today())
            ->whereIn('delivery_status', ['sent', 'delivered'])
            ->count();
        $failedNotifications = NotificationLog::where('center_id', $centerId)
            ->where('delivery_status', 'failed')
            ->count();

        $pendingSchedules = NotificationSchedule::where('center_id', $centerId)
            ->where('status', 'pending')
            ->where('scheduled_date', '<=', now()->addDays(7))
            ->count();

        $recentBatches = ImportBatch::where('center_id', $centerId)
            ->with('uploader')
            ->orderByDesc('created_at')
            ->take(5)
            ->get();

        return view('app.dashboard', compact(
            'totalRecords',
            'importedToday',
            'totalDuplicates',
            'totalFailed',
            'expiringThisWeek',
            'expiringThisMonth',
            'smsSentToday',
            'whatsappSentToday',
            'failedNotifications',
            'pendingSchedules',
            'recentBatches',
        ));
    }
}
