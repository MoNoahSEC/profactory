<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerDeposit;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Treasury;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InvoicePaymentAccountingTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Treasury $treasury;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'Admin', 'guard_name' => 'web']);
        $this->user = User::factory()->create();
        $this->user->assignRole('Admin');

        $this->treasury = Treasury::create([
            'name' => 'خزينة اختبار',
            'type' => 'cash',
            'initial_balance' => 0,
            'current_balance' => 0,
            'is_active' => true,
            'is_default' => true,
        ]);
    }

    private function createInvoice(float $total, float $paid = 0): Invoice
    {
        $customer = Customer::create([
            'name' => 'عميل اختبار',
            'type' => 'retail',
            'deposit_balance' => 500,
        ]);

        return Invoice::create([
            'invoice_number' => 'INV-TEST-' . uniqid(),
            'customer_id' => $customer->id,
            'invoice_date' => now()->toDateString(),
            'status' => $paid > 0 ? 'partial' : 'draft',
            'subtotal' => $total,
            'discount_type' => 'amount',
            'discount_value' => 0,
            'tax_rate' => 0,
            'tax_amount' => 0,
            'total_amount' => $total,
            'paid_amount' => $paid,
            'remaining_amount' => max(0, $total - $paid),
        ]);
    }

    public function test_cash_payment_updates_remaining_amount_and_treasury(): void
    {
        $invoice = $this->createInvoice(1000, 0);

        $response = $this->actingAs($this->user)->post(route('payments.store', $invoice), [
            'amount' => 300,
            'payment_date' => now()->toDateString(),
            'payment_method' => 'cash',
            'treasury_id' => $this->treasury->id,
        ]);

        $response->assertRedirect(route('invoices.show', $invoice));

        $invoice->refresh();
        $this->treasury->refresh();

        $this->assertEquals(300.0, (float) $invoice->paid_amount);
        $this->assertEquals(700.0, (float) $invoice->remaining_amount);
        $this->assertSame('partial', $invoice->status);
        $this->assertEquals(300.0, (float) $this->treasury->current_balance);
    }

    public function test_deleting_payment_restores_remaining_amount_and_treasury(): void
    {
        $invoice = $this->createInvoice(800, 200);

        $this->actingAs($this->user)->post(route('payments.store', $invoice), [
            'amount' => 200,
            'payment_date' => now()->toDateString(),
            'payment_method' => 'cash',
            'treasury_id' => $this->treasury->id,
        ]);

        $payment = Payment::where('invoice_id', $invoice->id)->latest('id')->first();
        $this->assertNotNull($payment);

        $this->actingAs($this->user)->delete(route('payments.destroy', $payment));

        $invoice->refresh();
        $this->treasury->refresh();

        $this->assertEquals(200.0, (float) $invoice->paid_amount);
        $this->assertEquals(600.0, (float) $invoice->remaining_amount);
        $this->assertEquals(0.0, (float) $this->treasury->current_balance);
    }

    public function test_deposit_payment_reduces_customer_deposit_and_updates_remaining(): void
    {
        $invoice = $this->createInvoice(500, 0);
        $customer = $invoice->customer;

        $this->actingAs($this->user)->post(route('payments.store', $invoice), [
            'amount' => 150,
            'payment_date' => now()->toDateString(),
            'payment_method' => 'deposit',
        ]);

        $invoice->refresh();
        $customer->refresh();

        $this->assertEquals(150.0, (float) $invoice->paid_amount);
        $this->assertEquals(350.0, (float) $invoice->remaining_amount);
        $this->assertEquals(350.0, (float) $customer->deposit_balance);
        $this->assertTrue(
            CustomerDeposit::where('customer_id', $customer->id)->where('type', 'applied')->exists()
        );
    }

    public function test_deleting_deposit_payment_restores_customer_deposit_balance(): void
    {
        $invoice = $this->createInvoice(400, 0);
        $customer = $invoice->customer;

        $this->actingAs($this->user)->post(route('payments.store', $invoice), [
            'amount' => 100,
            'payment_date' => now()->toDateString(),
            'payment_method' => 'deposit',
        ]);

        $payment = Payment::where('invoice_id', $invoice->id)->first();

        $this->actingAs($this->user)->delete(route('payments.destroy', $payment));

        $invoice->refresh();
        $customer->refresh();

        $this->assertEquals(0.0, (float) $invoice->paid_amount);
        $this->assertEquals(400.0, (float) $invoice->remaining_amount);
        $this->assertEquals(500.0, (float) $customer->deposit_balance);
    }
}
