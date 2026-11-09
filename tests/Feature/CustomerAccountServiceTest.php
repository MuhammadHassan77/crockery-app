<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\User;
use App\Services\CustomerAccountService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CustomerAccountServiceTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);
        $this->customer = Customer::create(['name' => 'Account Test']);
    }

    public function test_customer_receipt_pays_a_preferred_new_invoice_then_older_invoices(): void
    {
        $oldest = $this->invoice('INV-OLD-1', 2000, '2026-01-01');
        $next = $this->invoice('INV-OLD-2', 5000, '2026-01-02');
        $new = $this->invoice('INV-NEW', 1000, '2026-01-03');

        $receipt = app(CustomerAccountService::class)->receive($this->customer, [
            'amount' => 7000,
            'payment_method' => 'cash',
        ], $new);

        $this->assertSame(1000.0, (float) $new->fresh()->paid_amount);
        $this->assertSame(2000.0, (float) $oldest->fresh()->paid_amount);
        $this->assertSame(4000.0, (float) $next->fresh()->paid_amount);
        $this->assertSame('paid', $new->fresh()->status);
        $this->assertSame('partial', $next->fresh()->status);
        $this->assertSame(1000.0, (float) $this->customer->fresh()->account_balance);
        $this->assertCount(3, $receipt->allocations);
    }

    public function test_payment_without_open_invoices_is_stored_as_advance_and_applied_to_a_future_invoice(): void
    {
        $service = app(CustomerAccountService::class);
        $receipt = $service->receive($this->customer, [
            'amount' => 5000,
            'payment_method' => 'cash',
        ]);

        $this->assertSame(-5000.0, (float) $this->customer->fresh()->account_balance);
        $this->assertSame(0, $receipt->allocations->count());

        $futureInvoice = $this->invoice('INV-FUTURE', 3000, '2026-02-01');
        $service->reconcile($this->customer);

        $this->assertSame('paid', $futureInvoice->fresh()->status);
        $this->assertSame(-2000.0, (float) $this->customer->fresh()->account_balance);
        $this->assertSame(3000.0, (float) $futureInvoice->fresh()->paid_amount);
    }

    public function test_voiding_one_allocation_reduces_the_receipt_and_preserves_other_allocations(): void
    {
        $oldest = $this->invoice('INV-VOID-1', 2000, '2026-01-01');
        $next = $this->invoice('INV-VOID-2', 5000, '2026-01-02');
        $service = app(CustomerAccountService::class);
        $receipt = $service->receive($this->customer, [
            'amount' => 7000,
            'payment_method' => 'cash',
        ]);

        $result = $service->voidPayment($receipt->allocations->first()->payment);

        $this->assertEqualsCanonicalizing([$oldest->id, $next->id], $result['invoice_ids']);
        $this->assertSame(0.0, (float) $oldest->fresh()->paid_amount);
        $this->assertSame(5000.0, (float) $next->fresh()->paid_amount);
        $this->assertDatabaseHas('payment_history', [
            'id' => $receipt->id,
            'amount' => 5000,
            'deleted_at' => null,
        ]);
        $this->assertSame(1, $receipt->fresh()->allocations()->count());

        $service->reconcile($this->customer);

        $this->assertSame(0.0, (float) $oldest->fresh()->paid_amount);
        $this->assertSame(5000.0, (float) $next->fresh()->paid_amount);
        $this->assertSame(2000.0, (float) $this->customer->fresh()->account_balance);
        $this->assertSame(1, $receipt->fresh()->allocations()->count());
    }

    private function invoice(string $number, float $amount, string $date): Invoice
    {
        return Invoice::create([
            'invoice_number' => $number,
            'customer_id' => $this->customer->id,
            'subtotal' => $amount,
            'total_amount' => $amount,
            'paid_amount' => 0,
            'status' => 'pending',
            'invoice_date' => $date,
            'created_by' => $this->user->id,
            'share_token' => (string) Str::uuid(),
        ]);
    }
}