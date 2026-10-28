<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Overview') · Finora</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('css/finance.css') }}" rel="stylesheet">
</head>
<body>
<div class="app-shell">
    <aside class="sidebar" id="sidebar">
        <a class="brand" href="{{ route('dashboard') }}">
            <span class="brand-mark"><i class="bi bi-layers-half"></i></span>
            <span>finora<span class="brand-dot">.</span><small>FINANCE STUDIO</small></span>
        </a>
        <div class="nav-caption">WORKSPACE</div>
        <nav class="side-nav">
            <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}"><i class="bi bi-grid-1x2"></i><span>Overview</span></a>
            <div class="nav-caption nav-caption-gap">INVOICING</div>
            <a href="{{ route('invoices.index', 'sales') }}" class="{{ request()->fullUrlIs('*invoices/sales*') || request()->routeIs('invoices.show','invoices.edit') && isset($invoice) && $invoice->type === 'sales' ? 'active' : '' }}"><i class="bi bi-arrow-up-right-circle"></i><span>Sales invoices</span></a>
            <a href="{{ route('invoices.index', 'purchase') }}" class="{{ request()->fullUrlIs('*invoices/purchase*') || request()->routeIs('invoices.show','invoices.edit') && isset($invoice) && $invoice->type === 'purchase' ? 'active' : '' }}"><i class="bi bi-arrow-down-left-circle"></i><span>Purchase invoices</span></a>
            <a href="{{ route('customers.index') }}" class="{{ request()->routeIs('customers.*') ? 'active' : '' }}"><i class="bi bi-people"></i><span>Customers</span></a>
            <a href="{{ route('suppliers.index') }}" class="{{ request()->routeIs('suppliers.*') ? 'active' : '' }}"><i class="bi bi-truck"></i><span>Suppliers</span></a>
            <a href="{{ route('products.index') }}" class="{{ request()->routeIs('products.*') ? 'active' : '' }}"><i class="bi bi-box-seam"></i><span>Products & services</span></a>
            <div class="nav-caption nav-caption-gap">PLANNING</div>
            <a href="{{ route('budgets.index') }}" class="{{ request()->routeIs('budgets.*') ? 'active' : '' }}"><i class="bi bi-pie-chart"></i><span>Budgets</span></a>
            <a href="{{ route('expenses.index') }}" class="{{ request()->routeIs('expenses.*') ? 'active' : '' }}"><i class="bi bi-receipt"></i><span>Expenses</span></a>
            <a href="{{ route('reports.index') }}" class="{{ request()->routeIs('reports.*') ? 'active' : '' }}"><i class="bi bi-bar-chart-line"></i><span>Reports</span></a>
        </nav>
        <div class="sidebar-bottom">
            <div class="help-card"><span class="help-icon"><i class="bi bi-lightning-charge"></i></span><strong>Good money habits<br>start here.</strong><p>Keep your finances clear and your business moving.</p></div>
            <div class="user-chip">
                <span class="avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                <span class="user-name">{{ auth()->user()->name }}<small>Administrator</small></span>
                <form method="POST" action="{{ route('logout') }}">@csrf<button class="icon-button logout-button" title="Sign out"><i class="bi bi-box-arrow-right"></i></button></form>
            </div>
        </div>
    </aside>
    <main class="main-area">
        <header class="topbar">
            <button type="button" class="icon-button mobile-menu" id="menuToggle" aria-label="Toggle menu"><i class="bi bi-list"></i></button>
            <div class="breadcrumb-line"><span>Workspace</span><i class="bi bi-chevron-right"></i><strong>@yield('title', 'Overview')</strong></div>
            <div class="topbar-right">
                <span class="today-label"><i class="bi bi-calendar3"></i> {{ now()->format('D, M j, Y') }}</span>
                <span class="top-avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
            </div>
        </header>
        <section class="page-content">
            @if(session('success'))<div class="alert alert-success app-alert"><i class="bi bi-check-circle-fill"></i>{{ session('success') }}<button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button></div>@endif
            @if($errors->any())<div class="alert alert-danger app-alert"><i class="bi bi-exclamation-circle-fill"></i><div><strong>Please review the form.</strong><ul class="mb-0 mt-1">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div><button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button></div>@endif
            @yield('content')
        </section>
    </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('menuToggle')?.addEventListener('click', () => document.getElementById('sidebar').classList.toggle('sidebar-open'));
document.querySelectorAll('[data-confirm]').forEach(form => form.addEventListener('submit', event => {
    if (!confirm(form.dataset.confirm)) event.preventDefault();
}));
</script>
@stack('scripts')
</body>
</html>
