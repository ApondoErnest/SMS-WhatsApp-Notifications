<?php

namespace App\Http\Middleware;

use App\Support\CurrentCenter;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCurrentCenter
{
    public function __construct(private CurrentCenter $currentCenter) {}

    public function handle(Request $request, Closure $next): Response
    {
        $center = $this->currentCenter->center();

        if (! $center) {
            if ($request->user()?->isSuperAdmin()) {
                return redirect()
                    ->route('centers.index')
                    ->with('error', __('Select a center to continue.'));
            }

            abort(403);
        }

        if ($center->status !== 'active' && ! $request->user()?->isSuperAdmin()) {
            abort(403);
        }

        return $next($request);
    }
}
