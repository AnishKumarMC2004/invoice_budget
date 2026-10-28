@extends('layouts.app')
@php($editing = isset($budget))
@section('title', $editing ? 'Edit budget' : 'New budget')
@section('content')
<div class="page-heading"><div><div class="eyebrow">PLANNING / BUDGETS</div><h1>{{ $editing ? 'Edit budget' : 'Create a budget' }}</h1><p>Choose a time period and spending category.</p></div><a class="btn btn-light-outline" href="{{ route('budgets.index') }}"><i class="bi bi-arrow-left"></i> Back</a></div>
<form class="form-card" method="POST" action="{{ $editing ? route('budgets.update', $budget) : route('budgets.store') }}">
    @csrf @if($editing) @method('PUT') @endif
    <h2 class="form-section-title">Budget details</h2>
    <div class="row g-3">
        <div class="col-md-6"><label class="form-label" for="name">Budget name</label><input class="form-control" id="name" name="name" maxlength="120" value="{{ old('name', $budget->name ?? '') }}" placeholder="e.g. Studio operations" required></div>
        <div class="col-md-6"><label class="form-label" for="category">Category</label><input class="form-control" id="category" name="category" maxlength="100" value="{{ old('category', $budget->category ?? 'General') }}" required></div>
        <div class="col-md-4"><label class="form-label" for="period">Budget period</label><select class="form-select" id="period" name="period" required><option value="monthly" @selected(old('period', $budget->period ?? 'monthly') === 'monthly')>Monthly</option><option value="yearly" @selected(old('period', $budget->period ?? '') === 'yearly')>Yearly</option></select></div>
        <div class="col-md-4"><label class="form-label" for="month">Month</label><select class="form-select" id="month" name="month"><option value="">Select month</option>@foreach(range(1,12) as $month)<option value="{{ $month }}" @selected(old('month', $budget->month ?? now()->month) == $month)>{{ \Illuminate\Support\Carbon::create()->month($month)->format('F') }}</option>@endforeach</select></div>
        <div class="col-md-4"><label class="form-label" for="year">Year</label><input class="form-control" type="number" id="year" name="year" min="2000" max="2100" value="{{ old('year', $budget->year ?? now()->year) }}" required></div>
        <div class="col-md-6"><label class="form-label" for="amount">Budget amount</label><div class="input-group"><span class="input-group-text">USD</span><input class="form-control" type="number" id="amount" name="amount" step="0.01" min="0.01" value="{{ old('amount', $budget->amount ?? '') }}" required></div><div class="form-text">Expenses linked to this budget are summed automatically.</div></div>
        <div class="col-md-6"><label class="form-label" for="notes">Notes</label><textarea class="form-control" id="notes" name="notes" rows="2" maxlength="2000">{{ old('notes', $budget->notes ?? '') }}</textarea></div>
    </div>
    <div class="form-actions"><a class="btn btn-light-outline" href="{{ route('budgets.index') }}">Cancel</a><button type="submit" class="btn btn-primary">{{ $editing ? 'Save changes' : 'Create budget' }}</button></div>
</form>
@endsection
@push('scripts')<script>const period=document.getElementById('period'),month=document.getElementById('month');const toggleMonth=()=>{month.disabled=period.value==='yearly';if(month.disabled)month.value=''};period.addEventListener('change',toggleMonth);toggleMonth();</script>@endpush
