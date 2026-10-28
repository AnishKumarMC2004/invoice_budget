@extends('layouts.app')
@section('title', ucfirst($type))
@section('content')
<div class="page-heading"><div><div class="eyebrow">DIRECTORY / {{ strtoupper($type) }}</div><h1>{{ ucfirst($type) }}</h1><p>Manage your business relationships and billing details.</p></div><a class="btn btn-primary" href="{{ route($type.'.create') }}"><i class="bi bi-plus-lg"></i> Add {{ rtrim($type, 's') }}</a></div>
<section class="content-panel">
    <div class="table-toolbar">
        <form class="filter-row w-100" method="GET" action="{{ route($type.'.index') }}"><div class="search-form"><input class="form-control" name="search" value="{{ request('search') }}" placeholder="Search name or email"><button class="btn btn-light-outline" type="submit"><i class="bi bi-search"></i></button></div><span class="table-caption ms-auto"><strong>{{ $contacts->total() }}</strong> contacts</span></form>
    </div>
    <div class="table-scroll"><table class="records-table"><thead><tr><th>NAME</th><th>EMAIL</th><th>PHONE</th><th>TAX NUMBER</th><th>ADDED</th><th></th></tr></thead><tbody>
    @forelse($contacts as $contact)
        <tr><td><strong>{{ $contact->name }}</strong><small>{{ $contact->address ?: 'No address added' }}</small></td><td>{{ $contact->email ?: '—' }}</td><td>{{ $contact->phone ?: '—' }}</td><td>{{ $contact->tax_number ?: '—' }}</td><td>{{ $contact->created_at->format('M j, Y') }}</td><td class="actions-cell"><a class="btn btn-light-outline" href="{{ route($type.'.edit', $contact->id) }}">Edit</a><form class="d-inline" method="POST" action="{{ route($type.'.destroy', $contact->id) }}" data-confirm="Delete this contact? Existing invoices will keep their financial details.">@csrf @method('DELETE')<button class="btn btn-light-outline text-danger" type="submit">Delete</button></form></td></tr>
    @empty<tr><td colspan="6" class="empty-table">No {{ $type }} found. <a class="text-link" href="{{ route($type.'.create') }}">Add your first {{ rtrim($type, 's') }}</a>.</td></tr>@endforelse
    </tbody></table></div>{{ $contacts->links() }}
</section>
@endsection
