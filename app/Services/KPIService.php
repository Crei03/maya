<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ManifestItem;
use App\Models\ServiceRating;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Servicio para calcular KPIs de Calidad y CX.
 *
 * Métricas implementadas:
 * - Delivery Rate (tasa de entregas exitosas)
 * - CSAT (Customer Satisfaction Score)
 * - Por mensajero
 */
class KPIService
{
    /**
     * Obtiene el Delivery Rate global.
     *
     * Calcula el porcentaje de entregas exitosas vs. totales
     * usando la tabla manifest_items (is_delivered).
     *
     * @param Carbon|null $startDate Fecha inicial opcional
     * @param Carbon|null $endDate Fecha final opcional
     * @return array<string, mixed>
     */
    public function getDeliveryRate(?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $query = ManifestItem::query();

        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        $total = $query->count();
        $successful = $query->clone()->delivered()->count();
        $failed = $query->clone()->failed()->count();

        $rate = $total > 0 ? round(($successful / $total) * 100, 2) : 0;

        return [
            'metric' => 'delivery_rate',
            'label' => 'Tasa de Entregas Exitosas',
            'value' => $rate,
            'unit' => '%',
            'total' => $total,
            'successful' => $successful,
            'failed' => $failed,
            'trend' => null, // TODO: Calcular tendencia vs período anterior
        ];
    }

    /**
     * Obtiene el CSAT (Customer Satisfaction Score) global.
     *
     * Calcula el promedio de calificaciones 1-5.
     *
     * @param Carbon|null $startDate Fecha inicial opcional
     * @param Carbon|null $endDate Fecha final opcional
     * @return array<string, mixed>
     */
    public function getSatisfactionScore(?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $query = ServiceRating::query();

        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        $total = $query->count();
        $average = $query->clone()->avg('rating') ?? 0;

        // Distribución de calificaciones
        $distribution = $query->clone()
            ->select('rating', DB::raw('COUNT(*) as count'))
            ->groupBy('rating')
            ->orderBy('rating')
            ->pluck('count', 'rating')
            ->toArray();

        // Asegurar que existan todas las calificaciones 1-5
        for ($i = 1; $i <= 5; $i++) {
            if (!isset($distribution[$i])) {
                $distribution[$i] = 0;
            }
        }
        ksort($distribution);

        return [
            'metric' => 'csat',
            'label' => 'Satisfacción del Cliente',
            'value' => round($average, 2),
            'unit' => '/5',
            'total_ratings' => $total,
            'distribution' => $distribution,
            'trend' => null, // TODO: Calcular tendencia
        ];
    }

    /**
     * Obtiene el Delivery Rate agrupado por mensajero.
     *
     * @param int|null $limit Cantidad de mensajeros a retornar
     * @param Carbon|null $startDate Fecha inicial opcional
     * @param Carbon|null $endDate Fecha final opcional
     * @return Collection<int, array>
     */
    public function getDeliveryRateByMessenger(
        ?int $limit = null,
        ?Carbon $startDate = null,
        ?Carbon $endDate = null
    ): Collection {
        $query = DB::table('manifests')
            ->join('manifest_items', 'manifests.id', '=', 'manifest_items.manifest_id')
            ->join('users', 'manifests.messenger_id', '=', 'users.id')
            ->where('users.role', User::ROLE_MESSENGER)
            ->select(
                'users.id as messenger_id',
                'users.name as messenger_name',
                DB::raw('COUNT(*) as total_deliveries'),
                DB::raw('SUM(CASE WHEN manifest_items.is_delivered = 1 THEN 1 ELSE 0 END) as successful'),
                DB::raw('SUM(CASE WHEN manifest_items.is_delivered = 0 THEN 1 ELSE 0 END) as failed')
            )
            ->groupBy('users.id', 'users.name');

        if ($startDate && $endDate) {
            $query->whereBetween('manifest_items.created_at', [$startDate, $endDate]);
        }

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get()
            ->map(function ($row) {
                $rate = $row->total_deliveries > 0
                    ? round(($row->successful / $row->total_deliveries) * 100, 2)
                    : 0;

                return [
                    'messenger_id' => $row->messenger_id,
                    'messenger_name' => $row->messenger_name,
                    'total' => (int) $row->total_deliveries,
                    'successful' => (int) $row->successful,
                    'failed' => (int) $row->failed,
                    'rate' => $rate,
                ];
            })
            ->sortByDesc('rate')
            ->values();
    }

    /**
     * Obtiene estadísticas generales del dashboard.
     *
     * @return array<string, mixed>
     */
    public function getDashboardStats(): array
    {
        $today = Carbon::today();
        $startOfMonth = Carbon::now()->startOfMonth();

        return [
            'total_shipments' => DB::table('shipments')->count(),
            'shipments_today' => DB::table('shipments')
                ->whereDate('created_at', $today)
                ->count(),
            'active_messengers' => User::messengers()
                ->whereHas('manifests', function ($q) use ($startOfMonth) {
                    $q->where('created_at', '>=', $startOfMonth);
                })
                ->count(),
            'open_incidents' => DB::table('incidents')
                ->where('resolved', 0)
                ->count(),
        ];
    }

    /**
     * Obtiene todos los KPIs para el dashboard.
     *
     * @param Carbon|null $startDate
     * @param Carbon|null $endDate
     * @return array<string, mixed>
     */
    public function getAllKPIs(?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        if (!$startDate) {
            $startDate = Carbon::now()->subDays(30);
        }
        if (!$endDate) {
            $endDate = Carbon::now();
        }

        return [
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
            'delivery_rate' => $this->getDeliveryRate($startDate, $endDate),
            'satisfaction' => $this->getSatisfactionScore($startDate, $endDate),
            'by_messenger' => $this->getDeliveryRateByMessenger(10, $startDate, $endDate),
            'stats' => $this->getDashboardStats(),
        ];
    }
}
