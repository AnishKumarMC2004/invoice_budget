<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function index(string $type): View
    {
        $this->ensureType($type);
        $invoices = Invoice::with(['customer', 'supplier'])
            ->where('type', $type)
            ->when(request('search'), function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('number', 'like', "%{$search}%")
                        ->orWhereHas('customer', fn ($query) => $query->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('supplier', fn ($query) => $query->where('name', 'like', "%{$search}%"));
                });
            })
            ->when(request('status'), function ($query, string $status): void {
                if ($status === 'overdue') {
                    $query->where('status', '!=', 'paid')->whereDate('due_date', '<', today());
                } elseif ($status === 'pending') {
                    $query->where('status', 'pending')->where(function ($query): void {
                        $query->whereNull('due_date')->orWhereDate('due_date', '>=', today());
                    });
                } elseif ($status === 'partial') {
                    $query->where('status', 'partial')->where(function ($query): void {
                        $query->whereNull('due_date')->orWhereDate('due_date', '>=', today());
                    });
                } else {
                    $query->where('status', $status);
                }
            })
            ->latest('issue_date')
            ->paginate(12)
            ->withQueryString();

        return view('invoices.index', compact('invoices', 'type'));
    }

    public function create(string $type): View
    {
        $this->ensureType($type);

        return view('invoices.form', $this->formData($type));
    }

    public function store(Request $request, string $type): RedirectResponse
    {
        $this->ensureType($type);
        $data = $this->validatedInvoice($request, $type);

        $invoice = DB::transaction(function () use ($data, $type, $request): Invoice {
            $invoice = Invoice::create([
                'number' => strtoupper($type === 'sales' ? 'INV' : 'PUR').'-'.now()->format('ymd').'-'.strtoupper(str()->random(5)),
                'type' => $type,
                'customer_id' => $type === 'sales' ? $data['customer_id'] : null,
                'supplier_id' => $type === 'purchase' ? $data['supplier_id'] : null,
                'user_id' => $request->user()->id,
                'issue_date' => $data['issue_date'],
                'due_date' => $data['due_date'] ?? null,
                'currency' => strtoupper($data['currency']),
                'notes' => $data['notes'] ?? null,
            ]);

            $this->syncItems($invoice, $data['items']);
            $invoice->refreshPaymentStatus();

            return $invoice;
        });

        return redirect()->route('invoices.show', $invoice)->with('success', 'Invoice created.');
    }

    public function show(Invoice $invoice): View
    {
        $invoice->load(['customer', 'supplier', 'items', 'payments']);

        return view('invoices.show', compact('invoice'));
    }

    public function edit(Invoice $invoice): View
    {
        return view('invoices.form', $this->formData($invoice->type) + ['invoice' => $invoice->load('items')]);
    }

    public function update(Request $request, Invoice $invoice): RedirectResponse
    {
        $data = $this->validatedInvoice($request, $invoice->type);

        DB::transaction(function () use ($invoice, $data): void {
            $invoice->update([
                'customer_id' => $invoice->type === 'sales' ? $data['customer_id'] : null,
                'supplier_id' => $invoice->type === 'purchase' ? $data['supplier_id'] : null,
                'issue_date' => $data['issue_date'],
                'due_date' => $data['due_date'] ?? null,
                'currency' => strtoupper($data['currency']),
                'notes' => $data['notes'] ?? null,
            ]);
            $this->syncItems($invoice, $data['items']);
            $invoice->refreshPaymentStatus();
        });

        return redirect()->route('invoices.show', $invoice)->with('success', 'Invoice updated.');
    }

    public function destroy(Invoice $invoice): RedirectResponse
    {
        $type = $invoice->type;
        $invoice->delete();

        return redirect()->route('invoices.index', $type)->with('success', 'Invoice deleted.');
    }

    private function formData(string $type): array
    {
        return [
            'type' => $type,
            'parties' => $type === 'sales' ? Customer::orderBy('name')->get() : Supplier::orderBy('name')->get(),
            'products' => Product::orderBy('name')->get(),
        ];
    }

    private function validatedInvoice(Request $request, string $type): array
    {
        $request->merge(['currency' => strtoupper((string) $request->input('currency'))]);

        return $request->validate([
            'customer_id' => [$type === 'sales' ? 'required' : 'nullable', 'exists:customers,id'],
            'supplier_id' => [$type === 'purchase' ? 'required' : 'nullable', 'exists:suppliers,id'],
            'issue_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:issue_date'],
            'currency' => ['required', 'string', 'in:USD'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.product_id' => ['nullable', 'exists:products,id'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0', 'max:1000000'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0', 'max:9999999999'],
            'items.*.discount_rate' => ['required', 'numeric', 'between:0,100'],
            'items.*.tax_rate' => ['required', 'numeric', 'between:0,100'],
        ]);
    }

    private function syncItems(Invoice $invoice, array $items): void
    {
        $invoice->items()->delete();
        $subtotal = 0;
        $discountTotal = 0;
        $taxTotal = 0;
        $total = 0;

        foreach ($items as $item) {
            $lineSubtotal = round((float) $item['quantity'] * (float) $item['unit_price'], 2);
            $discount = round($lineSubtotal * (float) $item['discount_rate'] / 100, 2);
            $tax = round(($lineSubtotal - $discount) * (float) $item['tax_rate'] / 100, 2);
            $lineTotal = round($lineSubtotal - $discount + $tax, 2);
            $maximum = 9999999999.99;
            if (max($lineSubtotal, $discount, $tax, $lineTotal) > $maximum) {
                throw ValidationException::withMessages([
                    'items' => 'A line item exceeds the maximum supported invoice amount.',
                ]);
            }
            $invoice->items()->create([
                ...$item,
                'line_subtotal' => $lineSubtotal,
                'discount_amount' => $discount,
                'tax_amount' => $tax,
                'line_total' => $lineTotal,
            ]);
            $subtotal += $lineSubtotal;
            $discountTotal += $discount;
            $taxTotal += $tax;
            $total += $lineTotal;
            if (max($subtotal, $discountTotal, $taxTotal, $total) > $maximum) {
                throw ValidationException::withMessages([
                    'items' => 'Invoice totals exceed the maximum supported amount.',
                ]);
            }
        }

        $total = round($total, 2);
        if ($total < (float) $invoice->payments()->sum('amount')) {
            throw ValidationException::withMessages([
                'items' => 'Invoice total cannot be less than payments already recorded.',
            ]);
        }

        $invoice->update([
            'subtotal' => round($subtotal, 2),
            'discount_total' => round($discountTotal, 2),
            'tax_total' => round($taxTotal, 2),
            'total' => $total,
        ]);
    }

    private function ensureType(string $type): void
    {
        abort_unless(in_array($type, ['purchase', 'sales'], true), 404);
    }
}
