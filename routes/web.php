<?php

use App\Http\Controllers\App\DashboardController;
use App\Http\Controllers\App\ImportHistoryController;
use App\Http\Controllers\App\TemplateController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\LocaleController;
use App\Livewire\CsvImportUpload;
use Illuminate\Support\Facades\Route;

Route::get('/health', HealthController::class)->name('health');
Route::get('/locale/{locale}', LocaleController::class)->name('locale.switch');

Route::middleware('guest')->group(function (): void {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store']);
});

Route::middleware('auth')->group(function (): void {
    Route::post('logout', [LogoutController::class, 'destroy'])->name('logout');
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    // CSV Import
    Route::get('imports/create', fn () => view('app.imports.create'))->name('imports.create');

    // Import History
    Route::get('import-history', [ImportHistoryController::class, 'index'])->name('import-history.index');
    Route::get('import-history/{batch}', [ImportHistoryController::class, 'show'])->name('import-history.show');

    // Inspection Records
    Route::get('records', fn () => view('app.records.index'))->name('records.index');

    // Notification Schedules
    Route::get('schedules', fn () => view('app.schedules.index'))->name('schedules.index');

    // Notification Logs
    Route::get('notifications', fn () => view('app.notifications.index'))->name('notifications.index');

    // Templates
    Route::get('templates', [TemplateController::class, 'index'])->name('templates.index');
});

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});
