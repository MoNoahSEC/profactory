<?php

namespace Tests\Unit;

use App\Models\Product;
use App\Models\Worker;
use App\Services\WorkerWageCalculationService;
use Tests\TestCase;

class WorkerWageCalculationServiceTest extends TestCase
{
    private WorkerWageCalculationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new WorkerWageCalculationService();
    }

    public function test_piece_wage_system_uses_product_rate(): void
    {
        $worker = new Worker([
            'worker_type' => 'production',
            'production_role' => 'machinist',
            'wage_system' => 'piece',
            'shift_wage' => 280,
        ]);
        $product = new Product([
            'piece_wage' => 2.5,
            'labor_cost' => 1,
            'shift_target_quantity' => 110,
        ]);

        $result = $this->service->calculate($worker, $product, 110);

        $this->assertSame('piece_product', $result['wage_type']);
        $this->assertEquals(275.0, $result['total_pay']);
    }

    public function test_shift_wage_takes_priority_over_legacy_piece_price(): void
    {
        $worker = new Worker([
            'worker_type' => 'production',
            'production_role' => 'machinist',
            'piece_price' => 2.5,
            'shift_wage' => 280,
        ]);
        $product = new Product(['labor_cost' => 1, 'shift_target_quantity' => 110]);

        $result = $this->service->calculate($worker, $product, 110);

        $this->assertSame('shift', $result['wage_type']);
        $this->assertEquals(280.0, $result['total_pay']);
    }

    public function test_shift_wage_is_proportional_to_target(): void
    {
        $worker = new Worker([
            'worker_type' => 'production',
            'production_role' => 'machinist',
            'piece_price' => 0,
            'shift_wage' => 280,
        ]);
        $product = new Product(['labor_cost' => 0, 'shift_target_quantity' => 110]);

        $result = $this->service->calculate($worker, $product, 110);

        $this->assertSame('shift', $result['wage_type']);
        $this->assertEquals(280.0, $result['total_pay']);
    }

    public function test_shift_wage_partial_shift(): void
    {
        $worker = new Worker([
            'worker_type' => 'production',
            'production_role' => 'machinist',
            'piece_price' => 0,
            'shift_wage' => 280,
        ]);
        $product = new Product(['labor_cost' => 0, 'shift_target_quantity' => 110]);

        $result = $this->service->calculate($worker, $product, 55);

        $this->assertSame('shift', $result['wage_type']);
        $this->assertEquals(140.0, $result['total_pay']);
    }

    public function test_falls_back_to_product_rate(): void
    {
        $worker = new Worker([
            'worker_type' => 'production',
            'production_role' => 'scissors',
            'piece_price' => 0,
            'shift_wage' => 0,
        ]);
        $product = new Product(['scissors_cost' => 3, 'shift_target_quantity' => 110]);

        $result = $this->service->calculate($worker, $product, 10);

        $this->assertSame('product', $result['wage_type']);
        $this->assertEquals(30.0, $result['total_pay']);
    }
}
