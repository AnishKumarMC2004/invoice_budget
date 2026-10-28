<?php

namespace Database\Seeders;

use App\Models\Budget;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $admin = User::updateOrCreate([
            'email' => env('ADMIN_EMAIL', 'admin@example.com'),
        ], [
            'name' => env('ADMIN_NAME', 'Finora Administrator'),
            'password' => Hash::make(env('ADMIN_PASSWORD', 'password')),
            'is_admin' => true,
        ]);

        if (Invoice::exists()) {
            return;
        }

        $customer = Customer::create([
            'name' => 'Northstar Studio',
            'email' => 'accounts@northstar.example',
            'phone' => '+1 555 010 2040',
            'address' => '18 Market Street, Portland',
        ]);
        $supplier = Supplier::create([
            'name' => 'Paper & Co.',
            'email' => 'billing@paperco.example',
            'phone' => '+1 555 010 3090',
        ]);
        $products = [
            Product::create(['name' => 'Brand strategy', 'sku' => 'SRV-001', 'type' => 'service', 'unit_price' => 1800, 'tax_rate' => 8.5]),
            Product::create(['name' => 'Design retainer', 'sku' => 'SRV-002', 'type' => 'service', 'unit_price' => 2400, 'tax_rate' => 8.5]),
            Product::create(['name' => 'Office supplies', 'sku' => 'PRD-001', 'type' => 'product', 'unit_price' => 245, 'tax_rate' => 0]),
        ];
        $budget = Budget::create([
            'name' => 'Studio operations',
            'period' => 'monthly',
            'year' => now()->year,
            'month' => now()->month,
            'amount' => 5000,
            'category' => 'Operations',
        ]);
        Expense::create([
            'budget_id' => $budget->id,
            'description' => 'Workspace and supplies',
            'category' => 'Operations',
            'amount' => 860,
            'expense_date' => now()->startOfMonth()->addDays(3)->toDateString(),
            'vendor' => 'Paper & Co.',
        ]);

        $invoice = Invoice::create([
            'number' => 'INV-'.now()->format('ym').'-0001',
            'type' => 'sales',
            'customer_id' => $customer->id,
            'user_id' => $admin->id,
            'issue_date' => now()->startOfMonth()->addDays(2)->toDateString(),
            'due_date' => now()->addDays(14)->toDateString(),
            'currency' => 'USD',
            'subtotal' => 1800,
            'tax_total' => 153,
            'total' => 1953,
            'status' => 'pending',
        ]);
        $invoice->items()->create([
            'product_id' => $products[0]->id,
            'description' => $products[0]->name,
            'quantity' => 1,
            'unit_price' => 1800,
            'discount_rate' => 0,
            'tax_rate' => 8.5,
            'line_subtotal' => 1800,
            'discount_amount' => 0,
            'tax_amount' => 153,
            'line_total' => 1953,
        ]);

        $purchase = Invoice::create([
            'number' => 'PUR-'.now()->format('ym').'-0001',
            'type' => 'purchase',
            'supplier_id' => $supplier->id,
            'user_id' => $admin->id,
            'issue_date' => now()->startOfMonth()->addDay()->toDateString(),
            'due_date' => now()->addDays(6)->toDateString(),
            'currency' => 'USD',
            'subtotal' => 245,
            'total' => 245,
            'status' => 'pending',
        ]);
        $purchase->items()->create([
            'product_id' => $products[2]->id,
            'description' => $products[2]->name,
            'quantity' => 1,
            'unit_price' => 245,
            'discount_rate' => 0,
            'tax_rate' => 0,
            'line_subtotal' => 245,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'line_total' => 245,
        ]);
    }
}
