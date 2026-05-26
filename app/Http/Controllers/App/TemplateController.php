<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\NotificationTemplate;
use Illuminate\View\View;

class TemplateController extends Controller
{
    public function index(): View
    {
        $centerId = auth()->user()->center_id;
        $templates = NotificationTemplate::where('center_id', $centerId)->get();

        return view('app.templates.index', compact('templates'));
    }
}
