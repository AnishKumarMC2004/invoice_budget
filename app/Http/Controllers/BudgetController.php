<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class BudgetController extends Controller
{
    public function index(): View
    {
        $budgets = Budget::withSum('expenses', 'amount')->orderByDesc('year')->orderByDesc('month')->paginate(15);

        return view('budgets.index', compact('budgets'));
    }

    public function create(): View
    {
        return view('budgets.form');
    }

    public function store(Request $request): RedirectResponse
    {
        Budget::create($this->validated($request));

        return redirect()->route('budgets.index')->with('success', 'Budget created.');
    }

    public function edit(Budget $budget): View
    {
        return view('budgets.form', compact('budget'));
    }

    public function update(Request $request, Budget $budget): RedirectResponse
    {
        $budget->update($this->validated($request));

        return redirect()->route('budgets.index')->with('success', 'Budget updated.');
    }

    public function destroy(Budget $budget): RedirectResponse
    {
        $budget->delete();

        return back()->with('success', 'Budget deleted. Linked expenses are retained.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'period' => ['required', Rule::in(['monthly', 'yearly'])],
            'year' => ['required', 'integer', 'between:2000,2100'],
            'month' => ['nullable', 'integer', 'between:1,12'],
            'amount' => ['required', 'numeric', 'gt:0', 'max:9999999999'],
            'category' => ['required', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        if ($data['period'] === 'monthly' && empty($data['month'])) {
            throw ValidationException::withMessages(['month' => 'Choose a month for a monthly budget.']);
        }
        if ($data['period'] === 'yearly') {
            $data['month'] = null;
        }

        return $data;
    }
}
