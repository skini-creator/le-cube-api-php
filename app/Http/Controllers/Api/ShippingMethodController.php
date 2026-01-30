<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ShippingMethod;
use Illuminate\Http\Request;

class ShippingMethodController extends Controller
{
    // Public - voir les méthodes disponibles
    public function index()
    {
        $methods = ShippingMethod::active()->get();
        return response()->json(['success' => true, 'shipping_methods' => $methods]);
    }

    // Admin - CRUD
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'description' => 'nullable|string',
            'cost' => 'required|numeric|min:0',
            'estimated_days_min' => 'nullable|integer',
            'estimated_days_max' => 'nullable|integer',
            'free_shipping_threshold' => 'nullable|numeric|min:0',
        ]);

        $method = ShippingMethod::create($validated);
        return response()->json(['success' => true, 'shipping_method' => $method], 201);
    }

    public function update(Request $request, $id)
    {
        $method = ShippingMethod::findOrFail($id);
        $method->update($request->all());
        return response()->json(['success' => true, 'shipping_method' => $method]);
    }

    public function destroy($id)
    {
        ShippingMethod::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Shipping method deleted']);
    }
}