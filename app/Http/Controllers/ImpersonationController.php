<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ImpersonationController extends Controller
{
    public function leave(Request $request): RedirectResponse
    {
        if ($request->user()?->isImpersonated()) {
            $request->user()?->leaveImpersonation();

            return to_route('admin.settings.users')
                ->with('status', 'Impersonation ended.');
        }

        return back();
    }
}
