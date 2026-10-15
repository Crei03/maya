<?php

declare(strict_types=1);

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Models\DeliveryProof;
use App\Models\Shipment;
use App\Models\ShipmentTask;
use App\Models\ShipmentTaskItem;
use App\Services\CatalogoService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class DriverDashboardController extends Controller
{
    public function __construct(
        protected CatalogoService $catalogoService
    ) {}

    /**
     * Mobile driver welcome view with KPI cards and assigned delivery plans.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        // Load delivery plans assigned to this driver
        $tasks = ShipmentTask::query()
            ->where('driver_id', $user->id)
            ->with(['status', 'vehicle', 'warehouse', 'items.status'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Calculate KPIs across driver's tasks
        $allItems = $tasks->flatMap->items;
        $totalDeliveries = $allItems->count();
        $completedDeliveries = $allItems->filter(fn ($item) => in_array($item->status?->codigo, ['ENTREGADO', 'COMPLETADA']))->count();
        $pendingDeliveries = $allItems->filter(fn ($item) => in_array($item->status?->codigo, ['PENDIENTE', 'EN_CURSO', 'PAUSA']))->count();
        $returnedDeliveries = $allItems->filter(fn ($item) => in_array($item->status?->codigo, ['DEVUELTO', 'RETORNADO']))->count();

        $completionRate = $totalDeliveries > 0 ? (int) round(($completedDeliveries / $totalDeliveries) * 100) : 0;

        $mappedPlans = $tasks->map(function (ShipmentTask $task) {
            $totalStops = $task->items->count();
            $completedStops = $task->items->filter(fn ($i) => in_array($i->status?->codigo, ['ENTREGADO', 'COMPLETADA']))->count();
            $pendingStops = $task->items->filter(fn ($i) => in_array($i->status?->codigo, ['PENDIENTE', 'EN_CURSO', 'PAUSA']))->count();
            $progress = $totalStops > 0 ? (int) round(($completedStops / $totalStops) * 100) : 0;

            return [
                'id' => $task->id,
                'title' => $task->title,
                'scheduled_date' => $task->scheduled_date ? Carbon::parse($task->scheduled_date)->format('d/m/Y') : null,
                'started_at' => $task->started_at ? Carbon::parse($task->started_at)->format('H:i') : null,
                'notes' => $task->notes,
                'status' => [
                    'codigo' => $task->status?->codigo ?? 'PENDIENTE',
                    'valor' => $task->status?->valor ?? 'Pendiente',
                    'badge' => $task->status?->metadata['badge'] ?? 'bg-amber-50 text-amber-700 border border-amber-200',
                ],
                'vehicle' => $task->vehicle ? [
                    'plate' => $task->vehicle->license_plate ?? $task->vehicle->plate_number ?? $task->vehicle->plate,
                    'model' => $task->vehicle->model ?? 'Vehículo',
                ] : null,
                'warehouse' => $task->warehouse ? [
                    'name' => $task->warehouse->name,
                ] : null,
                'total_stops' => $totalStops,
                'completed_stops' => $completedStops,
                'pending_stops' => $pendingStops,
                'progress' => $progress,
            ];
        });

        return Inertia::render('Driver/Dashboard', [
            'driver' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ],
            'kpis' => [
                'total_deliveries' => $totalDeliveries,
                'completed_deliveries' => $completedDeliveries,
                'pending_deliveries' => $pendingDeliveries,
                'returned_deliveries' => $returnedDeliveries,
                'completion_rate' => $completionRate,
                'active_plans_count' => $tasks->filter(fn ($t) => ! in_array($t->status?->codigo, ['COMPLETADA', 'CANCELADA']))->count(),
            ],
            'plans' => $mappedPlans,
        ]);
    }

    /**
     * View delivery plan task stops with cards, map triggers, pastel badges, and bottom sheet.
     */
    public function tasks(Request $request, string $taskId): Response
    {
        $user = $request->user();

        $task = ShipmentTask::query()
            ->where('driver_id', $user->id)
            ->with([
                'status',
                'vehicle',
                'warehouse',
                'items' => fn ($q) => $q->orderBy('stop_order')->orderBy('created_at'),
                'items.status',
                'items.priority',
                'items.shipment.packageType',
            ])
            ->findOrFail($taskId);

        $mappedItems = $task->items->map(function (ShipmentTaskItem $item) {
            $shipment = $item->shipment;

            // Resolve pastel badge
            $statusCodigo = strtoupper($item->status?->codigo ?? 'PENDIENTE');
            $statusValor = $item->status?->valor ?? 'Pendiente';

            $badgeClasses = match ($statusCodigo) {
                'PENDIENTE' => 'bg-amber-50 text-amber-700 border border-amber-200 ring-amber-100',
                'EN_CURSO' => 'bg-sky-50 text-sky-700 border border-sky-200 ring-sky-100',
                'DEVUELTO', 'RETORNADO' => 'bg-rose-50 text-rose-700 border border-rose-200 ring-rose-100',
                'PAUSA' => 'bg-purple-50 text-purple-700 border border-purple-200 ring-purple-100',
                'ENTREGADO', 'COMPLETADA' => 'bg-emerald-50 text-emerald-700 border border-emerald-200 ring-emerald-100',
                default => 'bg-slate-50 text-slate-700 border border-slate-200 ring-slate-100',
            };

            return [
                'id' => $item->id,
                'shipment_id' => $item->shipment_id,
                'stop_order' => $item->stop_order ?? 1,
                'status' => [
                    'codigo' => $statusCodigo,
                    'valor' => $statusValor,
                    'badge' => $badgeClasses,
                ],
                'priority' => [
                    'codigo' => $item->priority?->codigo ?? 'MEDIA',
                    'valor' => $item->priority?->valor ?? 'Media',
                ],
                'recipient_name' => $shipment?->recipient_name ?? 'Cliente Destinatario',
                'recipient_phone' => $shipment?->recipient_phone ?? '',
                'destination_address' => $shipment?->destination_address ?? 'Sin dirección registrada',
                'destination_coords' => $shipment?->destination_coords,
                'tracking_number' => $shipment?->tracking_number ?? 'S/N',
                'package_type' => $shipment?->packageType?->valor ?? 'Paquete estándar',
                'weight_kg' => $shipment?->weight_kg ? (float) $shipment->weight_kg : null,
                'content_description' => $shipment?->content_description,
                'notes' => $item->notes,
                'delivered_at' => $item->delivered_at ? Carbon::parse($item->delivered_at)->format('H:i d/m/Y') : null,
                'return_reason' => $item->return_reason,
            ];
        });

        $totalStops = $mappedItems->count();
        $completedStops = $mappedItems->filter(fn ($i) => in_array($i['status']['codigo'], ['ENTREGADO', 'COMPLETADA']))->count();

        return Inertia::render('Driver/Tasks', [
            'task' => [
                'id' => $task->id,
                'title' => $task->title,
                'scheduled_date' => $task->scheduled_date ? Carbon::parse($task->scheduled_date)->format('d/m/Y') : null,
                'notes' => $task->notes,
                'status' => [
                    'codigo' => $task->status?->codigo ?? 'PENDIENTE',
                    'valor' => $task->status?->valor ?? 'Pendiente',
                ],
                'vehicle' => $task->vehicle ? [
                    'plate' => $task->vehicle->license_plate ?? $task->vehicle->plate_number ?? $task->vehicle->plate,
                ] : null,
                'warehouse' => $task->warehouse ? [
                    'name' => $task->warehouse->name,
                ] : null,
                'total_stops' => $totalStops,
                'completed_stops' => $completedStops,
            ],
            'items' => $mappedItems,
        ]);
    }

    /**
     * Update task stop status (PAUSAR, EN_CURSO, DEVUELTO, etc.).
     */
    public function updateStatus(Request $request, string $itemId): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'status' => 'required|string|in:PENDIENTE,EN_CURSO,PAUSA,DEVUELTO,RETORNADO',
            'reason' => 'nullable|string|max:500',
        ]);

        $item = ShipmentTaskItem::query()
            ->where('id', $itemId)
            ->whereHas('task', fn ($q) => $q->where('driver_id', $user->id))
            ->with('task')
            ->firstOrFail();

        $statusId = $this->catalogoService->getValorIdByCodigo('estado-item-tarea', $validated['status']);

        DB::transaction(function () use ($item, $statusId, $validated) {
            $updates = [];
            if ($statusId) {
                $updates['status_id'] = $statusId;
            }

            if (in_array($validated['status'], ['DEVUELTO', 'RETORNADO'])) {
                $updates['return_reason'] = $validated['reason'] ?? 'Devuelto por conductor';
            }

            $item->update($updates);

            // If task was pending and driver started an item, mark task in progress
            if ($validated['status'] === 'EN_CURSO' && $item->task->status?->codigo === 'PENDIENTE') {
                $taskProgressStatusId = $this->catalogoService->getValorIdByCodigo('estado-tarea', 'EN_PROCESO');
                if ($taskProgressStatusId) {
                    $item->task->update([
                        'status_id' => $taskProgressStatusId,
                        'started_at' => $item->task->started_at ?? now(),
                    ]);
                }
            }
        });

        return back()->with('success', 'Estado de la entrega actualizado.');
    }

    /**
     * Add notes or driver comments to a task stop.
     */
    public function addComment(Request $request, string $itemId): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'comment' => 'required|string|max:1000',
        ]);

        $item = ShipmentTaskItem::query()
            ->where('id', $itemId)
            ->whereHas('task', fn ($q) => $q->where('driver_id', $user->id))
            ->firstOrFail();

        $timestamp = now()->format('H:i d/m');
        $newNote = "[{$timestamp}]: {$validated['comment']}";
        $existingNotes = $item->notes ? $item->notes."\n" : '';

        $item->update([
            'notes' => $existingNotes.$newNote,
        ]);

        return back()->with('success', 'Comentario registrado exitosamente.');
    }

    /**
     * View for completing delivery: details, photos capture (1-5), and signature canvas.
     */
    public function completeView(Request $request, string $itemId): Response
    {
        $user = $request->user();

        $item = ShipmentTaskItem::query()
            ->where('id', $itemId)
            ->whereHas('task', fn ($q) => $q->where('driver_id', $user->id))
            ->with(['task', 'shipment.packageType'])
            ->firstOrFail();

        $shipment = $item->shipment;

        return Inertia::render('Driver/CompleteDelivery', [
            'item' => [
                'id' => $item->id,
                'shipment_task_id' => $item->shipment_task_id,
                'recipient_name' => $shipment?->recipient_name ?? 'Cliente Destinatario',
                'recipient_phone' => $shipment?->recipient_phone ?? '',
                'destination_address' => $shipment?->destination_address ?? 'Sin dirección',
                'tracking_number' => $shipment?->tracking_number ?? 'S/N',
                'package_type' => $shipment?->packageType?->valor ?? 'Paquete estándar',
                'weight_kg' => $shipment?->weight_kg ? (float) $shipment->weight_kg : null,
                'content_description' => $shipment?->content_description,
            ],
            'task' => [
                'id' => $item->task->id,
                'title' => $item->task->title,
            ],
        ]);
    }

    /**
     * Store delivery completion with signature named after client and 1-5 photos.
     */
    public function storeComplete(Request $request, string $itemId): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'signed_by' => 'required|string|max:255',
            'signature' => 'required|string', // Base64 Data URL from canvas
            'photos' => 'required|array|min:1|max:5', // Array of Base64 or uploaded files
            'photos.*' => 'required|string',
            'notes' => 'nullable|string|max:500',
            'coords' => 'nullable|array',
        ]);

        $item = ShipmentTaskItem::query()
            ->where('id', $itemId)
            ->whereHas('task', fn ($q) => $q->where('driver_id', $user->id))
            ->with(['task', 'shipment'])
            ->firstOrFail();

        $clientName = trim($validated['signed_by']);
        $clientSlug = Str::slug($clientName, '_');

        // 1. Process and save signature canvas image with client's full name
        $signatureBase64 = $validated['signature'];
        if (preg_match('/^data:image\/(\w+);base64,/', $signatureBase64, $type)) {
            $signatureData = substr($signatureBase64, strpos($signatureBase64, ',') + 1);
            $signatureDecoded = base64_decode($signatureData);
        } else {
            $signatureDecoded = base64_decode($signatureBase64);
        }

        $sigFilename = 'firma_'.$clientSlug.'_'.time().'_'.Str::random(4).'.png';
        Storage::disk('public')->put('signatures/'.$sigFilename, $signatureDecoded);
        $signatureUrl = '/storage/signatures/'.$sigFilename;

        // 2. Process and save photos (1 to 5)
        $savedPhotoUrls = [];
        foreach ($validated['photos'] as $idx => $photoBase64) {
            $ext = 'jpg';
            if (preg_match('/^data:image\/(\w+);base64,/', $photoBase64, $photoType)) {
                $ext = strtolower($photoType[1]) === 'png' ? 'png' : 'jpg';
                $photoData = substr($photoBase64, strpos($photoBase64, ',') + 1);
                $photoDecoded = base64_decode($photoData);
            } else {
                $photoDecoded = base64_decode($photoBase64);
            }

            $photoFilename = 'entrega_'.$item->id.'_'.($idx + 1).'_'.time().'.'.$ext;
            Storage::disk('public')->put('delivery_proofs/'.$photoFilename, $photoDecoded);
            $savedPhotoUrls[] = '/storage/delivery_proofs/'.$photoFilename;
        }

        DB::transaction(function () use ($item, $validated, $clientName, $signatureUrl, $savedPhotoUrls) {
            // Find ENTREGADO status for items
            $entregadoStatusId = $this->catalogoService->getValorIdByCodigo('estado-item-tarea', 'ENTREGADO');

            // Create delivery proof
            DeliveryProof::create([
                'tenant_id' => $item->tenant_id,
                'shipment_id' => $item->shipment_id,
                'signature_url' => $signatureUrl,
                'photo_url' => $savedPhotoUrls[0] ?? null,
                'photos' => $savedPhotoUrls,
                'signed_by' => $clientName,
                'gps_coords' => $validated['coords'] ?? null,
            ]);

            // Update item
            $itemNotes = $item->notes;
            if (! empty($validated['notes'])) {
                $timestamp = now()->format('H:i d/m');
                $itemNotes = ($itemNotes ? $itemNotes."\n" : '')."[{$timestamp} Entrega]: {$validated['notes']}";
            }

            $item->update([
                'status_id' => $entregadoStatusId ?? $item->status_id,
                'delivered_at' => now(),
                'notes' => $itemNotes,
            ]);

            // Update shipment
            if ($item->shipment) {
                $item->shipment->update([
                    'status_id' => $entregadoStatusId ?? $item->shipment->status_id,
                    'delivered_at' => now(),
                    'delivered_photo_url' => $savedPhotoUrls[0] ?? null,
                    'recipient_signature_url' => $signatureUrl,
                    'recipient_name' => $item->shipment->recipient_name ?: $clientName,
                ]);
            }

            // Check if all items in plan are completed
            $remaining = ShipmentTaskItem::where('shipment_task_id', $item->shipment_task_id)
                ->where('id', '!=', $item->id)
                ->where(function ($q) {
                    $q->whereNull('delivered_at')
                        ->whereHas('status', fn ($sq) => $sq->whereNotIn('codigo', ['ENTREGADO', 'DEVUELTO', 'RETORNADO']));
                })
                ->exists();

            if (! $remaining) {
                $taskCompletedStatusId = $this->catalogoService->getValorIdByCodigo('estado-tarea', 'COMPLETADA');
                if ($taskCompletedStatusId) {
                    $item->task->update([
                        'status_id' => $taskCompletedStatusId,
                        'end_date' => now(),
                    ]);
                }
            }
        });

        return redirect()->route('driver.tasks', ['taskId' => $item->shipment_task_id])
            ->with('success', "¡Entrega completada con éxito para {$clientName}!");
    }
}
