@extends('layouts.app')
@php($editing = isset($invoice))
@section('title', ($editing ? 'Edit' : 'New').' '.ucfirst($type).' invoice')
@section('content')
<div class="page-heading">
    <div><div class="eyebrow">INVOICING / {{ strtoupper($type) }}</div><h1>{{ $editing ? 'Edit invoice' : 'Create '.ucfirst($type).' invoice' }}</h1><p>Enter invoice details and line items. Totals are calculated automatically.</p></div>
    <a class="btn btn-light-outline" href="{{ $editing ? route('invoices.show', $invoice) : route('invoices.index', $type) }}"><i class="bi bi-arrow-left"></i> Back</a>
</div>
<form class="form-card" method="POST" action="{{ $editing ? route('invoices.update', $invoice) : route('invoices.store', $type) }}" id="invoiceForm">
    @csrf
    @if($editing) @method('PUT') @endif
    <h2 class="form-section-title">Invoice details</h2>
    <div class="row g-3">
        <div class="col-md-6"><label class="form-label" for="party">{{ $type === 'sales' ? 'Bill to · Customer' : 'Bill from · Supplier' }} <span class="text-danger">*</span></label>
            <select class="form-select" id="party" name="{{ $type === 'sales' ? 'customer_id' : 'supplier_id' }}" required><option value="">Choose a {{ $type === 'sales' ? 'customer' : 'supplier' }}</option>
                @foreach($parties as $party)<option value="{{ $party->id }}" @selected(old($type === 'sales' ? 'customer_id' : 'supplier_id', $editing ? $invoice->{$type === 'sales' ? 'customer_id' : 'supplier_id'} : '') == $party->id)>{{ $party->name }}</option>@endforeach
            </select>
            @if($parties->isEmpty())<div class="form-text text-danger">Add a {{ $type === 'sales' ? 'customer' : 'supplier' }} before creating an invoice.</div>@endif
        </div>
        <div class="col-md-2"><label class="form-label" for="currency">Base currency</label><input class="form-control" id="currency" name="currency" value="USD" readonly><div class="form-text">Reference rates are shown on the dashboard.</div></div>
        <div class="col-md-2"><label class="form-label" for="issue_date">Issue date</label><input class="form-control" type="date" id="issue_date" name="issue_date" value="{{ old('issue_date', $editing ? $invoice->issue_date->format('Y-m-d') : now()->format('Y-m-d')) }}" required></div>
        <div class="col-md-2"><label class="form-label" for="due_date">Due date</label><input class="form-control" type="date" id="due_date" name="due_date" value="{{ old('due_date', $editing ? $invoice->due_date?->format('Y-m-d') : now()->addDays(14)->format('Y-m-d')) }}"><div class="form-text">Optional</div></div>
        <div class="col-12"><label class="form-label" for="notes">Notes</label><textarea class="form-control" id="notes" name="notes" rows="2" placeholder="Payment terms or a note for the recipient">{{ old('notes', $editing ? $invoice->notes : '') }}</textarea></div>
    </div>
    <div class="form-divider"></div>
    <div class="item-heading"><h3>Line items</h3><button type="button" class="btn btn-light-outline" id="addItem"><i class="bi bi-plus-lg"></i> Add line</button></div>
    @php($defaultItems = [['product_id' => '', 'description' => '', 'quantity' => 1, 'unit_price' => '', 'discount_rate' => 0, 'tax_rate' => 0]])
    @php($items = old('items', $editing ? $invoice->items->map(fn($item) => $item->only(['product_id','description','quantity','unit_price','discount_rate','tax_rate']))->toArray() : $defaultItems))
    <div id="itemsContainer">
        @foreach($items as $index => $item)
            <div class="item-row">
                <div class="item-desc"><label class="form-label">Product / service</label><select class="form-select product-select" name="items[{{ $index }}][product_id]"><option value="">Custom item</option>@foreach($products as $product)<option value="{{ $product->id }}" @selected(($item['product_id'] ?? '') == $product->id)>{{ $product->name }}</option>@endforeach</select></div>
                <div><label class="form-label">Description</label><input class="form-control item-description" name="items[{{ $index }}][description]" value="{{ $item['description'] ?? '' }}" required maxlength="255"></div>
                <div><label class="form-label">Qty</label><input class="form-control item-quantity" type="number" step="0.01" min="0.01" name="items[{{ $index }}][quantity]" value="{{ $item['quantity'] ?? 1 }}" required></div>
                <div><label class="form-label">Unit price</label><input class="form-control item-price" type="number" step="0.01" min="0" name="items[{{ $index }}][unit_price]" value="{{ $item['unit_price'] ?? '' }}" required></div>
                <div><label class="form-label">Discount %</label><input class="form-control item-discount" type="number" step="0.01" min="0" max="100" name="items[{{ $index }}][discount_rate]" value="{{ $item['discount_rate'] ?? 0 }}" required></div>
                <div><label class="form-label">Tax %</label><input class="form-control item-tax" type="number" step="0.01" min="0" max="100" name="items[{{ $index }}][tax_rate]" value="{{ $item['tax_rate'] ?? 0 }}" required></div>
                <button type="button" class="remove-item" aria-label="Remove item"><i class="bi bi-x-lg"></i></button>
            </div>
        @endforeach
    </div>
    <div class="invoice-totals">
        <div class="total-row"><span>Subtotal</span><strong id="subtotalPreview">0.00</strong></div>
        <div class="total-row"><span>Discount</span><strong id="discountPreview">0.00</strong></div>
        <div class="total-row"><span>Tax</span><strong id="taxPreview">0.00</strong></div>
        <div class="total-row grand-total"><span>Total</span><strong id="totalPreview">0.00</strong></div>
    </div>
    <div class="form-actions"><a class="btn btn-light-outline" href="{{ $editing ? route('invoices.show', $invoice) : route('invoices.index', $type) }}">Cancel</a><button type="submit" class="btn btn-primary" @disabled($parties->isEmpty())><i class="bi bi-check2"></i> {{ $editing ? 'Save changes' : 'Create invoice' }}</button></div>
