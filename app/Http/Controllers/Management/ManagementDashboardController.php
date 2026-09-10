<?php

declare(strict_types=1);

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Shipment;
use App\Models\Tenant;
use Carbon\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class ManagementDashboardController extends Controller
{
    public function index(): Response
    {
        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();

        // 1. Conteo de tenants
        $totalTenants = Tenant::count();
        $activeTenants = Tenant::where('status', 'active')->count();
        $pausedTenants = Tenant::where('status', 'paused')->count();
        $inactiveTenants = Tenant::whereNotIn('status', ['active', 'paused'])->count();

        // 2. Paquetes globales (sin filtros de tenant)
        $totalShipments = Shipment::withoutGlobalScopes()->count();
        $monthShipments = Shipment::withoutGlobalScopes()->where('created_at', '>=', $startOfMonth)->count();
        $monthDelivered = Shipment::withoutGlobalScopes()
            ->where('status', Shipment::STATUS_DELIVERED)
            ->where('delivered_at', '>=', $startOfMonth)
            ->count();

        // 3. Ingresos estimados (Revenue potencial de planes asignados)
        $potentialRevenue = (float) Tenant::where('status', 'active')
            ->whereNotNull('plan_id')
            ->with('plan')
            ->get()
            ->sum(fn (Tenant $t) => (float) ($t->plan?->price_monthly ?? 0));

        // 4. Gráfico Crecimiento Mensual (últimos 12 meses)
        $growthLabels = [];
        $growthData = [];
        for ($i = 11; $i >= 0; $i--) {
            $monthDate = $now->copy()->subMonths($i);
            $growthLabels[] = $monthDate->translatedFormat('M Y');
            $start = $monthDate->copy()->startOfMonth();
            $end = $monthDate->copy()->endOfMonth();
            $growthData[] = Tenant::whereBetween('created_at', [$start, $end])->count();
        }

        // 5. Gráfico Volumen Global de Envíos (últimos 6 meses)
        $shipmentVolumeLabels = [];
        $shipmentVolumeData = [];
        for ($i = 5; $i >= 0; $i--) {
            $monthDate = $now->copy()->subMonths($i);
            $shipmentVolumeLabels[] = $monthDate->translatedFormat('M Y');
            $start = $monthDate->copy()->startOfMonth();
            $end = $monthDate->copy()->endOfMonth();
            $shipmentVolumeData[] = Shipment::withoutGlobalScopes()
                ->whereBetween('created_at', [$start, $end])
                ->count();
        }

        // 6. Alertas: Tenants activos con baja actividad (0 paquetes creados en los últimos 30 días)
        $thirtyDaysAgo = $now->copy()->subDays(30);
        $lowActivityTenants = Tenant::where('status', 'active')
            ->whereDoesntHave('shipments', function ($q) use ($thirtyDaysAgo): void {
                $q->withoutGlobalScopes()->where('created_at', '>=', $thirtyDaysAgo);
            })
            ->select('id', 'name', 'slug', 'contact_email', 'created_at')
            ->take(5)
            ->get()
            ->map(fn (Tenant $t) => [
                'id' => $t->id,
                'name' => $t->name,
                'slug' => $t->slug,
                'contact_email' => $t->contact_email,
                'created_at' => $t->created_at?->format('d/m/Y'),
            ]);

        // 7. Paqueterías recientes con conteo de paquetes
        $recentTenants = Tenant::with('plan')
            ->withCount(['shipments' => fn ($q) => $q->withoutGlobalScopes()])
            ->latest()
            ->take(5)
            ->get()
            ->map(fn (Tenant $t) => [
                'id' => $t->id,
                'name' => $t->name,
                'slug' => $t->slug,
                'status' => $t->status,
                'plan_name' => $t->plan?->name ?? 'Plan Básico',
                'shipments_count' => $t->shipments_count,
                'created_at' => $t->created_at?->format('d/m/Y'),
            ]);

        // 8. Logs de auditoría recientes (globales)
        $recentAudits = [];
        if (\Illuminate\Support\Facades\Schema::hasTable('audit_logs')) {
            $recentAudits = AuditLog::withoutGlobalScopes()
                ->latest('created_at')
                ->take(8)
                ->get()
                ->map(fn (AuditLog $log) => [
                    'id' => $log->id,
                    'action' => $log->action,
                    'entity_type' => class_basename($log->entity_type ?? ''),
                    'ip_address' => $log->ip_address,
                    'created_at' => $log->created_at ? Carbon::parse($log->created_at)->diffForHumans() : 'Reciente',
                ])
                ->all();
        }

        $stats = [
            'total_tenants' => $totalTenants,
            'active_tenants' => $activeTenants,
            'paused_tenants' => $pausedTenants,
            'inactive_tenants' => $inactiveTenants,
            'total_shipments' => $totalShipments,
            'month_shipments' => $monthShipments,
            'month_delivered' => $monthDelivered,
            'potential_revenue' => $potentialRevenue,
            'recent_tenants' => $recentTenants,
            'low_activity_tenants' => $lowActivityTenants,
            'charts' => [
                'growth' => [
                    'labels' => $growthLabels,
                    'series' => $growthData,
                ],
                'shipments' => [
                    'labels' => $shipmentVolumeLabels,
                    'series' => $shipmentVolumeData,
                ],
                'status_distribution' => [
                    'labels' => ['Activas', 'Pausadas', 'Inactivas'],
                    'series' => [$activeTenants, $pausedTenants, $inactiveTenants],
                ],
            ],
        ];

        return Inertia::render('Management/Dashboard', [
            'stats' => $stats,
            'recentAudits' => $recentAudits,
        ]);
    }
}
