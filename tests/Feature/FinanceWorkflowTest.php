<?php

namespace Tests\Feature;

use App\Models\Budget;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FinanceWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Customer $customer;

    private Supplier $supplier;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['is_admin' => true]);
        $this->customer = Customer::create(['name' => 'Acme Studio', 'email' => 'accounts@example.com']);
        $this->supplier = Supplier::create(['name' => 'Supply House']);
        $this->product = Product::create([
            'name' => 'Consulting',
            'type' => 'service',
            'unit_price' => 100,
            'tax_rate' => 8.5,
        ]);
    }

    public function test_admin_authentication_is_required_for_finance_pages(): void
    {
        $this->get('/dashboard')->assertRedirect(route('login'));
        $this->get(route('login'))->assertOk();

        $this->post(route('login.store'), [
            'email' => $this->admin->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $this->get('/dashboard')->assertOk();
    }

    public function test_non_admin_users_cannot_sign_in_to_the_admin_workspace(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->from(route('login'))->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('login'))->assertSessionHasErrors('email');
    }

    public function test_invoice_totals_are_calculated_on_the_server_and_payments_update_status(): void
    {
        $this->actingAs($this->admin)->post(route('invoices.store', 'sales'), [
            'customer_id' => $this->customer->id,
            'issue_date' => today()->toDateString(),
            'due_date' => today()->addDays(10)->toDateString(),
            'currency' => 'USD',
            'items' => [[
                'product_id' => $this->product->id,
                'description' => 'Consulting',
                'quantity' => 2,
                'unit_price' => 100,
                'discount_rate' => 10,
                'tax_rate' => 8.5,
            ]],
        ])->assertRedirect();

        $invoice = Invoice::with('items')->firstOrFail();
        $this->assertSame('200.00', $invoice->subtotal);
        $this->assertSame('20.00', $invoice->discount_total);
        $this->assertSame('15.30', $invoice->tax_total);
        $this->assertSame('195.30', $invoice->total);
        $this->assertSame('pending', $invoice->status);

        $this->actingAs($this->admin)->post(route('payments.store', $invoice), [
            'amount' => 50,
            'paid_at' => today()->toDateString(),
            'method' => 'Bank transfer',
        ])->assertSessionHasNoErrors();
        $this->assertSame('partial', $invoice->fresh()->status);
        $this->assertSame('50.00', $invoice->fresh()->amount_paid);

        $this->actingAs($this->admin)->from(route('invoices.show', $invoice))->post(route('payments.store', $invoice), [
            'amount' => 150,
            'paid_at' => today()->toDateString(),
            'method' => 'Card',
        ])->assertSessionHasErrors('amount');

        $this->actingAs($this->admin)->post(route('payments.store', $invoice), [
            'amount' => 145.30,
            'paid_at' => today()->toDateString(),
            'method' => 'Card',
        ])->assertSessionHasNoErrors();
        $this->assertSame('paid', $invoice->fresh()->status);
        $this->assertSame('195.30', $invoice->fresh()->amount_paid);
    }

    public function test_overdue_status_is_derived_from_the_due_date(): void
    {
        $invoice = Invoice::create([
            'number' => 'INV-OVERDUE-1',
            'type' => 'sales',
            'customer_id' => $this->customer->id,
            'user_id' => $this->admin->id,
            'issue_date' => today()->subDays(10),
            'due_date' => today()->subDay(),
            'currency' => 'USD',
            'total' => 100,
        ]);

        $invoice->refreshPaymentStatus();
        $this->assertSame('overdue', $invoice->fresh()->status);
        $this->assertSame('overdue', $invoice->fresh()->display_status);
    }

    public function test_reports_and_overdue_invoice_filter_render(): void
    {
        Invoice::create([
            'number' => 'INV-OVERDUE-2',
            'type' => 'sales',
            'customer_id' => $this->customer->id,
            'user_id' => $this->admin->id,
            'issue_date' => today()->subDays(20),
            'due_date' => today()->subDays(5),
            'currency' => 'USD',
            'subtotal' => 80,
            'total' => 80,
            'status' => 'pending',
        ]);

        $this->actingAs($this->admin)->get(route('invoices.index', ['type' => 'sales', 'status' => 'overdue']))
            ->assertOk()
            ->assertSee('INV-OVERDUE-2')
            ->assertSee('Overdue');
        $this->actingAs($this->admin)->get(route('reports.index', ['year' => now()->year]))
            ->assertOk()
            ->assertSee('Reports & analytics', false)
            ->assertSee('80.00');
    }

    public function test_expense_updates_budget_actual_and_rejects_mismatched_budget_period(): void
    {
        $budget = Budget::create([
            'name' => 'Current month',
            'period' => 'monthly',
            'year' => now()->year,
            'month' => now()->month,
            'amount' => 1000,
            'category' => 'Operations',
        ]);

        $this->actingAs($this->admin)->post(route('expenses.store'), [
            'budget_id' => $budget->id,
            'description' => 'Internet',
            'category' => 'Operations',
            'amount' => 125.50,
            'expense_date' => today()->toDateString(),
        ])->assertRedirect(route('expenses.index'));
        $this->assertSame(125.5, (float) $budget->expenses()->sum('amount'));

        $this->actingAs($this->admin)->from(route('expenses.create'))->post(route('expenses.store'), [
            'budget_id' => $budget->id,
            'description' => 'Out of period',
            'category' => 'Operations',
            'amount' => 50,
            'expense_date' => now()->subYear()->toDateString(),
        ])->assertSessionHasErrors('budget_id');
        $this->assertSame(1, Expense::count());
    }

    public function test_dashboard_renders_live_rates_and_api_failure_is_visible(): void
    {
        Http::fake([
            'api.frankfurter.app/latest*' => Http::sequence()
                ->push([
                    'date' => today()->toDateString(),
                    'base' => 'USD',
                    'rates' => ['EUR' => 0.92, 'GBP' => 0.79, 'INR' => 83.1],
                ])
                ->push([], 503),
        ]);

        $this->actingAs($this->admin)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Currency snapshot')
            ->assertSee('0.9200');

        Cache::forget('exchange-rates-usd');
        $this->actingAs($this->admin)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Live currency rates are temporarily unavailable.');
    }

    public function test_contact_crud_routes_use_their_default_contact_type(): void
    {
        $this->actingAs($this->admin)->get(route('customers.edit', $this->customer->id))->assertOk();

        $this->actingAs($this->admin)->put(route('customers.update', $this->customer->id), [
            'name' => 'Updated customer',
            'email' => 'new@example.com',
        ])->assertRedirect(route('customers.index'));

        $this->assertDatabaseHas('customers', ['id' => $this->customer->id, 'name' => 'Updated customer']);
    }

    public function test_management_screens_render_for_an_admin(): void
    {
        $this->actingAs($this->admin);

        $this->get(route('invoices.create', 'sales'))->assertOk()->assertSee('Line items');
        $this->get(route('invoices.index', 'purchase'))->assertOk()->assertSee('Purchase invoices');
        $this->get(route('budgets.index'))->assertOk()->assertSee('Budgets');
        $this->get(route('budgets.create'))->assertOk()->assertSee('Create a budget');
        $this->get(route('expenses.index'))->assertOk()->assertSee('Expenses');
        $this->get(route('expenses.create'))->assertOk()->assertSee('Record an expense');
        $this->get(route('customers.index'))->assertOk()->assertSee('Acme Studio');
        $this->get(route('suppliers.index'))->assertOk()->assertSee('Supply House');
        $this->get(route('products.index'))->assertOk()->assertSee('Consulting');
        $this->get(route('products.create'))->assertOk()->assertSee('Catalog details');
    }

    public function test_invoice_detail_displays_itemized_totals_and_payments(): void
    {
        $invoice = Invoice::create([
            'number' => 'INV-DETAIL-1',
            'type' => 'sales',
            'customer_id' => $this->customer->id,
            'user_id' => $this->admin->id,
            'issue_date' => today()->toDateString(),
            'currency' => 'USD',
            'subtotal' => 100,
            'tax_total' => 8.5,
            'total' => 108.5,
            'status' => 'pending',
        ]);
        $invoice->items()->create([
            'product_id' => $this->product->id,
            'description' => 'Consulting',
            'quantity' => 1,
            'unit_price' => 100,
            'discount_rate' => 0,
            'tax_rate' => 8.5,
            'line_subtotal' => 100,
            'discount_amount' => 0,
            'tax_amount' => 8.5,
            'line_total' => 108.5,
        ]);

        $this->actingAs($this->admin)->get(route('invoices.show', $invoice))
            ->assertOk()
            ->assertSee('INV-DETAIL-1')
            ->assertSee('Consulting')
            ->assertSee('108.50');
    }

    public function test_purchase_invoice_is_persisted_with_supplier_relationship(): void
    {
        $this->actingAs($this->admin)->post(route('invoices.store', 'purchase'), [
            'supplier_id' => $this->supplier->id,
            'issue_date' => today()->toDateString(),
            'currency' => 'usd',
            'items' => [[
                'product_id' => $this->product->id,
                'description' => 'Service purchase',
                'quantity' => 1,
                'unit_price' => 100,
                'discount_rate' => 0,
                'tax_rate' => 8.5,
            ]],
        ])->assertRedirect();

        $this->assertDatabaseHas('invoices', [
            'type' => 'purchase',
            'supplier_id' => $this->supplier->id,
            'currency' => 'USD',
            'total' => '108.50',
        ]);
    }

    public function test_invoice_currency_must_match_the_reporting_base_currency(): void
    {
        $this->actingAs($this->admin)->from(route('invoices.create', 'sales'))->post(route('invoices.store', 'sales'), [
            'customer_id' => $this->customer->id,
            'issue_date' => today()->toDateString(),
            'currency' => 'EUR',
            'items' => [[
                'description' => 'Consulting',
                'quantity' => 1,
                'unit_price' => 100,
                'discount_rate' => 0,
                'tax_rate' => 0,
            ]],
        ])->assertSessionHasErrors('currency');

        $this->assertSame(0, Invoice::count());
    }
}
