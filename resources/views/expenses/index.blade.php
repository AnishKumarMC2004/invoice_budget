@extends('layouts.app')
@section('title', 'Expenses')
@section('content')
<div class="page-heading"><div><div class="eyebrow">PLANNING / EXPENSES</div><h1>Expenses</h1><p>Track business spend and connect it to the right budget.</p></div><a class="btn btn-primary" href="{{ route('expenses.create') }}"><i class="bi bi-plus-lg"></i> Record expense</a></div>
<section class="content-panel">
    <div class="table-toolbar"><div class="table-caption"><strong>{{ $expenses->total() }}</strong> expenses recorded</div></div>
    <div class="table-scroll"><table class="records-table"><thead><tr><th>EXPENSE</th><th>CATEGORY</th><th>DATE</th><th>VENDOR</th><th>BUDGET</th><th class="text-end">AMOUNT</th><th></th></tr></thead><tbody>
    @forelse($expenses as $expense)
        <tr><td><strong>{{ $expense->description }}</strong>@if($expense->notes)<small>{{ $expense->notes }}</small>@endif</td><td>{{ $expense->category }}</td><td>{{ $expense->expense_date->format('M j, Y') }}</td><td>{{ $expense->vendor ?: '—' }}</td><td>{{ $expense->budget?->name ?? 'Unassigned' }}</td><td class="text-end"><strong>{{ number_format((float) $expense->amount, 2) }}</strong></td><td class="actions-cell"><a class="btn btn-light-outline" href="{{ route('expenses.edit', $expense) }}">Edit</a><form class="d-inline" method="POST" action="{{ route('expenses.destroy', $expense) }}" data-confirm="Delete this expense?">@csrf @method('DELETE')<button class="btn btn-light-outline text-danger" type="submit">Delete</button></form></td></tr>
    @empty<tr><td colspan="7" class="empty-table">No expenses recorded. <a class="text-link" href="{{ route('expenses.create') }}">Record an expense</a>.</td></tr>@endforelse
    </tbody></table></div>{{ $expenses->links() }}
</section>
@endsection
