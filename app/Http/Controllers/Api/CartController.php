<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    /**
     * Get user cart
     */
    public function index()
    {
        $cart = $this->getOrCreateCart();
        $cart->load(['items.product.primaryImage', 'items.variant']);

        return response()->json([
            'success' => true,
            'cart' => $cart,
            'subtotal' => $this->calculateSubtotal($cart),
            'items_count' => $cart->items->count()
        ]);
    }

    /**
     * Add item to cart
     */
    public function add(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'variant_id' => 'nullable|exists:product_variants,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $product = Product::findOrFail($validated['product_id']);

        // Check stock
        if ($product->stock_quantity < $validated['quantity']) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient stock'
            ], 400);
        }

        $cart = $this->getOrCreateCart();

        // Check if item already exists
        $cartItem = $cart->items()
            ->where('product_id', $validated['product_id'])
            ->where('variant_id', $validated['variant_id'] ?? null)
            ->first();

        if ($cartItem) {
            // Update quantity
            $newQuantity = $cartItem->quantity + $validated['quantity'];
            
            if ($product->stock_quantity < $newQuantity) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient stock'
                ], 400);
            }

            $cartItem->update(['quantity' => $newQuantity]);
        } else {
            // Create new cart item
            $price = $product->sale_price ?? $product->price;
            
            $cartItem = $cart->items()->create([
                'product_id' => $validated['product_id'],
                'variant_id' => $validated['variant_id'] ?? null,
                'quantity' => $validated['quantity'],
                'price' => $price,
            ]);
        }

        $cart->load(['items.product.primaryImage', 'items.variant']);

        return response()->json([
            'success' => true,
            'message' => 'Item added to cart',
            'cart' => $cart,
            'subtotal' => $this->calculateSubtotal($cart),
        ]);
    }

    /**
     * Update cart item quantity
     */
    public function update(Request $request, $itemId)
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $cart = $this->getOrCreateCart();
        $cartItem = $cart->items()->findOrFail($itemId);

        // Check stock
        if ($cartItem->product->stock_quantity < $validated['quantity']) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient stock'
            ], 400);
        }

        $cartItem->update($validated);

        $cart->load(['items.product.primaryImage', 'items.variant']);

        return response()->json([
            'success' => true,
            'message' => 'Cart updated',
            'cart' => $cart,
            'subtotal' => $this->calculateSubtotal($cart),
        ]);
    }

    /**
     * Remove item from cart
     */
    public function remove($itemId)
    {
        $cart = $this->getOrCreateCart();
        $cartItem = $cart->items()->findOrFail($itemId);
        $cartItem->delete();

        $cart->load(['items.product.primaryImage', 'items.variant']);

        return response()->json([
            'success' => true,
            'message' => 'Item removed from cart',
            'cart' => $cart,
            'subtotal' => $this->calculateSubtotal($cart),
        ]);
    }

    /**
     * Clear cart
     */
    public function clear()
    {
        $cart = $this->getOrCreateCart();
        $cart->items()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Cart cleared'
        ]);
    }

    /**
     * Apply coupon to cart
     */
    public function applyCoupon(Request $request)
    {
        $request->validate([
            'coupon_code' => 'required|string',
        ]);

        $cart = $this->getOrCreateCart();
        $subtotal = $this->calculateSubtotal($cart);

        // Validate coupon (implement coupon validation logic)
        $coupon = \App\Models\Coupon::where('code', $request->coupon_code)
            ->where('is_active', true)
            ->first();

        if (!$coupon) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid coupon code'
            ], 400);
        }

        // Calculate discount
        $discount = $coupon->calculateDiscount($subtotal);

        return response()->json([
            'success' => true,
            'message' => 'Coupon applied',
            'discount' => $discount,
            'subtotal' => $subtotal,
            'total' => $subtotal - $discount
        ]);
    }

    /**
     * Get or create cart for current user
     */
    private function getOrCreateCart()
    {
        $user = Auth::user();

        if ($user) {
            return Cart::firstOrCreate(['user_id' => $user->id]);
        }

        // For guest users, use session
        $sessionId = session()->getId();
        return Cart::firstOrCreate(['session_id' => $sessionId]);
    }

    /**
     * Calculate cart subtotal
     */
    private function calculateSubtotal($cart)
    {
        return $cart->items->sum(function ($item) {
            return $item->price * $item->quantity;
        });
    }
}