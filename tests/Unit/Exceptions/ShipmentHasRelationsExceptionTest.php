<?php

declare(strict_types=1);

namespace Tests\Unit\Exceptions;

use App\Exceptions\ShipmentHasRelationsException;
use PHPUnit\Framework\TestCase;

class ShipmentHasRelationsExceptionTest extends TestCase
{
    public function test_constructor_stores_shipment_id_and_relation_type(): void
    {
        // Arrange & Act
        $exception = new ShipmentHasRelationsException(
            shipmentId: 'abc-123',
            relationType: 'trackingEvents'
        );

        // Assert: message format
        $this->assertSame(
            'No se puede eliminar el paquete #abc-123: tiene trackingEvents asociados',
            $exception->getMessage()
        );
    }

    public function test_exception_extends_runtime_exception(): void
    {
        $exception = new ShipmentHasRelationsException(
            shipmentId: 'xyz-456',
            relationType: 'manifestItems'
        );

        $this->assertInstanceOf(\RuntimeException::class, $exception);
    }

    public function test_different_relation_types_produce_different_messages(): void
    {
        $exception = new ShipmentHasRelationsException(
            shipmentId: 'def-789',
            relationType: 'shipmentTaskItems'
        );

        $this->assertSame(
            'No se puede eliminar el paquete #def-789: tiene shipmentTaskItems asociados',
            $exception->getMessage()
        );
    }
}
