<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\FailedImportRow;
use App\Models\ImportBatch;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportFailedRowsController extends Controller
{
    public function __invoke(ImportBatch $batch): StreamedResponse
    {
        $centerId = auth()->user()->center_id;
        abort_if($batch->center_id !== $centerId, 403);

        $filename = 'failed_rows_' . $batch->id . '_' . now()->format('Y-m-d_His') . '.csv';

        return response()->streamDownload(function () use ($batch) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'Row number',
                'Error',
                'Data',
            ], ';');

            $batch->failedRows()->chunk(500, function ($rows) use ($handle) {
                foreach ($rows as $row) {
                    fputcsv($handle, [
                        $row->row_number,
                        $row->error_message,
                        json_encode($row->row_data, JSON_UNESCAPED_UNICODE),
                    ], ';');
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
