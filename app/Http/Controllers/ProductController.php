<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(): View
    {
        $products = Product::when(request('search'), function ($query, string $search): void {
            $query->where(function ($query) use ($search): void {
                $query->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%");
            });
        })->orderBy('name')->paginate(15)->withQueryString();

        return view('products.index', compact('products'));
    }

    public function create(): View
    {
        return view('products.form');
    }

    public function store(Request $request): RedirectResponse
    {
        Product::create($this->validated($request));

        return redirect()->route('products.index')->with('success', 'Product or service created.');
    }

    public function edit(Product $product): View
    {
        return view('products.form', compact('product'));
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $product->update($this->validated($request, $product));

        return redirect()->route('products.index')->with('success', 'Product or service updated.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();

        return back()->with('success', 'Product or service deleted.');
    }

    private function validated(Request $request, ?Product $product = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'sku' => ['nullable', 'string', 'max:60', Rule::unique('products', 'sku')->ignore($product)],
            'type' => ['required', Rule::in(['product', 'service'])],
            'description' => ['nullable', 'string', 'max:2000'],
            'unit_price' => ['required', 'numeric', 'min:0', 'max:9999999999'],
            'tax_rate' => ['required', 'numeric', 'between:0,100'],
        ]);
    }
}
