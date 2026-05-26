<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\InspectionRecord;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportRecordsController extends Controller
{
    public function __invoke(Request $request): StreamedResponse
    {
        $centerId = auth()->user()->center_id;

        $query = InspectionRecord::query()
            ->where('center_id', $centerId)
            ->when($request->input('status'), fn ($q, $v) => $q->where('status', $v))
            ->when($request->input('batch'), fn ($q, $v) => $q->where('import_batch_id', $v))
            ->when($request->input('expiry'), function ($q, $v) {
                match ($v) {
                    'this_week' => $q->whereBetween('expiration_date', [now(), now()->addDays(7)]),
                    'this_month' => $q->whereBetween('expiration_date', [now(), now()->addDays(30)]),
                    'expired' => $q->where('expiration_date', '<', now()),
                    default => $q,
                };
            })
            ->orderBy('expiration_date');

        $filename = 'inspection_records_' . now()->format('Y-m-d_His') . '.csv';

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'Registration date',
                'Inspection date',
                'Expiration date',
                'Cat.',
                'Type',
                'Licence plate',
                'Category',
                'Customer',
                'Phone number',
                'Normalized phone',
                'Status',
                'Import date',
            ], ';');

            $query->chunk(500, function ($records) use ($handle) {
                foreach ($records as $record) {
                    fputcsv($handle, [
                        $record->registration_date?->format('d/m/Y') ?? '',
                        $record->inspection_date?->format('d/m/Y') ?? '',
                        $record->expiration_date?->format('d/m/Y') ?? '',
                        $record->vehicle_class ?? '',
                        $record->inspection_type ?? '',
                        $record->licence_plate,
                        $record->vehicle_category ?? '',
                        $record->customer_name,
                        $record->phone_number,
                        $record->normalized_phone_number,
                        $record->status,
                        $record->created_at->format('d/m/Y H:i'),
                    ], ';');
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
