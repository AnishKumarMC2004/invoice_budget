@extends('layouts.app')
@section('title', 'Budgets')
@section('content')
<div class="page-heading"><div><div class="eyebrow">PLANNING / BUDGETS</div><h1>Budgets</h1><p>Set monthly and yearly limits and keep spending on track.</p></div><a class="btn btn-primary" href="{{ route('budgets.create') }}"><i class="bi bi-plus-lg"></i> New budget</a></div>
<section class="content-panel">
    <div class="table-toolbar"><div class="table-caption"><strong>{{ $budgets->total() }}</strong> budgets · actuals update as expenses are recorded</div></div>
    <div class="table-scroll"><table class="records-table"><thead><tr><th>BUDGET</th><th>PERIOD</th><th>CATEGORY</th><th>SPENT</th><th>LIMIT / PROGRESS</th><th></th></tr></thead><tbody>
    @forelse($budgets as $budget)
        @php($spent = (float) $budget->expenses_sum_amount)
        @php($progress = (float) $budget->amount > 0 ? min(100, $spent / (float) $budget->amount * 100) : 0)
        <tr><td><strong>{{ $budget->name }}</strong><small>{{ $budget->notes ?: 'Budget' }}</small></td><td>{{ $budget->period === 'monthly' ? \Illuminate\Support\Carbon::create()->month($budget->month)->format('F') : 'Full year' }} {{ $budget->year }}</td><td>{{ $budget->category }}</td><td><strong>{{ number_format($spent, 2) }}</strong></td><td class="budget-cell"><div class="budget-row-top"><strong>{{ number_format((float) $budget->amount, 2) }}</strong><span>{{ number_format($progress, 0) }}%</span></div><div class="progress budget-progress"><div class="progress-bar {{ $progress >= 90 ? 'bg-warning' : '' }}" style="width:{{ $progress }}%"></div></div><small>{{ number_format(max(0, (float) $budget->amount - $spent), 2) }} remaining</small></td><td class="actions-cell"><a class="btn btn-light-outline" href="{{ route('budgets.edit', $budget) }}">Edit</a><form class="d-inline" method="POST" action="{{ route('budgets.destroy', $budget) }}" data-confirm="Delete this budget? Linked expenses will be kept without a budget link.">@csrf @method('DELETE')<button class="btn btn-light-outline text-danger" type="submit">Delete</button></form></td></tr>
    @empty<tr><td colspan="6" class="empty-table">No budgets created yet. <a class="text-link" href="{{ route('budgets.create') }}">Create your first budget</a>.</td></tr>@endforelse
    </tbody></table></div>{{ $budgets->links() }}
</section>
@endsection
