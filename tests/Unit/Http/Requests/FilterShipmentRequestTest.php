<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Requests;

use App\Http\Requests\Admin\FilterShipmentRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class FilterShipmentRequestTest extends TestCase
{
    public function test_all_fields_are_optional(): void
    {
        $request = new FilterShipmentRequest();

        // Empty input should pass validation — all fields are optional
        $validator = Validator::make([], $request->rules());

        $this->assertFalse($validator->fails());
    }

    public function test_validates_optional_filters_individually(): void
    {
        $request = new FilterShipmentRequest();

        $data = [
            'search'       => 'MAYA123',
            'status'       => 'pending',
            'warehouse_id' => 5,
            'driver_id'    => 12,
            'date_from'    => '2026-01-01',
            'date_to'      => '2026-12-31',
            'package_type' => 'caja',
            'per_page'     => 25,
        ];

        $validator = Validator::make($data, $request->rules());

        $this->assertFalse($validator->fails(), 'All valid filters should pass validation.');
    }

    public function test_date_to_must_be_after_or_equal_to_date_from(): void
    {
        $request = new FilterShipmentRequest();

        // date_to before date_from → should fail
        $validator = Validator::make([
            'date_from' => '2026-02-15',
            'date_to'   => '2026-01-01',
        ], $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('date_to', $validator->errors()->toArray());
    }

    public function test_date_to_equal_to_date_from_is_valid(): void
    {
        $request = new FilterShipmentRequest();

        $validator = Validator::make([
            'date_from' => '2026-05-16',
            'date_to'   => '2026-05-16',
        ], $request->rules());

        $this->assertFalse($validator->fails());
    }

    public function test_per_page_must_be_at_least_one(): void
    {
        $request = new FilterShipmentRequest();

        $validator = Validator::make([
            'per_page' => 0,
        ], $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('per_page', $validator->errors()->toArray());
    }

    public function test_per_page_must_not_exceed_one_hundred(): void
    {
        $request = new FilterShipmentRequest();

        $validator = Validator::make([
            'per_page' => 101,
        ], $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('per_page', $validator->errors()->toArray());
    }

    public function test_authorize_returns_true(): void
    {
        $request = new FilterShipmentRequest();

        $this->assertTrue($request->authorize());
    }
}
