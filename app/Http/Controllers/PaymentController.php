<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentController extends Controller
{
    public function store(Request $request, Invoice $invoice): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0', 'max:9999999999'],
            'paid_at' => ['required', 'date'],
            'method' => ['required', 'string', 'max:80'],
            'reference' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($invoice, $data): void {
            $lockedInvoice = Invoice::whereKey($invoice->id)->lockForUpdate()->firstOrFail();
            $remaining = round((float) $lockedInvoice->total - (float) $lockedInvoice->payments()->sum('amount'), 2);
            if ((float) $data['amount'] > $remaining) {
                throw ValidationException::withMessages(['amount' => 'Payment cannot exceed the remaining invoice balance.']);
            }
            $lockedInvoice->payments()->create($data);
            $lockedInvoice->refreshPaymentStatus();
        });

        return back()->with('success', 'Payment recorded.');
    }

    public function destroy(Invoice $invoice, int $payment): RedirectResponse
    {
        DB::transaction(function () use ($invoice, $payment): void {
            $lockedInvoice = Invoice::whereKey($invoice->id)->lockForUpdate()->firstOrFail();
            $lockedInvoice->payments()->findOrFail($payment)->delete();
            $lockedInvoice->refreshPaymentStatus();
        });

        return back()->with('success', 'Payment removed.');
    }
}
