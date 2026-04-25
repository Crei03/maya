<?php

declare(strict_types=1);

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ManagementDashboardController extends Controller
{
    public function index(): Response
    {
        $stats = [
            'total_tenants' => Tenant::count(),
            'active_tenants' => Tenant::where('status', 'active')->count(),
            'paused_tenants' => Tenant::where('status', 'paused')->count(),
            'recent_tenants' => Tenant::latest()->take(5)->get(),
        ];

        $recentAudits = AuditLog::latest()->take(10)->get();

        return Inertia::render('Management/Dashboard', [
            'stats' => $stats,
            'recentAudits' => $recentAudits,
        ]);
    }
}
