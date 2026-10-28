<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function __invoke(Request $request): View
    {
        $year = (int) $request->query('year', now()->year);
        abort_unless($year >= 2000 && $year <= 2100, 422);

        $monthlyData = [];
        foreach (range(1, 12) as $month) {
            $monthlyData[$month] = [
                'month' => now()->setYear($year)->month($month)->format('M'),
                'sales' => 0.0,
                'purchases' => 0.0,
                'expenses' => 0.0,
                'net' => 0.0,
            ];
        }
        $invoicesForYear = Invoice::whereBetween('issue_date', ["{$year}-01-01", "{$year}-12-31"])->get(['issue_date', 'type', 'total']);
        foreach ($invoicesForYear as $invoice) {
            $month = $invoice->issue_date->month;
            $key = $invoice->type === 'sales' ? 'sales' : 'purchases';
            $monthlyData[$month][$key] += (float) $invoice->total;
        }
        $expensesForYear = Expense::whereBetween('expense_date', ["{$year}-01-01", "{$year}-12-31"])->get(['expense_date', 'amount']);
        foreach ($expensesForYear as $expense) {
            $monthlyData[$expense->expense_date->month]['expenses'] += (float) $expense->amount;
        }
        $months = collect($monthlyData)->values()->map(function (array $month): array {
            $month['net'] = $month['sales'] - $month['purchases'] - $month['expenses'];

            return $month;
        });
        $invoices = Invoice::whereYear('issue_date', $year);

        return view('reports.index', [
            'year' => $year,
            'months' => $months,
            'salesTotal' => (float) (clone $invoices)->where('type', 'sales')->sum('total'),
            'purchaseTotal' => (float) (clone $invoices)->where('type', 'purchase')->sum('total'),
            'expenseTotal' => (float) Expense::whereYear('expense_date', $year)->sum('amount'),
            'invoiceCount' => (clone $invoices)->count(),
        ]);
    }
}
