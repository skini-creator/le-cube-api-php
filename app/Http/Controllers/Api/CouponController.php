<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CouponResource;
use App\Models\Coupon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CouponController extends Controller
{
    /**
     * Validate coupon code
     */
    public function validate(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
            'subtotal' => 'required|numeric|min:0',
        ]);

        $coupon = Coupon::where('code', $request->code)
            ->active()
            ->first();

        if (!$coupon) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired coupon code'
            ], 404);
        }

        if (!$coupon->isValid()) {
            return response()->json([
                'success' => false,
                'message' => 'This coupon is no longer valid'
            ], 400);
        }

        // Check minimum purchase
        if ($coupon->minimum_purchase && $request->subtotal < $coupon->minimum_purchase) {
            return response()->json([
                'success' => false,
                'message' => "Minimum purchase of {$coupon->minimum_purchase} required"
            ], 400);
        }

        // Check usage limit per user
        if ($coupon->usage_limit_per_user && Auth::check()) {
            $userUsage = $coupon->usages()
                ->where('user_id', Auth::id())
                ->count();

            if ($userUsage >= $coupon->usage_limit_per_user) {
                return response()->json([
                    'success' => false,
                    'message' => 'You have reached the usage limit for this coupon'
                ], 400);
            }
        }

        $discount = $coupon->calculateDiscount($request->subtotal);

        return response()->json([
            'success' => true,
            'message' => 'Coupon is valid',
            'coupon' => new CouponResource($coupon),
            'discount' => $discount,
            'final_total' => $request->subtotal - $discount,
        ]);
    }

    /**
     * Get all coupons (Admin only)
     */
    public function index()
    {
        $coupons = Coupon::latest()->paginate(20);
        return CouponResource::collection($coupons);
    }

    /**
     * Create coupon (Admin only)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|unique:coupons,code',
            'description' => 'nullable|string',
            'type' => 'required|in:percentage,fixed',
            'value' => 'required|numeric|min:0',
            'minimum_purchase' => 'nullable|numeric|min:0',
            'maximum_discount' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'usage_limit_per_user' => 'nullable|integer|min:1',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after:starts_at',
            'applicable_categories' => 'nullable|array',
            'applicable_products' => 'nullable|array',
        ]);

        $validated['code'] = strtoupper($validated['code']);

        $coupon = Coupon::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Coupon created successfully',
            'coupon' => new CouponResource($coupon)
        ], 201);
    }

    /**
     * Update coupon (Admin only)
     */
    public function update(Request $request, $id)
    {
        $coupon = Coupon::findOrFail($id);

        $validated = $request->validate([
            'code' => 'sometimes|string|unique:coupons,code,' . $id,
            'description' => 'nullable|string',
            'type' => 'sometimes|in:percentage,fixed',
            'value' => 'sometimes|numeric|min:0',
            'minimum_purchase' => 'nullable|numeric|min:0',
            'maximum_discount' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'usage_limit_per_user' => 'nullable|integer|min:1',
            'is_active' => 'sometimes|boolean',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date',
            'applicable_categories' => 'nullable|array',
            'applicable_products' => 'nullable|array',
        ]);

        if (isset($validated['code'])) {
            $validated['code'] = strtoupper($validated['code']);
        }

        $coupon->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Coupon updated successfully',
            'coupon' => new CouponResource($coupon)
        ]);
    }

    /**
     * Delete coupon (Admin only)
     */
    public function destroy($id)
    {
        $coupon = Coupon::findOrFail($id);

        // Check if coupon is being used in active orders
        if ($coupon->orders()->whereIn('status', ['pending', 'processing'])->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete coupon that is used in active orders'
            ], 400);
        }

        $coupon->delete();

        return response()->json([
            'success' => true,
            'message' => 'Coupon deleted successfully'
        ]);
    }
}