<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Requests;

use App\Http\Requests\Admin\UpdateShipmentRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class UpdateShipmentRequestTest extends TestCase
{
    public function test_all_fields_are_optional_via_sometimes_rule(): void
    {
        $request = new UpdateShipmentRequest();

        // Empty input completely — should pass because all fields use "sometimes"
        $validator = Validator::make([], $request->rules());

        $this->assertFalse($validator->fails(), 'Empty payload should pass (partial PATCH).');
    }

    public function test_partial_update_with_single_field_is_valid(): void
    {
        $request = new UpdateShipmentRequest();

        // Only sending one field — should pass because of "sometimes" rule
        $validator = Validator::make([
            'status' => 'in_transit',
        ], $request->rules());

        $this->assertFalse($validator->fails());
    }

    public function test_partial_update_with_multiple_fields_is_valid(): void
    {
        $request = new UpdateShipmentRequest();

        $validator = Validator::make([
            'recipient_name'      => 'María García',
            'destination_address' => 'Avenida Central 123, Panamá',
            'weight_kg'           => 3.5,
        ], $request->rules());

        $this->assertFalse($validator->fails());
    }

    public function test_all_fields_present_validates_correctly(): void
    {
        $request = new UpdateShipmentRequest();

        $data = [
            'recipient_name'      => 'Juan Pérez',
            'destination_address'  => 'Calle 50, Edificio Plaza, Panamá',
            'package_type'         => 'caja',
            'weight_kg'            => 5.5,
            'content_description'  => 'Documentos',
            'weight_lb'            => 12.1,
            'dimensions'           => '30x20x15',
            'origin_address'       => 'Bodega Central',
            'destination_coords'   => '8.9833,-79.5167',
            'recipient_phone'      => '+507 6000-1234',
            'sender_id'            => null,
            'warehouse_id'         => null,
        ];

        $validator = Validator::make($data, $request->rules());

        $this->assertFalse($validator->fails());
    }

    public function test_weight_kg_must_be_numeric_when_provided(): void
    {
        $request = new UpdateShipmentRequest();

        $validator = Validator::make([
            'weight_kg' => 'not-a-number',
        ], $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('weight_kg', $validator->errors()->toArray());
    }

    public function test_weight_kg_cannot_be_negative_when_provided(): void
    {
        $request = new UpdateShipmentRequest();

        $validator = Validator::make([
            'weight_kg' => -1.0,
        ], $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('weight_kg', $validator->errors()->toArray());
    }

    public function test_weight_lb_must_be_numeric_when_provided(): void
    {
        $request = new UpdateShipmentRequest();

        $validator = Validator::make([
            'weight_lb' => 'abc',
        ], $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('weight_lb', $validator->errors()->toArray());
    }

    public function test_max_length_constraints_apply_when_fields_provided(): void
    {
        $request = new UpdateShipmentRequest();

        $validator = Validator::make([
            'recipient_name' => str_repeat('a', 256),
        ], $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('recipient_name', $validator->errors()->toArray());
    }

    public function test_unknown_fields_are_ignored(): void
    {
        $request = new UpdateShipmentRequest();

        // Send an unknown field — it should be ignored (no validation error)
        $validator = Validator::make([
            'non_existent_field' => 'some value',
            'recipient_name'     => 'Valid Name',
        ], $request->rules());

        $this->assertFalse($validator->fails());
    }

    public function test_authorize_returns_true(): void
    {
        $request = new UpdateShipmentRequest();

        $this->assertTrue($request->authorize());
    }
}
