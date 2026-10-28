<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReportController;
use App\Services\ExchangeRateService;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->middleware('throttle:5,1')->name('login.store');
});

Route::middleware(['auth', 'admin'])->group(function () {
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/reports', ReportController::class)->name('reports.index');

    foreach (['customers', 'suppliers'] as $type) {
        Route::get("/{$type}", [ContactController::class, 'index'])->defaults('type', $type)->name("{$type}.index");
        Route::get("/{$type}/create", [ContactController::class, 'create'])->defaults('type', $type)->name("{$type}.create");
        Route::post("/{$type}", [ContactController::class, 'store'])->defaults('type', $type)->name("{$type}.store");
        Route::get("/{$type}/{contact}/edit", [ContactController::class, 'edit'])->defaults('type', $type)->name("{$type}.edit");
        Route::put("/{$type}/{contact}", [ContactController::class, 'update'])->defaults('type', $type)->name("{$type}.update");
        Route::delete("/{$type}/{contact}", [ContactController::class, 'destroy'])->defaults('type', $type)->name("{$type}.destroy");
    }

    Route::resource('products', ProductController::class)->except('show');
    Route::get('/invoices/{type}', [InvoiceController::class, 'index'])->whereIn('type', ['purchase', 'sales'])->name('invoices.index');
    Route::get('/invoices/{type}/create', [InvoiceController::class, 'create'])->whereIn('type', ['purchase', 'sales'])->name('invoices.create');
    Route::post('/invoices/{type}', [InvoiceController::class, 'store'])->whereIn('type', ['purchase', 'sales'])->name('invoices.store');
    Route::get('/invoice/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
    Route::get('/invoice/{invoice}/edit', [InvoiceController::class, 'edit'])->name('invoices.edit');
    Route::put('/invoice/{invoice}', [InvoiceController::class, 'update'])->name('invoices.update');
    Route::delete('/invoice/{invoice}', [InvoiceController::class, 'destroy'])->name('invoices.destroy');
    Route::post('/invoice/{invoice}/payments', [PaymentController::class, 'store'])->name('payments.store');
    Route::delete('/invoice/{invoice}/payments/{payment}', [PaymentController::class, 'destroy'])->name('payments.destroy');
    Route::resource('budgets', BudgetController::class)->except('show');
    Route::resource('expenses', ExpenseController::class)->except('show');
    Route::get('/api/exchange-rates', function (ExchangeRateService $rates) {
        return response()->json($rates->latest());
    })->name('exchange-rates');
});
