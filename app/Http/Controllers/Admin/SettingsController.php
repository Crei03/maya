<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Configuracion');
    }

    public function clients(): Response
    {
        return Inertia::render('Admin/Configuracion/Client');
    }

    public function users(): Response
    {
        return Inertia::render('Admin/Configuracion/Users');
    }

    public function drivers(): Response
    {
        return Inertia::render('Admin/Configuracion/Conductores');
    }

    public function catalogos(): Response
    {
        return Inertia::render('Admin/Configuracion/Catalogos');
    }
}
