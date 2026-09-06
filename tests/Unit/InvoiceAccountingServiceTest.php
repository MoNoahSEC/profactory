<?php

namespace Tests\Unit;

use App\Models\Invoice;
use App\Services\InvoiceAccountingService;
use Tests\TestCase;

class InvoiceAccountingServiceTest extends TestCase
{
    private InvoiceAccountingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new InvoiceAccountingService();
    }

    public function test_sync_sets_remaining_and_partial_status(): void
    {
        $invoice = new Invoice([
            'total_amount' => 1000,
            'paid_amount' => 400,
            'remaining_amount' => 0,
            'status' => 'draft',
        ]);

        $result = $this->service->syncFromPaidAmount($invoice);

        $this->assertSame(600.0, $result['remaining_amount']);
        $this->assertSame('partial', $result['status']);
    }

    public function test_sync_marks_invoice_paid_when_fully_collected(): void
    {
        $invoice = new Invoice([
            'total_amount' => 500,
            'paid_amount' => 500,
            'remaining_amount' => 100,
            'status' => 'partial',
        ]);

        $result = $this->service->syncFromPaidAmount($invoice);

        $this->assertSame(0.0, $result['remaining_amount']);
        $this->assertSame('paid', $result['status']);
    }

    public function test_sync_never_returns_negative_remaining(): void
    {
        $invoice = new Invoice([
            'total_amount' => 300,
            'paid_amount' => 350,
            'remaining_amount' => 50,
            'status' => 'partial',
        ]);

        $result = $this->service->syncFromPaidAmount($invoice);

        $this->assertSame(0.0, $result['remaining_amount']);
        $this->assertSame('paid', $result['status']);
    }
}
