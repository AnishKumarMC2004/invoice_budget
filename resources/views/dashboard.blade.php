@extends('layouts.app')
@section('title', 'Overview')
@section('content')
<div class="page-heading">
    <div><div class="eyebrow">YOUR BUSINESS AT A GLANCE</div><h1>Good {{ now()->hour < 12 ? 'morning' : (now()->hour < 18 ? 'afternoon' : 'evening') }}, {{ explode(' ', auth()->user()->name)[0] }} <span class="wave">✦</span></h1><p>Here's what's happening with your finances this month.</p></div>
    <div class="heading-actions"><a class="btn btn-light-outline" href="{{ route('reports.index') }}"><i class="bi bi-download"></i> Reports</a><a class="btn btn-primary" href="{{ route('invoices.create', 'sales') }}"><i class="bi bi-plus-lg"></i> New invoice</a></div>
</div>
<div class="summary-grid">
    <article class="summary-card"><div class="card-topline"><span>Sales this month</span><span class="metric-icon mint"><i class="bi bi-arrow-up-right"></i></span></div><div class="metric-value">{{ number_format($monthlySales, 2) }} <small>USD</small></div><div class="metric-foot"><span class="metric-dot green-dot"></span>{{ $invoiceCount }} invoices issued this month</div></article>
    <article class="summary-card"><div class="card-topline"><span>Outstanding</span><span class="metric-icon peach"><i class="bi bi-hourglass-split"></i></span></div><div class="metric-value">{{ number_format($receivables, 2) }} <small>USD</small></div><div class="metric-foot">Open customer balances</div></article>
    <article class="summary-card"><div class="card-topline"><span>Purchases this month</span><span class="metric-icon lilac"><i class="bi bi-bag-check"></i></span></div><div class="metric-value">{{ number_format($monthlyPurchases, 2) }} <small>USD</small></div><div class="metric-foot">Supplier invoices recorded</div></article>
    <article class="summary-card"><div class="card-topline"><span>Expenses this month</span><span class="metric-icon sky"><i class="bi bi-wallet2"></i></span></div><div class="metric-value">{{ number_format($monthlyExpenses, 2) }} <small>USD</small></div><div class="metric-foot">Tracked operating expenses</div></article>
</div>
<div class="dashboard-grid">
    <section class="panel chart-panel">
        <div class="panel-heading"><div><h2>Cash flow</h2><p>Sales and purchases · last 6 months</p></div><span class="period-pill"><i class="bi bi-calendar3"></i> 6 months</span></div>
        @php($maxTrend = max(1, (float) $trend->max('sales'), (float) $trend->max('purchases')))
        <div class="chart-legend"><span><i class="legend-dot sales-color"></i>Sales</span><span><i class="legend-dot purchases-color"></i>Purchases</span></div>
        <div class="bar-chart">
            @foreach($trend as $point)
            <div class="bar-group"><div class="bars"><span class="bar sales-bar" style="height:{{ max(3, $point['sales'] / $maxTrend * 100) }}%" title="Sales {{ number_format($point['sales'], 2) }}"></span><span class="bar purchases-bar" style="height:{{ max(3, $point['purchases'] / $maxTrend * 100) }}%" title="Purchases {{ number_format($point['purchases'], 2) }}"></span></div><span class="bar-label">{{ $point['label'] }}</span></div>
            @endforeach
        </div>
        <div class="chart-baseline"></div>
    </section>
    <section class="panel currency-panel">
        <div class="panel-heading"><div><h2>Currency snapshot</h2><p>Daily reference rates · Frankfurter API</p></div><span class="exchange-icon"><i class="bi bi-arrow-left-right"></i></span></div>
        @if($exchangeRates)
            <div class="base-currency"><span class="currency-badge">USD</span><span>1 US Dollar</span><span class="base-label">BASE</span></div>
            <div class="rates-list">
                @foreach(['EUR' => 'Euro', 'GBP' => 'British Pound', 'INR' => 'Indian Rupee'] as $code => $name)
                    <div class="rate-row"><span class="currency-code">{{ $code }}</span><span class="currency-name">{{ $name }}</span><strong>{{ number_format((float) $exchangeRates['rates'][$code], 4) }}</strong></div>
                @endforeach
            </div>
            <div class="rate-updated">Rates dated {{ \Illuminate\Support\Carbon::parse($exchangeRates['date'])->format('M j, Y') }} <span><i class="bi bi-arrow-repeat"></i> Refreshes daily</span></div>
        @else
            <div class="api-error"><i class="bi bi-cloud-slash"></i><div><strong>Rates unavailable</strong><p>{{ $exchangeRateError }} Try again later.</p></div></div>
        @endif
    </section>
