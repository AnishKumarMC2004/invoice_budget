<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function index(string $type): View
    {
        $model = $this->model($type);
        $contacts = $model::query()
            ->when(request('search'), function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')->paginate(15)->withQueryString();

        return view('contacts.index', compact('contacts', 'type'));
    }

    public function create(string $type): View
    {
        $this->model($type);

        return view('contacts.form', compact('type'));
    }

    public function store(Request $request, string $type): RedirectResponse
    {
        $this->model($type)::create($this->validated($request));

        return redirect()->route($type.'.index')->with('success', ucfirst($type).' created.');
    }

    public function edit(int $contact, string $type): View
    {
        $item = $this->model($type)::findOrFail($contact);

        return view('contacts.form', compact('item', 'type'));
    }

    public function update(Request $request, int $contact, string $type): RedirectResponse
    {
        $this->model($type)::findOrFail($contact)->update($this->validated($request));

        return redirect()->route($type.'.index')->with('success', ucfirst($type).' updated.');
    }

    public function destroy(int $contact, string $type): RedirectResponse
    {
        $this->model($type)::findOrFail($contact)->delete();

        return back()->with('success', ucfirst($type).' deleted. Invoice records remain available.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['nullable', 'email', 'max:180'],
            'phone' => ['nullable', 'string', 'max:40'],
            'address' => ['nullable', 'string', 'max:1000'],
            'tax_number' => ['nullable', 'string', 'max:80'],
        ]);
    }

    private function model(string $type): string
    {
        return match ($type) {
            'customers' => Customer::class,
            'suppliers' => Supplier::class,
            default => abort(404),
        };
    }
}
