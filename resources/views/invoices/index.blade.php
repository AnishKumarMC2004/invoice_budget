@extends('layouts.app')
@section('title', ucfirst($type).' invoices')
@section('content')
<div class="page-heading">
    <div><div class="eyebrow">INVOICING / {{ strtoupper($type) }}</div><h1>{{ ucfirst($type) }} invoices</h1><p>{{ $type === 'sales' ? 'Track customer billing and incoming payments.' : 'Keep supplier bills and outgoing costs organised.' }}</p></div>
    <div class="heading-actions"><a class="btn btn-primary" href="{{ route('invoices.create', $type) }}"><i class="bi bi-plus-lg"></i> New {{ $type === 'sales' ? 'sales' : 'purchase' }} invoice</a></div>
</div>
<section class="content-panel">
    <div class="table-toolbar">
        <form class="filter-row w-100" method="GET" action="{{ route('invoices.index', $type) }}">
            <div class="search-form"><input class="form-control" name="search" value="{{ request('search') }}" placeholder="Search invoice or contact"><button class="btn btn-light-outline" type="submit"><i class="bi bi-search"></i></button></div>
            <select class="form-select" name="status" onchange="this.form.submit()"><option value="">All statuses</option>@foreach(['pending','partial','paid','overdue'] as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>@endforeach</select>
            <span class="table-caption ms-auto"><strong>{{ $invoices->total() }}</strong> records</span>
        </form>
    </div>
    <div class="table-scroll"><table class="records-table">
        <thead><tr><th>INVOICE</th><th>{{ $type === 'sales' ? 'CUSTOMER' : 'SUPPLIER' }}</th><th>ISSUED</th><th>DUE</th><th>STATUS</th><th class="text-end">TOTAL</th><th></th></tr></thead>
        <tbody>
        @forelse($invoices as $invoice)
            @php($party = $type === 'sales' ? $invoice->customer : $invoice->supplier)
            <tr>
                <td><a class="invoice-link" href="{{ route('invoices.show', $invoice) }}">{{ $invoice->number }}</a><small>{{ $invoice->currency }}</small></td>
                <td><strong>{{ $party?->name ?? 'Deleted contact' }}</strong><small>{{ $party?->email ?? '—' }}</small></td>
                <td>{{ $invoice->issue_date->format('M j, Y') }}</td>
                <td>{{ $invoice->due_date?->format('M j, Y') ?? '—' }}</td>
                <td><span class="status-badge status-{{ $invoice->display_status }}">{{ ucfirst($invoice->display_status) }}</span></td>
                <td class="text-end"><strong>{{ number_format((float) $invoice->total, 2) }}</strong><small class="text-end">{{ number_format((float) $invoice->amount_paid, 2) }} paid</small></td>
                <td class="actions-cell"><a class="btn btn-light-outline" href="{{ route('invoices.show', $invoice) }}">Open</a></td>
            </tr>
        @empty
            <tr><td class="empty-table" colspan="7">No {{ $type }} invoices found. <a class="text-link" href="{{ route('invoices.create', $type) }}">Create one</a></td></tr>
        @endforelse
        </tbody>
    </table></div>
    {{ $invoices->links() }}
</section>
@endsection
