@extends('layouts.app')
@php($editing = isset($expense))
@section('title', $editing ? 'Edit expense' : 'Record expense')
@section('content')
<div class="page-heading"><div><div class="eyebrow">PLANNING / EXPENSES</div><h1>{{ $editing ? 'Edit expense' : 'Record an expense' }}</h1><p>Assign a matching budget period to update spending progress.</p></div><a class="btn btn-light-outline" href="{{ route('expenses.index') }}"><i class="bi bi-arrow-left"></i> Back</a></div>
<form class="form-card" method="POST" action="{{ $editing ? route('expenses.update', $expense) : route('expenses.store') }}">
    @csrf @if($editing) @method('PUT') @endif
    <h2 class="form-section-title">Expense details</h2>
    <div class="row g-3">
        <div class="col-md-6"><label class="form-label" for="description">Description</label><input class="form-control" id="description" name="description" maxlength="180" value="{{ old('description', $expense->description ?? '') }}" required></div>
        <div class="col-md-6"><label class="form-label" for="vendor">Vendor / payee</label><input class="form-control" id="vendor" name="vendor" maxlength="150" value="{{ old('vendor', $expense->vendor ?? '') }}"></div>
        <div class="col-md-4"><label class="form-label" for="category">Category</label><input class="form-control" id="category" name="category" maxlength="100" value="{{ old('category', $expense->category ?? 'General') }}" required></div>
        <div class="col-md-4"><label class="form-label" for="amount">Amount</label><div class="input-group"><span class="input-group-text">USD</span><input class="form-control" type="number" id="amount" name="amount" min="0.01" step="0.01" value="{{ old('amount', $expense->amount ?? '') }}" required></div></div>
        <div class="col-md-4"><label class="form-label" for="expense_date">Expense date</label><input class="form-control" type="date" id="expense_date" name="expense_date" value="{{ old('expense_date', isset($expense) ? $expense->expense_date->format('Y-m-d') : now()->format('Y-m-d')) }}" required></div>
        <div class="col-md-6"><label class="form-label" for="budget_id">Budget</label><select class="form-select" id="budget_id" name="budget_id"><option value="">Unassigned</option>@foreach($budgets as $budget)<option value="{{ $budget->id }}" @selected(old('budget_id', $expense->budget_id ?? '') == $budget->id)>{{ $budget->name }} · {{ $budget->period === 'monthly' ? \Illuminate\Support\Carbon::create()->month($budget->month)->format('M').' ' : '' }}{{ $budget->year }}</option>@endforeach</select><div class="form-text">The expense date must fall within the selected budget period.</div></div>
        <div class="col-md-6"><label class="form-label" for="notes">Notes</label><textarea class="form-control" id="notes" name="notes" rows="2" maxlength="2000">{{ old('notes', $expense->notes ?? '') }}</textarea></div>
    </div>
    <div class="form-actions"><a class="btn btn-light-outline" href="{{ route('expenses.index') }}">Cancel</a><button class="btn btn-primary" type="submit">{{ $editing ? 'Save changes' : 'Record expense' }}</button></div>
</form>
@endsection
