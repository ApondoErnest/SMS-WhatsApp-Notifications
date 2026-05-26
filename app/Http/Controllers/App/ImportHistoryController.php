<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\ImportBatch;
use Illuminate\View\View;

class ImportHistoryController extends Controller
{
    public function index(): View
    {
        return view('app.import-history.index');
    }

    public function show(ImportBatch $batch): View
    {
        $centerId = auth()->user()->center_id;

        abort_if($batch->center_id !== $centerId, 403);

        $records = $batch->inspectionRecords()->paginate(25, ['*'], 'records_page');
        $failedRows = $batch->failedRows()->get();

        return view('app.import-history.show', compact('batch', 'records', 'failedRows'));
    }
}
