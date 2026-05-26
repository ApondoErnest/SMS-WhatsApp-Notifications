<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\InspectionRecord;
use Illuminate\View\View;

class RecordController extends Controller
{
    public function show(InspectionRecord $record): View
    {
        $centerId = auth()->user()->center_id;
        abort_if($record->center_id !== $centerId, 403);

        $record->load(['importBatch', 'notificationSchedules', 'notificationLogs']);

        return view('app.records.show', compact('record'));
    }
}
