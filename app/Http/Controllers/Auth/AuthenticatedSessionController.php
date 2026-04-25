<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = $request->user();

        // Super admin goes to SaaS dashboard
        if ($user->isManagement()) {
            $url = route('Management.dashboard', absolute: false);
            return redirect()->intended($url);
        }

        // Single-tenant mode or local development: stay on current domain
        if (! config('multi-tenant.enabled') || app()->environment('local', 'development')) {
            $url = route('admin.dashboard', absolute: false);
            return redirect()->intended($url);
        }

        // Production multi-tenant: redirect to tenant subdomain
        if ($user->tenant) {
            $tenantUrl = 'http://' . $user->tenant->slug . '.' . parse_url(config('app.url'), PHP_URL_HOST) . '/dashboard';
            return redirect()->intended($tenantUrl);
        }

        $url = route('admin.dashboard', absolute: false);
        return redirect()->intended($url);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
