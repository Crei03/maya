<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

class ShipmentHasRelationsException extends RuntimeException
{
    /**
     * Create a new exception instance.
     *
     * @param string $shipmentId  The UUID of the shipment
     * @param string $relationType The relation type blocking deletion
     */
    public function __construct(string $shipmentId, string $relationType)
    {
        parent::__construct(
            sprintf('Cannot delete shipment #%s: has associated %s', $shipmentId, $relationType)
        );
    }
}
