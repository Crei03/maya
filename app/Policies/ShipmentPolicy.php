<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Shipment;
use App\Models\User;

class ShipmentPolicy
{
    /**
     * Determine if the user can view any shipments.
     */
    public function viewAny(User $user): bool
    {
        return $user->isGestor();
    }

    /**
     * Determine if the user can view a specific shipment.
     */
    public function view(User $user, Shipment $shipment): bool
    {
        return $user->isGestor() && $user->tenant_id === $shipment->tenant_id;
    }

    /**
     * Determine if the user can create shipments.
     */
    public function create(User $user): bool
    {
        return $user->isGestor();
    }

    /**
     * Determine if the user can update a shipment.
     */
    public function update(User $user, Shipment $shipment): bool
    {
        return $user->isGestor() && $user->tenant_id === $shipment->tenant_id;
    }

    /**
     * Determine if the user can delete a shipment.
     */
    public function delete(User $user, Shipment $shipment): bool
    {
        return $user->isGestor() && $user->tenant_id === $shipment->tenant_id;
    }
}