</form>
@endsection
@push('scripts')
<script>
const catalog = {{ \Illuminate\Support\Js::from($products->map(fn($product) => ['id' => $product->id, 'name' => $product->name, 'price' => (float) $product->unit_price, 'tax' => (float) $product->tax_rate])->values()) }};
const container = document.getElementById('itemsContainer');
let rowIndex = {{ count($items) }};
const template = index => `<div class="item-row">
    <div class="item-desc"><label class="form-label">Product / service</label><select class="form-select product-select" name="items[${index}][product_id]"><option value="">Custom item</option>${catalog.map(item => `<option value="${item.id}">${item.name}</option>`).join('')}</select></div>
    <div><label class="form-label">Description</label><input class="form-control item-description" name="items[${index}][description]" required maxlength="255"></div>
    <div><label class="form-label">Qty</label><input class="form-control item-quantity" type="number" step="0.01" min="0.01" name="items[${index}][quantity]" value="1" required></div>
    <div><label class="form-label">Unit price</label><input class="form-control item-price" type="number" step="0.01" min="0" name="items[${index}][unit_price]" value="0" required></div>
    <div><label class="form-label">Discount %</label><input class="form-control item-discount" type="number" step="0.01" min="0" max="100" name="items[${index}][discount_rate]" value="0" required></div>
    <div><label class="form-label">Tax %</label><input class="form-control item-tax" type="number" step="0.01" min="0" max="100" name="items[${index}][tax_rate]" value="0" required></div>
    <button type="button" class="remove-item" aria-label="Remove item"><i class="bi bi-x-lg"></i></button>
    </div>`;
const updateTotals = () => {
    let subtotal = 0, discount = 0, tax = 0, total = 0;
    container.querySelectorAll('.item-row').forEach(row => {
        const qty = Number(row.querySelector('.item-quantity').value) || 0;
        const price = Number(row.querySelector('.item-price').value) || 0;
        const discountRate = Number(row.querySelector('.item-discount').value) || 0;
        const taxRate = Number(row.querySelector('.item-tax').value) || 0;
        const line = Math.round(qty * price * 100) / 100;
        const lineDiscount = Math.round(line * discountRate) / 100;
        const lineTax = Math.round((line - lineDiscount) * taxRate) / 100;
        subtotal += line; discount += lineDiscount; tax += lineTax; total += line - lineDiscount + lineTax;
    });
    const currency = document.getElementById('currency').value.toUpperCase() || 'USD';
    const format = value => `${currency} ${value.toFixed(2)}`;
    document.getElementById('subtotalPreview').textContent = format(subtotal);
    document.getElementById('discountPreview').textContent = format(discount);
    document.getElementById('taxPreview').textContent = format(tax);
    document.getElementById('totalPreview').textContent = format(total);
};
document.getElementById('addItem').addEventListener('click', () => { container.insertAdjacentHTML('beforeend', template(rowIndex++)); updateTotals(); });
container.addEventListener('input', updateTotals);
container.addEventListener('change', event => {
    if (event.target.matches('.product-select')) {
        const product = catalog.find(item => item.id === Number(event.target.value));
        if (product) {
            const row = event.target.closest('.item-row');
            row.querySelector('.item-description').value = product.name;
            row.querySelector('.item-price').value = product.price.toFixed(2);
            row.querySelector('.item-tax').value = product.tax.toFixed(2);
        }
    }
    updateTotals();
});
container.addEventListener('click', event => {
    const button = event.target.closest('.remove-item');
    if (button && container.querySelectorAll('.item-row').length > 1) { button.closest('.item-row').remove(); updateTotals(); }
});
updateTotals();
</script>
@endpush
