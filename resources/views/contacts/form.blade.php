@extends('layouts.app')
@php($editing = isset($item))
@section('title', ($editing ? 'Edit ' : 'Add ').rtrim(ucfirst($type), 's'))
@section('content')
<div class="page-heading"><div><div class="eyebrow">DIRECTORY / {{ strtoupper($type) }}</div><h1>{{ $editing ? 'Edit '.rtrim(ucfirst($type), 's') : 'Add '.rtrim(ucfirst($type), 's') }}</h1><p>Keep contact information available on invoices.</p></div><a class="btn btn-light-outline" href="{{ route($type.'.index') }}"><i class="bi bi-arrow-left"></i> Back</a></div>
<form class="form-card" method="POST" action="{{ $editing ? route($type.'.update', $item->id) : route($type.'.store') }}">
    @csrf @if($editing) @method('PUT') @endif
    <h2 class="form-section-title">Contact details</h2>
    <div class="row g-3">
        <div class="col-md-6"><label class="form-label" for="name">Name / business name</label><input class="form-control" id="name" name="name" maxlength="150" value="{{ old('name', $item->name ?? '') }}" required></div>
        <div class="col-md-6"><label class="form-label" for="email">Email</label><input class="form-control" type="email" id="email" name="email" maxlength="180" value="{{ old('email', $item->email ?? '') }}"></div>
        <div class="col-md-6"><label class="form-label" for="phone">Phone</label><input class="form-control" id="phone" name="phone" maxlength="40" value="{{ old('phone', $item->phone ?? '') }}"></div>
        <div class="col-md-6"><label class="form-label" for="tax_number">Tax number</label><input class="form-control" id="tax_number" name="tax_number" maxlength="80" value="{{ old('tax_number', $item->tax_number ?? '') }}"></div>
        <div class="col-12"><label class="form-label" for="address">Address</label><textarea class="form-control" id="address" name="address" rows="3" maxlength="1000">{{ old('address', $item->address ?? '') }}</textarea></div>
    </div>
    <div class="form-actions"><a class="btn btn-light-outline" href="{{ route($type.'.index') }}">Cancel</a><button type="submit" class="btn btn-primary">{{ $editing ? 'Save changes' : 'Add '.rtrim(ucfirst($type), 's') }}</button></div>
</form>
@endsection
