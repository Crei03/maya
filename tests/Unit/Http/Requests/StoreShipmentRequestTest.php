<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Requests;

use App\Http\Requests\Admin\StoreShipmentRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StoreShipmentRequestTest extends TestCase
{
    public function test_required_fields(): void
    {
        $request = new StoreShipmentRequest();

        // Empty input — should fail on required fields
        $validator = Validator::make([], $request->rules());

        $this->assertTrue($validator->fails());
        $errors = $validator->errors()->toArray();
        $this->assertArrayHasKey('recipient_name', $errors);
        $this->assertArrayHasKey('destination_address', $errors);
        $this->assertArrayHasKey('package_type', $errors);
        $this->assertArrayHasKey('weight_kg', $errors);
    }

    public function test_valid_payload_passes_validation(): void
    {
        $request = new StoreShipmentRequest();

        $data = [
            'recipient_name'      => 'Juan Pérez',
            'destination_address' => 'Calle 50, Edificio Plaza, Panamá',
            'package_type'        => 'caja',
            'weight_kg'           => 5.5,
            'content_description' => 'Documentos',
            'weight_lb'           => 12.1,
            'dimensions'          => '30x20x15',
            'origin_address'      => 'Bodega Central',
            'destination_coords'  => '8.9833,-79.5167',
            'recipient_phone'     => '+507 6000-1234',
            'sender_id'           => null,
            'warehouse_id'        => null,
        ];

        $validator = Validator::make($data, $request->rules());

        $this->assertFalse($validator->fails());
    }

    public function test_weight_kg_must_be_numeric(): void
    {
        $request = new StoreShipmentRequest();

        $validator = Validator::make([
            'recipient_name'      => 'Juan Pérez',
            'destination_address' => 'Calle 50, Panamá',
            'package_type'        => 'caja',
            'weight_kg'           => 'not-a-number',
        ], $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('weight_kg', $validator->errors()->toArray());
    }

    public function test_weight_kg_cannot_be_negative(): void
    {
        $request = new StoreShipmentRequest();

        $validator = Validator::make([
            'recipient_name'      => 'Juan Pérez',
            'destination_address' => 'Calle 50, Panamá',
            'package_type'        => 'caja',
            'weight_kg'           => -1.0,
        ], $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('weight_kg', $validator->errors()->toArray());
    }

    public function test_recipient_name_max_length(): void
    {
        $request = new StoreShipmentRequest();

        $validator = Validator::make([
            'recipient_name'      => str_repeat('a', 256),
            'destination_address' => 'Calle 50, Panamá',
            'package_type'        => 'caja',
            'weight_kg'           => 1.0,
        ], $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('recipient_name', $validator->errors()->toArray());
    }

    public function test_destination_address_max_length(): void
    {
        $request = new StoreShipmentRequest();

        $validator = Validator::make([
            'recipient_name'      => 'Juan Pérez',
            'destination_address' => str_repeat('a', 501),
            'package_type'        => 'caja',
            'weight_kg'           => 1.0,
        ], $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('destination_address', $validator->errors()->toArray());
    }

    public function test_package_type_max_length(): void
    {
        $request = new StoreShipmentRequest();

        $validator = Validator::make([
            'recipient_name'      => 'Juan Pérez',
            'destination_address' => 'Calle 50, Panamá',
            'package_type'        => str_repeat('a', 101),
            'weight_kg'           => 1.0,
        ], $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('package_type', $validator->errors()->toArray());
    }

    public function test_optional_fields_are_valid_when_present(): void
    {
        $request = new StoreShipmentRequest();

        $data = [
            'recipient_name'      => 'Juan Pérez',
            'destination_address' => 'Calle 50, Panamá',
            'package_type'        => 'caja',
            'weight_kg'           => 5.5,
            'weight_lb'           => 12.1,
            'dimensions'          => '30x20x15',
            'origin_address'      => 'Bodega Central',
            'destination_coords'  => '8.9833,-79.5167',
            'recipient_phone'     => '+507 6000-1234',
            'content_description' => 'Documentos importantes',
        ];

        $validator = Validator::make($data, $request->rules());
        $this->assertFalse($validator->fails());
    }

    public function test_authorize_returns_true(): void
    {
        $request = new StoreShipmentRequest();

        $this->assertTrue($request->authorize());
    }
}
