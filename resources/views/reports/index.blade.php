@extends('layouts.app')
@section('title', 'Reports')
@section('content')
<div class="page-heading"><div><div class="eyebrow">INSIGHTS / REPORTS</div><h1>Reports & analytics</h1><p>A clear view of revenue, costs and operating expenses.</p></div><form class="period-toggle" method="GET"><label for="year" class="form-label mb-0">Fiscal year</label><select class="form-select" id="year" name="year" onchange="this.form.submit()">@foreach(range(now()->year, now()->year - 5) as $option)<option value="{{ $option }}" @selected($year === $option)>{{ $option }}</option>@endforeach</select><button class="btn btn-light-outline" type="button" onclick="window.print()"><i class="bi bi-printer"></i> Print</button></form></div>
<div class="report-cards">
    <div class="report-card"><span>Sales revenue</span><strong>{{ number_format($salesTotal, 2) }} <small>USD</small></strong></div>
    <div class="report-card"><span>Purchase invoices</span><strong>{{ number_format($purchaseTotal, 2) }} <small>USD</small></strong></div>
    <div class="report-card"><span>Operating expenses</span><strong>{{ number_format($expenseTotal, 2) }} <small>USD</small></strong></div>
    <div class="report-card"><span>Net after costs</span><strong class="{{ $salesTotal - $purchaseTotal - $expenseTotal >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format($salesTotal - $purchaseTotal - $expenseTotal, 2) }} <small>USD</small></strong></div>
</div>
<section class="content-panel">
    <div class="table-toolbar"><div><h2 class="form-section-title mb-1">Monthly performance · {{ $year }}</h2><div class="table-caption">{{ $invoiceCount }} invoices issued this year</div></div></div>
    <div class="table-scroll"><table class="records-table report-table"><thead><tr><th>MONTH</th><th class="text-end">SALES</th><th class="text-end">PURCHASES</th><th class="text-end">EXPENSES</th><th class="text-end">NET</th><th>PERFORMANCE</th></tr></thead><tbody>
    @php($maxMonthly = max(1, (float) $months->max('sales')))
    @foreach($months as $row)
        <tr><td><strong>{{ $row['month'] }}</strong></td><td class="text-end">{{ number_format($row['sales'], 2) }}</td><td class="text-end">{{ number_format($row['purchases'], 2) }}</td><td class="text-end">{{ number_format($row['expenses'], 2) }}</td><td class="text-end {{ $row['net'] >= 0 ? 'net-positive' : 'net-negative' }}">{{ number_format($row['net'], 2) }}</td><td style="width:180px"><div class="progress budget-progress"><div class="progress-bar" style="width:{{ min(100, $row['sales'] / $maxMonthly * 100) }}%"></div></div></td></tr>
    @endforeach
    </tbody></table></div>
</section>
@endsection
