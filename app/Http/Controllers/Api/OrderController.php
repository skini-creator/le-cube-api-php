<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\Cart;
use App\Models\Coupon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    /**
     * Get user orders
     */
    public function index()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        $orders = $user->orders()
            ->with(['items.product', 'shippingAddress'])
            ->latest()
            ->paginate(15);

        return OrderResource::collection($orders);
    }

    /**
     * Get single order
     */
    public function show($id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        $order = $user->orders()
            ->with(['items.product.primaryImage', 'shippingAddress', 'billingAddress', 'coupon'])
            ->findOrFail($id);

        return new OrderResource($order);
    }

    /**
     * Create new order
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'shipping_address_id' => 'required|exists:addresses,id',
            'billing_address_id' => 'nullable|exists:addresses,id',
            'payment_method' => 'required|string|in:stripe,paypal,cash_on_delivery',
            'coupon_code' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();
        $cart = $user->cart;

        if (!$cart || $cart->items->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Cart is empty'
            ], 400);
        }

        // Verify stock availability
        foreach ($cart->items as $item) {
            if ($item->product->stock_quantity < $item->quantity) {
                return response()->json([
                    'success' => false,
                    'message' => "Insufficient stock for {$item->product->name}"
                ], 400);
            }
        }

        // Calculate totals
        $subtotal = $cart->items->sum(fn($item) => $item->price * $item->quantity);
        $shippingCost = $this->calculateShipping($subtotal);
        $tax = $subtotal * config('app.tax_rate', 0.20);
        
        $discount = 0;
        $couponId = null;

        // Apply coupon if provided
        if ($request->coupon_code) {
            $coupon = Coupon::where('code', $request->coupon_code)
                ->where('is_active', true)
                ->first();

            if ($coupon && $coupon->isValid()) {
                $discount = $coupon->calculateDiscount($subtotal);
                $couponId = $coupon->id;
            }
        }

        $total = $subtotal + $tax + $shippingCost - $discount;

        // Create order
        $order = Order::create([
            'order_number' => Order::generateOrderNumber(),
            'user_id' => $user->id,
            'shipping_address_id' => $validated['shipping_address_id'],
            'billing_address_id' => $validated['billing_address_id'] ?? $validated['shipping_address_id'],
            'payment_method' => $validated['payment_method'],
            'subtotal' => $subtotal,
            'tax' => $tax,
            'shipping_cost' => $shippingCost,
            'discount' => $discount,
            'total' => $total,
            'coupon_id' => $couponId,
            'notes' => $validated['notes'] ?? null,
            'status' => 'pending',
            'payment_status' => 'pending',
        ]);

        // Create order items and reduce stock
        foreach ($cart->items as $cartItem) {
            $order->items()->create([
                'product_id' => $cartItem->product_id,
                'variant_id' => $cartItem->variant_id,
                'product_name' => $cartItem->product->name,
                'product_sku' => $cartItem->product->sku,
                'variant_details' => $cartItem->variant ? $cartItem->variant->attributes : null,
                'quantity' => $cartItem->quantity,
                'price' => $cartItem->price,
                'total' => $cartItem->price * $cartItem->quantity,
            ]);

            // Reduce product stock
            $cartItem->product->decrementStock($cartItem->quantity);
        }

        // Clear cart
        $cart->items()->delete();

        // Update coupon usage
        if (isset($coupon) && $coupon) {
            $coupon->increment('usage_count');
            $coupon->usages()->create([
                'user_id' => $user->id,
                'order_id' => $order->id,
            ]);
        }

        // TODO: Process payment based on payment_method
        // For now, if cash_on_delivery, mark as pending
        // Otherwise, redirect to payment gateway

        $order->load(['items.product', 'shippingAddress']);

        return response()->json([
            'success' => true,
            'message' => 'Order created successfully',
            'order' => new OrderResource($order),
        ], 201);
    }

    /**
     * Cancel order
     */
    public function cancel($id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        $order = $user->orders()->findOrFail($id);

        if (!$order->canBeCancelled()) {
            return response()->json([
                'success' => false,
                'message' => 'Order cannot be cancelled'
            ], 400);
        }

        $order->cancel();

        return response()->json([
            'success' => true,
            'message' => 'Order cancelled successfully',
            'order' => new OrderResource($order->fresh())
        ]);
    }

    /**
     * Download invoice
     */
    public function invoice($id)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        $order = $user->orders()
            ->with(['items.product', 'shippingAddress', 'billingAddress'])
            ->findOrFail($id);

        // TODO: Generate PDF invoice
        // For now, return order data
        return response()->json([
            'success' => true,
            'order' => new OrderResource($order)
        ]);
    }

    /**
     * Track order
     */
    public function track($orderNumber)
    {
        $order = Order::where('order_number', $orderNumber)
            ->with(['items.product', 'shippingAddress'])
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'order' => [
                'order_number' => $order->order_number,
                'status' => $order->status,
                'tracking_number' => $order->tracking_number,
                'shipped_at' => $order->shipped_at,
                'delivered_at' => $order->delivered_at,
            ]
        ]);
    }

    /**
     * Calculate shipping cost
     */
    private function calculateShipping($subtotal)
    {
        $freeShippingThreshold = config('app.free_shipping_threshold', 100);
        
        if ($subtotal >= $freeShippingThreshold) {
            return 0;
        }

        return 10; // Default shipping cost
    }
}