<?php

namespace App\Providers;

use App\Models\InspectionCenter;
use App\Support\CurrentCenter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(CurrentCenter::class);
    }

    public function boot(): void
    {
        View::composer('layouts.app', function ($view): void {
            $user = auth()->user();
            $current = app(CurrentCenter::class)->center();

            $view->with([
                'currentCenter' => $current,
                'availableCenters' => $user?->isSuperAdmin()
                    ? InspectionCenter::query()->orderBy('name')->get()
                    : collect(),
            ]);
        });
    }
}
