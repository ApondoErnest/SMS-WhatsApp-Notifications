<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    public function __invoke(Request $request, string $locale): RedirectResponse
    {
        if (! in_array($locale, ['fr', 'en'])) {
            abort(400);
        }

        session(['locale' => $locale]);

        return redirect()->back()->withCookie(cookie('locale', $locale, 60 * 24 * 365));
    }
}
