<?php

namespace App\Services\Reports;

use App\Models\ImportBatch;
use App\Models\InspectionRecord;
use App\Models\NotificationLog;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReportService
{
    /**
     * @return array{
     *     from: Carbon,
     *     to: Carbon,
     *     imports: array{batches: int, imported_rows: int, duplicate_rows: int, failed_rows: int},
     *     expiry: array{this_week: int, this_month: int, in_range: int, expired: int},
     *     sms: array{sent: int, delivered: int, failed: int, skipped: int},
     *     whatsapp: array{sent: int, delivered: int, read: int, failed: int, skipped: int},
     *     failed_notifications: int,
     *     daily: list<array{date: string, imported_rows: int, sms_sent: int, whatsapp_sent: int, failed: int}>
     * }
     */
    public function overview(int $centerId, Carbon $from, Carbon $to): array
    {
        $from = $from->copy()->startOfDay();
        $to = $to->copy()->endOfDay();

        $importTotals = ImportBatch::query()
            ->where('center_id', $centerId)
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('COUNT(*) as batches')
            ->selectRaw('COALESCE(SUM(imported_rows), 0) as imported_rows')
            ->selectRaw('COALESCE(SUM(duplicate_rows), 0) as duplicate_rows')
            ->selectRaw('COALESCE(SUM(failed_rows), 0) as failed_rows')
            ->first();

        return [
            'from' => $from,
            'to' => $to,
            'imports' => [
                'batches' => (int) ($importTotals->batches ?? 0),
                'imported_rows' => (int) ($importTotals->imported_rows ?? 0),
                'duplicate_rows' => (int) ($importTotals->duplicate_rows ?? 0),
                'failed_rows' => (int) ($importTotals->failed_rows ?? 0),
            ],
            'expiry' => [
                'this_week' => $this->expiringCount($centerId, now(), now()->addDays(7)),
                'this_month' => $this->expiringCount($centerId, now(), now()->addDays(30)),
                'in_range' => $this->expiringCount($centerId, $from, $to),
                'expired' => InspectionRecord::query()
                    ->where('center_id', $centerId)
                    ->whereDate('expiration_date', '<', now()->toDateString())
                    ->count(),
            ],
            'sms' => $this->channelStats($centerId, 'sms', $from, $to),
            'whatsapp' => $this->channelStats($centerId, 'whatsapp', $from, $to),
            'failed_notifications' => NotificationLog::query()
                ->where('center_id', $centerId)
                ->where('delivery_status', 'failed')
                ->whereBetween('created_at', [$from, $to])
                ->count(),
            'daily' => $this->dailyActivity($centerId, $from, $to),
        ];
    }

    public function importBatches(int $centerId, Carbon $from, Carbon $to, int $perPage = 20): LengthAwarePaginator
    {
        return ImportBatch::query()
            ->where('center_id', $centerId)
            ->whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->with('uploader')
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function expiringRecords(
        int $centerId,
        Carbon $from,
        Carbon $to,
        int $perPage = 20,
    ): LengthAwarePaginator {
        return InspectionRecord::query()
            ->where('center_id', $centerId)
            ->whereBetween('expiration_date', [$from->toDateString(), $to->toDateString()])
            ->orderBy('expiration_date')
            ->orderBy('licence_plate')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function notificationLogs(
        int $centerId,
        Carbon $from,
        Carbon $to,
        ?string $channel = null,
        ?string $status = null,
        int $perPage = 20,
    ): LengthAwarePaginator {
        return $this->notificationQuery($centerId, $from, $to, $channel, $status)
            ->with('inspectionRecord')
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @return Collection<int, NotificationLog>
     */
    public function notificationLogsForExport(
        int $centerId,
        Carbon $from,
        Carbon $to,
        ?string $channel = null,
        ?string $status = null,
        int $limit = 5000,
    ): Collection {
        return $this->notificationQuery($centerId, $from, $to, $channel, $status)
            ->with('inspectionRecord')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * @return Collection<int, InspectionRecord>
     */
    public function expiringRecordsForExport(int $centerId, Carbon $from, Carbon $to, int $limit = 5000): Collection
    {
        return InspectionRecord::query()
            ->where('center_id', $centerId)
            ->whereBetween('expiration_date', [$from->toDateString(), $to->toDateString()])
            ->orderBy('expiration_date')
            ->limit($limit)
            ->get();
    }

    private function expiringCount(int $centerId, Carbon $from, Carbon $to): int
    {
        return InspectionRecord::query()
            ->where('center_id', $centerId)
            ->whereBetween('expiration_date', [$from->toDateString(), $to->toDateString()])
            ->count();
    }

    /**
     * @return array{sent: int, delivered: int, read: int, failed: int, skipped: int}
     */
    private function channelStats(int $centerId, string $channel, Carbon $from, Carbon $to): array
    {
        $rows = NotificationLog::query()
            ->where('center_id', $centerId)
            ->where('channel', $channel)
            ->whereBetween('created_at', [$from, $to])
            ->select('delivery_status', DB::raw('COUNT(*) as total'))
            ->groupBy('delivery_status')
            ->pluck('total', 'delivery_status');

        return [
            'sent' => (int) ($rows['sent'] ?? 0),
            'delivered' => (int) ($rows['delivered'] ?? 0),
            'read' => (int) ($rows['read'] ?? 0),
            'failed' => (int) ($rows['failed'] ?? 0),
            'skipped' => (int) ($rows['skipped'] ?? 0),
        ];
    }

    /**
     * @return list<array{date: string, imported_rows: int, sms_sent: int, whatsapp_sent: int, failed: int}>
     */
    private function dailyActivity(int $centerId, Carbon $from, Carbon $to): array
    {
        $imports = ImportBatch::query()
            ->where('center_id', $centerId)
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('DATE(created_at) as day')
            ->selectRaw('COALESCE(SUM(imported_rows), 0) as imported_rows')
            ->groupBy('day')
            ->pluck('imported_rows', 'day');

        $notifications = NotificationLog::query()
            ->where('center_id', $centerId)
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('DATE(created_at) as day')
            ->selectRaw("SUM(CASE WHEN channel = 'sms' AND delivery_status IN ('sent','delivered') THEN 1 ELSE 0 END) as sms_sent")
            ->selectRaw("SUM(CASE WHEN channel = 'whatsapp' AND delivery_status IN ('sent','delivered','read') THEN 1 ELSE 0 END) as whatsapp_sent")
            ->selectRaw("SUM(CASE WHEN delivery_status = 'failed' THEN 1 ELSE 0 END) as failed")
            ->groupBy('day')
            ->get()
            ->keyBy('day');

        $days = [];
        $cursor = $from->copy()->startOfDay();
        $end = $to->copy()->startOfDay();

        while ($cursor->lte($end)) {
            $key = $cursor->toDateString();
            $row = $notifications->get($key);

            $days[] = [
                'date' => $key,
                'imported_rows' => (int) ($imports[$key] ?? 0),
                'sms_sent' => (int) ($row->sms_sent ?? 0),
                'whatsapp_sent' => (int) ($row->whatsapp_sent ?? 0),
                'failed' => (int) ($row->failed ?? 0),
            ];

            $cursor->addDay();
        }

        return array_reverse($days);
    }

    private function notificationQuery(
        int $centerId,
        Carbon $from,
        Carbon $to,
        ?string $channel,
        ?string $status,
    ): Builder {
        return NotificationLog::query()
            ->where('center_id', $centerId)
            ->whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->when($channel, fn (Builder $q) => $q->where('channel', $channel))
            ->when($status, fn (Builder $q) => $q->where('delivery_status', $status));
    }
}
