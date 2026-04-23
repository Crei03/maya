<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    /**
     * Render the admin settings page.
     */
    public function index(): Response
    {
        return Inertia::render('Admin/Configuracion');
    }

    public function clients(): Response
    {
        return Inertia::render('Admin/Configuracion/Client');
    }
}