</div>
<div class="dashboard-grid lower-grid">
    <section class="panel table-panel">
        <div class="panel-heading"><div><h2>Recent invoices</h2><p>Keep an eye on what's still open</p></div><a class="text-link" href="{{ route('invoices.index', 'sales') }}">View all <i class="bi bi-arrow-right"></i></a></div>
        <div class="table-responsive"><table class="table app-table"><thead><tr><th>INVOICE</th><th>CLIENT / SUPPLIER</th><th>DUE DATE</th><th>STATUS</th><th class="text-end">AMOUNT</th></tr></thead><tbody>
        @forelse($openInvoices as $invoice)
            <tr><td><a class="invoice-link" href="{{ route('invoices.show', $invoice) }}">{{ $invoice->number }}</a><small>{{ ucfirst($invoice->type) }}</small></td><td>{{ $invoice->type === 'sales' ? ($invoice->customer?->name ?? 'Deleted customer') : ($invoice->supplier?->name ?? 'Deleted supplier') }}</td><td>{{ $invoice->due_date?->format('M j, Y') ?? '—' }}</td><td><span class="status-badge status-{{ $invoice->status }}">{{ ucfirst($invoice->status) }}</span></td><td class="text-end amount-cell">{{ $invoice->currency }} {{ number_format((float) $invoice->total, 2) }}</td></tr>
        @empty
            <tr><td colspan="5"><div class="empty-state compact-empty"><i class="bi bi-check2-circle"></i><strong>All caught up</strong><span>There are no open invoices.</span></div></td></tr>
        @endforelse
        </tbody></table></div>
    </section>
    <section class="panel budget-panel">
        <div class="panel-heading"><div><h2>Budget pulse</h2><p>Spending against your limits</p></div><a class="text-link" href="{{ route('budgets.index') }}">All budgets <i class="bi bi-arrow-right"></i></a></div>
        @forelse($budgets as $budget)
            @php($spent = (float) $budget->expenses_sum_amount)
            @php($progress = $budget->amount > 0 ? min(100, $spent / (float) $budget->amount * 100) : 0)
            <div class="budget-row"><div class="budget-row-top"><div><strong>{{ $budget->name }}</strong><small>{{ $budget->period === 'monthly' ? \Illuminate\Support\Carbon::create()->month($budget->month)->format('F') : 'Yearly' }} · {{ $budget->year }}</small></div><span>{{ number_format($progress, 0) }}%</span></div><div class="progress budget-progress"><div class="progress-bar {{ $progress >= 90 ? 'bg-warning' : '' }}" style="width:{{ $progress }}%"></div></div><div class="budget-row-foot"><span>{{ number_format($spent, 2) }} spent</span><span>{{ number_format((float) $budget->amount, 2) }} budget</span></div></div>
        @empty
            <div class="empty-state compact-empty"><i class="bi bi-pie-chart"></i><strong>No budgets yet</strong><span>Create your first budget to track spending.</span><a href="{{ route('budgets.create') }}">Create budget</a></div>
        @endforelse
    </section>
</div>
@endsection
