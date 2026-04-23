<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ColumnPreference;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ColumnPreferenceController extends Controller
{
    public function show(Request $request, string $module): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'No autenticado.',
            ], 401);
        }

        $preference = ColumnPreference::query()
            ->where('user_id', $user->id)
            ->where('module', $module)
            ->first();

        return response()->json([
            'success' => true,
            'data' => $preference?->visible_columns ?? null,
        ]);
    }

    public function update(Request $request, string $module): JsonResponse
    {
        $validated = $request->validate([
            'visible_columns' => ['required', 'array', 'min:1'],
            'visible_columns.*' => ['string', 'max:80'],
        ], [
            'visible_columns.required' => 'Selecciona al menos una columna.',
            'visible_columns.array' => 'La configuracion de columnas no es valida.',
            'visible_columns.min' => 'Selecciona al menos una columna.',
            'visible_columns.*.string' => 'Una columna seleccionada no es valida.',
            'visible_columns.*.max' => 'Una columna seleccionada no es valida.',
        ]);

        $user = $request->user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'No autenticado.',
            ], 401);
        }

        $preference = ColumnPreference::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'module' => $module,
            ],
            [
                'visible_columns' => array_values($validated['visible_columns']),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Configuracion de columnas guardada correctamente.',
            'data' => $preference->visible_columns,
        ]);
    }
}
