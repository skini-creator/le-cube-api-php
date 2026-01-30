<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductVariant;
use Illuminate\Http\Request;

class ProductVariantController extends Controller
{
    public function index($productId)
    {
        $variants = ProductVariant::where('product_id', $productId)
            ->active()
            ->get();

        return response()->json(['success' => true, 'variants' => $variants]);
    }

    public function store(Request $request, $productId)
    {
        $validated = $request->validate([
            'sku' => 'required|string|unique:product_variants',
            'name' => 'required|string',
            'attributes' => 'required|array',
            'price' => 'required|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
        ]);

        $validated['product_id'] = $productId;
        $variant = ProductVariant::create($validated);

        return response()->json(['success' => true, 'variant' => $variant], 201);
    }

    public function update(Request $request, $id)
    {
        $variant = ProductVariant::findOrFail($id);
        $variant->update($request->all());
        return response()->json(['success' => true, 'variant' => $variant]);
    }

    public function destroy($id)
    {
        ProductVariant::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Variant deleted']);
    }
}