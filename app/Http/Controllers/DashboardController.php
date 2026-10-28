<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\Expense;
use App\Models\Invoice;
use App\Services\ExchangeRateService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use RuntimeException;

class DashboardController extends Controller
{
    public function __invoke(ExchangeRateService $exchangeRateService): View
    {
        $monthStart = now()->startOfMonth()->toDateString();
        $monthEnd = now()->endOfMonth()->toDateString();
        $sales = Invoice::where('type', 'sales')->whereBetween('issue_date', [$monthStart, $monthEnd]);
        $purchases = Invoice::where('type', 'purchase')->whereBetween('issue_date', [$monthStart, $monthEnd]);
        $trend = collect(range(5, 0))->map(function (int $offset): array {
            $month = Carbon::now()->startOfMonth()->subMonths($offset);

            return [
                'label' => $month->format('M'),
                'sales' => (float) Invoice::where('type', 'sales')->whereYear('issue_date', $month->year)->whereMonth('issue_date', $month->month)->sum('total'),
                'purchases' => (float) Invoice::where('type', 'purchase')->whereYear('issue_date', $month->year)->whereMonth('issue_date', $month->month)->sum('total'),
            ];
        });

        $exchangeRates = null;
        $exchangeRateError = null;
        try {
            $exchangeRates = $exchangeRateService->latest();
        } catch (ConnectionException|RuntimeException $exception) {
            report($exception);
            $exchangeRateError = 'Live currency rates are temporarily unavailable.';
        }

        return view('dashboard', [
            'monthlySales' => (float) (clone $sales)->sum('total'),
            'monthlyPurchases' => (float) (clone $purchases)->sum('total'),
            'receivables' => max(0, (float) Invoice::where('type', 'sales')->sum('total')
                - (float) Invoice::where('type', 'sales')->sum('amount_paid')),
            'monthlyExpenses' => (float) Expense::whereBetween('expense_date', [$monthStart, $monthEnd])->sum('amount'),
            'openInvoices' => Invoice::with(['customer', 'supplier'])->whereIn('status', ['pending', 'partial', 'overdue'])->latest('issue_date')->limit(6)->get(),
            'budgets' => Budget::withSum('expenses', 'amount')->orderByDesc('year')->orderByDesc('month')->limit(4)->get(),
            'trend' => $trend,
            'invoiceCount' => Invoice::whereBetween('issue_date', [$monthStart, $monthEnd])->count(),
            'exchangeRates' => $exchangeRates,
            'exchangeRateError' => $exchangeRateError,
        ]);
    }
}
