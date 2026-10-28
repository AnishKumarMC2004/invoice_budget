@extends('layouts.app')
@section('title', 'Products & services')
@section('content')
<div class="page-heading"><div><div class="eyebrow">CATALOG / PRODUCTS & SERVICES</div><h1>Products & services</h1><p>Maintain reusable line items for faster invoice creation.</p></div><a class="btn btn-primary" href="{{ route('products.create') }}"><i class="bi bi-plus-lg"></i> Add item</a></div>
<section class="content-panel">
    <div class="table-toolbar"><form class="filter-row w-100" method="GET"><div class="search-form"><input class="form-control" name="search" value="{{ request('search') }}" placeholder="Search name or SKU"><button class="btn btn-light-outline" type="submit"><i class="bi bi-search"></i></button></div><span class="table-caption ms-auto"><strong>{{ $products->total() }}</strong> catalog items</span></form></div>
    <div class="table-scroll"><table class="records-table"><thead><tr><th>ITEM</th><th>SKU</th><th>TYPE</th><th>UNIT PRICE</th><th>TAX RATE</th><th></th></tr></thead><tbody>
    @forelse($products as $product)
        <tr><td><strong>{{ $product->name }}</strong><small>{{ $product->description ?: '—' }}</small></td><td>{{ $product->sku ?: '—' }}</td><td><span class="status-badge status-{{ $product->type === 'service' ? 'partial' : 'pending' }}">{{ ucfirst($product->type) }}</span></td><td><strong>{{ number_format((float) $product->unit_price, 2) }}</strong></td><td>{{ number_format((float) $product->tax_rate, 2) }}%</td><td class="actions-cell"><a class="btn btn-light-outline" href="{{ route('products.edit', $product) }}">Edit</a><form class="d-inline" method="POST" action="{{ route('products.destroy', $product) }}" data-confirm="Delete this catalog item? Invoice line items will be retained.">@csrf @method('DELETE')<button class="btn btn-light-outline text-danger" type="submit">Delete</button></form></td></tr>
    @empty<tr><td colspan="6" class="empty-table">Your catalog is empty. <a class="text-link" href="{{ route('products.create') }}">Add a product or service</a>.</td></tr>@endforelse
    </tbody></table></div>{{ $products->links() }}
</section>
@endsection
