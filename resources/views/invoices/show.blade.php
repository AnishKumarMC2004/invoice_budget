@extends('layouts.app')
@section('title', $invoice->number)
@section('content')
<div class="page-heading no-print">
    <div><div class="eyebrow">INVOICING / {{ strtoupper($invoice->type) }}</div><h1>Invoice details</h1><p>Review line items, payment history and status.</p></div>
    <div class="heading-actions"><a class="btn btn-light-outline" href="{{ route('invoices.index', $invoice->type) }}"><i class="bi bi-arrow-left"></i> Back to invoices</a><button class="btn btn-light-outline" type="button" onclick="window.print()"><i class="bi bi-printer"></i> Print</button><a class="btn btn-primary" href="{{ route('invoices.edit', $invoice) }}"><i class="bi bi-pencil"></i> Edit</a></div>
</div>
<div class="invoice-detail-grid">
    <section class="detail-card">
        <div class="detail-heading"><div><div class="eyebrow">{{ $invoice->type === 'sales' ? 'SALES INVOICE' : 'PURCHASE INVOICE' }}</div><div class="detail-number">{{ $invoice->number }}</div></div><span class="status-badge status-{{ $invoice->display_status }}">{{ ucfirst($invoice->display_status) }}</span></div>
        <div class="detail-party"><span>{{ $invoice->type === 'sales' ? 'Bill to' : 'Supplier' }}</span><strong>{{ $invoice->type === 'sales' ? ($invoice->customer?->name ?? 'Deleted customer') : ($invoice->supplier?->name ?? 'Deleted supplier') }}</strong><p>{{ $invoice->type === 'sales' ? $invoice->customer?->email : $invoice->supplier?->email }}</p><p>{{ $invoice->type === 'sales' ? $invoice->customer?->address : $invoice->supplier?->address }}</p></div>
        <div class="detail-meta"><div><span>ISSUE DATE</span><strong>{{ $invoice->issue_date->format('M j, Y') }}</strong></div><div><span>DUE DATE</span><strong>{{ $invoice->due_date?->format('M j, Y') ?? 'No due date' }}</strong></div><div><span>CURRENCY</span><strong>{{ $invoice->currency }}</strong></div></div>
        <div class="table-scroll mt-3"><table class="records-table"><thead><tr><th>DESCRIPTION</th><th>QTY × RATE</th><th>DISCOUNT</th><th>TAX</th><th class="text-end">TOTAL</th></tr></thead><tbody>
        @foreach($invoice->items as $item)<tr><td><strong>{{ $item->description }}</strong></td><td>{{ number_format((float) $item->quantity, 2) }} × {{ number_format((float) $item->unit_price, 2) }}</td><td>{{ number_format((float) $item->discount_amount, 2) }}<small>{{ number_format((float) $item->discount_rate, 2) }}%</small></td><td>{{ number_format((float) $item->tax_amount, 2) }}<small>{{ number_format((float) $item->tax_rate, 2) }}%</small></td><td class="text-end"><strong>{{ number_format((float) $item->line_total, 2) }}</strong></td></tr>@endforeach
        </tbody></table></div>
        <div class="invoice-totals"><div class="total-row"><span>Subtotal</span><strong>{{ $invoice->currency }} {{ number_format((float) $invoice->subtotal, 2) }}</strong></div><div class="total-row"><span>Discount</span><strong>− {{ $invoice->currency }} {{ number_format((float) $invoice->discount_total, 2) }}</strong></div><div class="total-row"><span>Tax</span><strong>{{ $invoice->currency }} {{ number_format((float) $invoice->tax_total, 2) }}</strong></div><div class="total-row grand-total"><span>Total due</span><strong>{{ $invoice->currency }} {{ number_format((float) $invoice->total, 2) }}</strong></div><div class="total-row"><span>Paid to date</span><strong>{{ $invoice->currency }} {{ number_format((float) $invoice->amount_paid, 2) }}</strong></div><div class="total-row"><span>Remaining</span><strong>{{ $invoice->currency }} {{ number_format(max(0, (float) $invoice->total - (float) $invoice->amount_paid), 2) }}</strong></div></div>
        @if($invoice->notes)<div class="form-divider"></div><div class="detail-party mb-0"><span>Notes</span><p>{{ $invoice->notes }}</p></div>@endif
    </section>
    <aside class="detail-card">
        <div class="panel-heading"><div><h2>Payments</h2><p>{{ $invoice->payments->count() }} recorded</p></div><span class="exchange-icon"><i class="bi bi-credit-card"></i></span></div>
        <div class="payments-list">
            @forelse($invoice->payments->sortByDesc('paid_at') as $payment)
                <div class="payment-line"><div><strong>{{ $payment->method }}</strong><small>{{ $payment->paid_at->format('M j, Y') }}{{ $payment->reference ? ' · '.$payment->reference : '' }}</small></div><div class="text-end"><strong>{{ $invoice->currency }} {{ number_format((float) $payment->amount, 2) }}</strong><form class="no-print" method="POST" action="{{ route('payments.destroy', [$invoice, $payment]) }}" data-confirm="Remove this payment?">@csrf @method('DELETE')<button class="icon-button p-0 text-danger" type="submit">Remove</button></form></div></div>
            @empty<div class="empty-state compact-empty"><i class="bi bi-credit-card-2-back"></i><strong>No payments yet</strong><span>Recorded payments will appear here.</span></div>@endforelse
        </div>
        @if($invoice->display_status !== 'paid')
        <div class="form-divider"></div>
        <form class="payment-form" method="POST" action="{{ route('payments.store', $invoice) }}">
            @csrf
            <h3 class="form-section-title">Record a payment</h3>
            <div class="mb-3"><label class="form-label" for="amount">Amount · Remaining {{ $invoice->currency }} {{ number_format(max(0, (float) $invoice->total - (float) $invoice->amount_paid), 2) }}</label><input class="form-control" id="amount" name="amount" type="number" min="0.01" max="{{ max(0, (float) $invoice->total - (float) $invoice->amount_paid) }}" step="0.01" required></div>
            <div class="row g-2"><div class="col-6"><label class="form-label" for="paid_at">Paid date</label><input class="form-control" id="paid_at" name="paid_at" type="date" value="{{ now()->format('Y-m-d') }}" required></div><div class="col-6"><label class="form-label" for="method">Method</label><select class="form-select" id="method" name="method" required><option>Bank transfer</option><option>Card</option><option>Cash</option><option>Cheque</option><option>Other</option></select></div><div class="col-12"><label class="form-label" for="reference">Reference</label><input class="form-control" id="reference" name="reference" maxlength="120"></div></div>
            <button type="submit" class="btn btn-primary w-100 mt-3">Add payment</button>
        </form>
        @endif
        <div class="detail-actions no-print"><form method="POST" action="{{ route('invoices.destroy', $invoice) }}" data-confirm="Delete this invoice and its payment history?">@csrf @method('DELETE')<button class="btn btn-light-outline text-danger" type="submit"><i class="bi bi-trash"></i> Delete invoice</button></form></div>
    </aside>
</div>
@endsection
