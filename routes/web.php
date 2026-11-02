<?php

use App\Http\Controllers\App\CenterController;
use App\Http\Controllers\App\CenterSettingsController;
use App\Http\Controllers\App\DashboardController;
use App\Http\Controllers\App\ExportFailedRowsController;
use App\Http\Controllers\App\ExportRecordsController;
use App\Http\Controllers\App\ImportHistoryController;
use App\Http\Controllers\App\PasswordController;
use App\Http\Controllers\App\RecordController;
use App\Http\Controllers\App\ReportController;
use App\Http\Controllers\App\SmsBalanceController;
use App\Http\Controllers\App\TemplateController;
use App\Http\Controllers\App\UserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\Webhooks\MetaWhatsAppWebhookController;
use App\Http\Controllers\Webhooks\NexahSmsWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/health', HealthController::class)->name('health');
Route::get('/locale/{locale}', LocaleController::class)->name('locale.switch');

Route::get('webhooks/meta/whatsapp', [MetaWhatsAppWebhookController::class, 'verify'])
    ->name('webhooks.meta.whatsapp.verify');
Route::post('webhooks/meta/whatsapp', [MetaWhatsAppWebhookController::class, 'handle'])
    ->name('webhooks.meta.whatsapp');
Route::post('webhooks/nexah/sms', [NexahSmsWebhookController::class, 'handle'])
    ->name('webhooks.nexah.sms');

Route::middleware('guest')->group(function (): void {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store']);
});

Route::middleware('auth')->group(function (): void {
    Route::post('logout', [LogoutController::class, 'destroy'])->name('logout');

    Route::get('account/password', [PasswordController::class, 'edit'])->name('account.password.edit');
    Route::put('account/password', [PasswordController::class, 'update'])->name('account.password.update');

    Route::middleware('permission:manage-centers')->group(function (): void {
        Route::post('centers/switch', [CenterController::class, 'switch'])->name('centers.switch');
        Route::delete('centers', [CenterController::class, 'destroyMany'])->name('centers.destroy-many');
        Route::resource('centers', CenterController::class)->except(['show']);
        Route::get('sms-balance', SmsBalanceController::class)->name('sms.balance');
    });

    Route::middleware('tenant')->group(function (): void {
        Route::get('dashboard', DashboardController::class)->name('dashboard');

        Route::get('imports/create', fn () => view('app.imports.create'))->name('imports.create');

        Route::get('import-history', [ImportHistoryController::class, 'index'])->name('import-history.index');
        Route::get('import-history/{batch}', [ImportHistoryController::class, 'show'])->name('import-history.show');
        Route::get('import-history/{batch}/failed-export', ExportFailedRowsController::class)->name('import-history.failed-export');

        Route::middleware('permission:view-records')->group(function (): void {
            Route::get('records', fn () => view('app.records.index'))->name('records.index');
            Route::get('records/export', ExportRecordsController::class)->name('records.export');
            Route::get('records/{record}', [RecordController::class, 'show'])->name('records.show');
            Route::put('records/{record}/whatsapp-consent', [RecordController::class, 'updateWhatsAppConsent'])
                ->name('records.whatsapp-consent');
        });

        Route::get('schedules', fn () => view('app.schedules.index'))->name('schedules.index');
        Route::get('notifications', fn () => view('app.notifications.index'))->name('notifications.index');

        Route::middleware('permission:view-reports')->prefix('reports')->name('reports.')->group(function (): void {
            Route::get('/', [ReportController::class, 'index'])->name('index');
            Route::get('/imports', [ReportController::class, 'imports'])->name('imports');
            Route::get('/expiry', [ReportController::class, 'expiry'])->name('expiry');
            Route::get('/expiry/export', [ReportController::class, 'exportExpiry'])->name('expiry.export');
            Route::get('/notifications', [ReportController::class, 'notifications'])->name('notifications');
            Route::get('/notifications/export', [ReportController::class, 'exportNotifications'])->name('notifications.export');
        });

        Route::middleware('permission:manage-templates')->group(function (): void {
            Route::get('templates', [TemplateController::class, 'index'])->name('templates.index');
            Route::get('templates/create', [TemplateController::class, 'create'])->name('templates.create');
            Route::post('templates', [TemplateController::class, 'store'])->name('templates.store');
            Route::get('templates/{template}/edit', [TemplateController::class, 'edit'])->name('templates.edit');
            Route::put('templates/{template}', [TemplateController::class, 'update'])->name('templates.update');
            Route::delete('templates/{template}', [TemplateController::class, 'destroy'])->name('templates.destroy');
        });

        Route::middleware('permission:manage-users')->group(function (): void {
            Route::resource('users', UserController::class)->except(['show']);
        });

        Route::middleware('permission:manage-center')->group(function (): void {
            Route::get('center', [CenterSettingsController::class, 'edit'])->name('center.settings.edit');
            Route::put('center', [CenterSettingsController::class, 'update'])->name('center.settings.update');
            Route::put('center/reminders', [CenterSettingsController::class, 'updateReminders'])->name('center.reminders.update');
        });
    });
});

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});
