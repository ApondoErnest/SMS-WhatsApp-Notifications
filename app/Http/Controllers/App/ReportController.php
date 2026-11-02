<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Services\Reports\ReportService;
use App\Support\CurrentCenter;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(private ReportService $reports) {}

    public function index(Request $request): View
    {
        [$from, $to] = $this->dateRange($request);
        $centerId = app(CurrentCenter::class)->id();
        $overview = $this->reports->overview($centerId, $from, $to);

        return view('app.reports.index', [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'overview' => $overview,
        ]);
    }

    public function imports(Request $request): View
    {
        [$from, $to] = $this->dateRange($request);
        $centerId = app(CurrentCenter::class)->id();

        return view('app.reports.imports', [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'batches' => $this->reports->importBatches($centerId, $from, $to),
            'summary' => $this->reports->overview($centerId, $from, $to)['imports'],
        ]);
    }

    public function expiry(Request $request): View
    {
        [$from, $to] = $this->expiryRange($request);
        $centerId = app(CurrentCenter::class)->id();

        $records = $this->reports->expiringRecords($centerId, $from, $to);

        return view('app.reports.expiry', [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'window' => $request->string('window')->toString() ?: 'month',
            'records' => $records,
            'count' => $records->total(),
        ]);
    }

    public function notifications(Request $request): View
    {
        [$from, $to] = $this->dateRange($request);
        $centerId = app(CurrentCenter::class)->id();
        $channel = $this->nullableFilter($request->input('channel'), ['sms', 'whatsapp']);
        $status = $this->nullableFilter($request->input('status'), [
            'sent', 'delivered', 'read', 'failed', 'skipped', 'pending',
        ]);

        $overview = $this->reports->overview($centerId, $from, $to);

        return view('app.reports.notifications', [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'channel' => $channel,
            'status' => $status,
            'logs' => $this->reports->notificationLogs($centerId, $from, $to, $channel, $status),
            'sms' => $overview['sms'],
            'whatsapp' => $overview['whatsapp'],
            'failedNotifications' => $overview['failed_notifications'],
        ]);
    }

    public function exportExpiry(Request $request): StreamedResponse
    {
        [$from, $to] = $this->expiryRange($request);
        $centerId = app(CurrentCenter::class)->id();
        $records = $this->reports->expiringRecordsForExport($centerId, $from, $to);

        $filename = 'expiry-report-'.$from->toDateString().'_'.$to->toDateString().'.csv';

        return $this->streamCsv($filename, [
            'Licence plate',
            'Customer',
            'Phone',
            'Expiration date',
            'Status',
            'Inspection date',
        ], function ($out) use ($records) {
            foreach ($records as $record) {
                fputcsv($out, [
                    $record->licence_plate,
                    $record->customer_name,
                    $record->normalized_phone_number ?: $record->phone_number,
                    optional($record->expiration_date)?->format('Y-m-d'),
                    $record->status,
                    optional($record->inspection_date)?->format('Y-m-d'),
                ], ';');
            }
        });
    }

    public function exportNotifications(Request $request): StreamedResponse
    {
        [$from, $to] = $this->dateRange($request);
        $centerId = app(CurrentCenter::class)->id();
        $channel = $this->nullableFilter($request->input('channel'), ['sms', 'whatsapp']);
        $status = $this->nullableFilter($request->input('status'), [
            'sent', 'delivered', 'read', 'failed', 'skipped', 'pending',
        ]);

        $logs = $this->reports->notificationLogsForExport($centerId, $from, $to, $channel, $status);
        $filename = 'notifications-report-'.$from->toDateString().'_'.$to->toDateString().'.csv';

        return $this->streamCsv($filename, [
            'Date',
            'Channel',
            'Provider',
            'Phone',
            'Customer',
            'Licence plate',
            'Status',
            'Provider message ID',
            'Error',
        ], function ($out) use ($logs) {
            foreach ($logs as $log) {
                fputcsv($out, [
                    optional($log->created_at)?->format('Y-m-d H:i:s'),
                    $log->channel,
                    $log->provider,
                    $log->phone_number,
                    $log->inspectionRecord?->customer_name,
                    $log->inspectionRecord?->licence_plate,
                    $log->delivery_status,
                    $log->provider_message_id,
                    $log->error_message,
                ], ';');
            }
        });
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function dateRange(Request $request): array
    {
        $to = $this->parseDate($request->input('to')) ?? now();
        $from = $this->parseDate($request->input('from')) ?? $to->copy()->subDays(29);

        if ($from->gt($to)) {
            [$from, $to] = [$to->copy(), $from->copy()];
        }

        return [$from->startOfDay(), $to->endOfDay()];
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function expiryRange(Request $request): array
    {
        $window = $request->string('window')->toString();

        return match ($window) {
            'week' => [now()->startOfDay(), now()->addDays(7)->endOfDay()],
            'expired' => [
                Carbon::parse('2000-01-01')->startOfDay(),
                now()->subDay()->endOfDay(),
            ],
            'custom' => $this->dateRange($request),
            default => [now()->startOfDay(), now()->addDays(30)->endOfDay()],
        };
    }

    private function parseDate(mixed $value): ?Carbon
    {
        if (! filled($value)) {
            return null;
        }

        try {
            return Carbon::parse((string) $value);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param  list<string>  $allowed
     */
    private function nullableFilter(mixed $value, array $allowed): ?string
    {
        $value = is_string($value) ? $value : null;

        return in_array($value, $allowed, true) ? $value : null;
    }

    /**
     * @param  list<string>  $headers
     * @param  callable(resource): void  $writer
     */
    private function streamCsv(string $filename, array $headers, callable $writer): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $writer) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $headers, ';');
            $writer($out);
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
