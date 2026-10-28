<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\Expense;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ExpenseController extends Controller
{
    public function index(): View
    {
        $expenses = Expense::with('budget')->latest('expense_date')->paginate(15);

        return view('expenses.index', compact('expenses'));
    }

    public function create(): View
    {
        return view('expenses.form', ['budgets' => Budget::orderByDesc('year')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        Expense::create($this->validated($request));

        return redirect()->route('expenses.index')->with('success', 'Expense recorded.');
    }

    public function edit(Expense $expense): View
    {
        return view('expenses.form', ['expense' => $expense, 'budgets' => Budget::orderByDesc('year')->get()]);
    }

    public function update(Request $request, Expense $expense): RedirectResponse
    {
        $expense->update($this->validated($request));

        return redirect()->route('expenses.index')->with('success', 'Expense updated.');
    }

    public function destroy(Expense $expense): RedirectResponse
    {
        $expense->delete();

        return back()->with('success', 'Expense deleted.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'budget_id' => ['nullable', 'exists:budgets,id'],
            'description' => ['required', 'string', 'max:180'],
            'category' => ['required', 'string', 'max:100'],
            'amount' => ['required', 'numeric', 'gt:0', 'max:9999999999'],
            'expense_date' => ['required', 'date'],
            'vendor' => ['nullable', 'string', 'max:150'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        if ($data['budget_id']) {
            $budget = Budget::findOrFail($data['budget_id']);
            $date = Carbon::parse($data['expense_date']);
            $matchesPeriod = (int) $budget->year === $date->year
                && ($budget->period === 'yearly' || (int) $budget->month === $date->month);
            if (! $matchesPeriod) {
                throw ValidationException::withMessages(['budget_id' => 'The selected budget does not cover this expense date.']);
            }
        }

        return $data;
    }
}
