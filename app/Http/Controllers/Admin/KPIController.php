<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\KPIService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controlador para KPIs del panel de administración.
 */
class KPIController extends Controller
{
    /**
     * Constructor.
     */
    public function __construct(
        private readonly KPIService $kpiService
    ) {
    }

    /**
     * Muestra el dashboard de KPIs.
     *
     * GET /admin/dashboard
     */
    public function dashboard(Request $request): Response
    {
        $startDate = $request->input('start_date')
            ? Carbon::parse($request->input('start_date'))
            : Carbon::now()->subDays(30);

        $endDate = $request->input('end_date')
            ? Carbon::parse($request->input('end_date'))
            : Carbon::now();

        $kpis = $this->kpiService->getAllKPIs($startDate, $endDate);

        return Inertia::render('Admin/Dashboard', [
            'kpis' => $kpis,
            'filters' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
        ]);
    }

    /**
     * API para obtener KPIs en formato JSON.
     *
     * GET /api/admin/kpis
     */
    public function index(Request $request): JsonResponse
    {
        $startDate = $request->input('start_date')
            ? Carbon::parse($request->input('start_date'))
            : null;

        $endDate = $request->input('end_date')
            ? Carbon::parse($request->input('end_date'))
            : null;

        $kpis = $this->kpiService->getAllKPIs($startDate, $endDate);

        return response()->json([
            'success' => true,
            'data' => $kpis,
        ]);
    }

    /**
     * Obtiene solo el Delivery Rate.
     *
     * GET /api/admin/kpis/delivery-rate
     */
    public function deliveryRate(Request $request): JsonResponse
    {
        $startDate = $request->input('start_date')
            ? Carbon::parse($request->input('start_date'))
            : null;

        $endDate = $request->input('end_date')
            ? Carbon::parse($request->input('end_date'))
            : null;

        $data = $this->kpiService->getDeliveryRate($startDate, $endDate);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Obtiene solo el CSAT.
     *
     * GET /api/admin/kpis/satisfaction
     */
    public function satisfaction(Request $request): JsonResponse
    {
        $startDate = $request->input('start_date')
            ? Carbon::parse($request->input('start_date'))
            : null;

        $endDate = $request->input('end_date')
            ? Carbon::parse($request->input('end_date'))
            : null;

        $data = $this->kpiService->getSatisfactionScore($startDate, $endDate);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Obtiene el Delivery Rate por mensajero.
     *
     * GET /api/admin/kpis/by-messenger
     */
    public function byMessenger(Request $request): JsonResponse
    {
        $startDate = $request->input('start_date')
            ? Carbon::parse($request->input('start_date'))
            : null;

        $endDate = $request->input('end_date')
            ? Carbon::parse($request->input('end_date'))
            : null;

        $limit = $request->input('limit', 10);

        $data = $this->kpiService->getDeliveryRateByMessenger($limit, $startDate, $endDate);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }
}
