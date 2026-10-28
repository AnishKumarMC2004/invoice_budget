@extends('layouts.app')
@php($editing = isset($product))
@section('title', $editing ? 'Edit catalog item' : 'New catalog item')
@section('content')
<div class="page-heading"><div><div class="eyebrow">CATALOG / PRODUCTS & SERVICES</div><h1>{{ $editing ? 'Edit catalog item' : 'Add a catalog item' }}</h1><p>Saved items can be selected in invoice line items.</p></div><a class="btn btn-light-outline" href="{{ route('products.index') }}"><i class="bi bi-arrow-left"></i> Back</a></div>
<form class="form-card" method="POST" action="{{ $editing ? route('products.update', $product) : route('products.store') }}">
    @csrf @if($editing) @method('PUT') @endif
    <h2 class="form-section-title">Catalog details</h2>
    <div class="row g-3">
        <div class="col-md-6"><label class="form-label" for="name">Name</label><input class="form-control" id="name" name="name" maxlength="150" value="{{ old('name', $product->name ?? '') }}" required></div>
        <div class="col-md-3"><label class="form-label" for="sku">SKU / code</label><input class="form-control" id="sku" name="sku" maxlength="60" value="{{ old('sku', $product->sku ?? '') }}"></div>
        <div class="col-md-3"><label class="form-label" for="type">Type</label><select class="form-select" id="type" name="type"><option value="service" @selected(old('type', $product->type ?? 'service') === 'service')>Service</option><option value="product" @selected(old('type', $product->type ?? '') === 'product')>Product</option></select></div>
        <div class="col-md-4"><label class="form-label" for="unit_price">Default unit price</label><div class="input-group"><span class="input-group-text">USD</span><input class="form-control" type="number" id="unit_price" name="unit_price" step="0.01" min="0" value="{{ old('unit_price', $product->unit_price ?? '0.00') }}" required></div></div>
        <div class="col-md-4"><label class="form-label" for="tax_rate">Tax rate</label><div class="input-group"><input class="form-control" type="number" id="tax_rate" name="tax_rate" step="0.01" min="0" max="100" value="{{ old('tax_rate', $product->tax_rate ?? '0.00') }}" required><span class="input-group-text">%</span></div></div>
        <div class="col-md-12"><label class="form-label" for="description">Description</label><textarea class="form-control" id="description" name="description" rows="3" maxlength="2000">{{ old('description', $product->description ?? '') }}</textarea></div>
    </div>
    <div class="form-actions"><a class="btn btn-light-outline" href="{{ route('products.index') }}">Cancel</a><button class="btn btn-primary" type="submit">{{ $editing ? 'Save changes' : 'Add item' }}</button></div>
</form>
@endsection
