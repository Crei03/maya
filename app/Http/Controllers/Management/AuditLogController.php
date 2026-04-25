<?php

declare(strict_types=1);

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuditLogController extends Controller
{
    public function index(Request $request): Response
    {
        $logs = AuditLog::query()
            ->when($request->user, fn ($q, $user) => $q->where('user_id', $user))
            ->when($request->action, fn ($q, $action) => $q->where('action', $action))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Management/AuditLogs/Index', [
            'logs' => $logs,
            'filters' => $request->only(['user', 'action']),
        ]);
    }
}
