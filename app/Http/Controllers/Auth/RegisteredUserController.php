<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse|SymfonyResponse
    {
        if (! $request->filled('slug') && $request->filled('company_name')) {
            $request->merge(['slug' => Str::slug((string) $request->input('company_name'))]);
        }

        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:120'],
            'slug' => ['required', 'string', 'max:60', 'regex:/^[a-z0-9-]+$/', 'unique:tenants,slug'],
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class.',email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ], [
            'company_name.required' => 'El nombre de la empresa es obligatorio.',
            'slug.required' => 'El subdominio es obligatorio.',
            'slug.regex' => 'El subdominio solo puede contener letras minúsculas, números y guiones.',
            'slug.unique' => 'Este subdominio ya está registrado para otra empresa.',
            'name.required' => 'El nombre del administrador es obligatorio.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Ingresa un correo electrónico válido.',
            'email.unique' => 'Este correo ya se encuentra registrado.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.confirmed' => 'La confirmación de la contraseña no coincide.',
        ]);

        [$tenant, $user] = DB::transaction(function () use ($validated): array {
            $tenant = Tenant::create([
                'name' => trim($validated['company_name']),
                'slug' => Str::slug($validated['slug']),
                'contact_email' => $validated['email'],
                'status' => 'active',
            ]);

            $user = User::create([
                'tenant_id' => $tenant->id,
                'name' => trim($validated['name']),
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => User::ROLE_GESTOR,
                'status' => true,
            ]);

            return [$tenant, $user];
        });

        event(new Registered($user));

        Auth::login($user);

        if (method_exists($tenant, 'makeCurrent')) {
            $tenant->makeCurrent();
        }

        // Subdomain redirect in production multi-tenant mode
        if (config('multi-tenant.enabled') && ! app()->environment('local', 'development', 'testing')) {
            $scheme = parse_url((string) config('app.url'), PHP_URL_SCHEME) ?: 'https';
            $host = parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'maya.app';
            $tenantUrl = "{$scheme}://{$tenant->slug}.{$host}/dashboard";

            return Inertia::location($tenantUrl);
        }

        return redirect()->route('admin.dashboard');
    }
}
