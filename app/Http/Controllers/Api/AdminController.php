<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Http\Resources\UserResource;
use App\Models\Order;
use App\Models\User;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class AdminController extends Controller
{
    /**
     * Get dashboard statistics
     */
    public function dashboard()
    {
        $totalRevenue = Order::where('payment_status', 'paid')->sum('total');
        $totalOrders = Order::count();
        $pendingOrders = Order::where('status', 'pending')->count();
        $totalProducts = Product::count();
        $totalUsers = User::where('role', 'customer')->count();
        $lowStockProducts = Product::where('stock_quantity', '<', 10)->count();

        // Recent orders
        $recentOrders = Order::with(['user', 'items'])
            ->latest()
            ->take(10)
            ->get();

        // Sales trend (last 7 days)
        $salesTrend = Order::where('payment_status', 'paid')
            ->where('created_at', '>=', now()->subDays(7))
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as orders'),
                DB::raw('SUM(total) as revenue')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Top selling products
        $topProducts = Product::withCount(['orderItems as total_sold' => function ($query) {
            $query->select(DB::raw('SUM(quantity)'));
        }])
            ->orderBy('total_sold', 'desc')
            ->take(10)
            ->get();

        return response()->json([
            'success' => true,
            'statistics' => [
                'total_revenue' => $totalRevenue,
                'total_orders' => $totalOrders,
                'pending_orders' => $pendingOrders,
                'total_products' => $totalProducts,
                'total_users' => $totalUsers,
                'low_stock_products' => $lowStockProducts,
            ],
            'recent_orders' => OrderResource::collection($recentOrders),
            'sales_trend' => $salesTrend,
            'top_products' => $topProducts,
        ]);
    }

    /**
     * Get detailed statistics
     */
    public function statistics(Request $request)
    {
        $period = $request->get('period', 'month'); // day, week, month, year

        $startDate = match ($period) {
            'day' => now()->startOfDay(),
            'week' => now()->startOfWeek(),
            'month' => now()->startOfMonth(),
            'year' => now()->startOfYear(),
            default => now()->startOfMonth(),
        };

        $revenue = Order::where('payment_status', 'paid')
            ->where('created_at', '>=', $startDate)
            ->sum('total');

        $orders = Order::where('created_at', '>=', $startDate)->count();

        $newCustomers = User::where('role', 'customer')
            ->where('created_at', '>=', $startDate)
            ->count();

        $averageOrderValue = $orders > 0 ? $revenue / $orders : 0;

        return response()->json([
            'success' => true,
            'period' => $period,
            'statistics' => [
                'revenue' => round($revenue, 2),
                'orders' => $orders,
                'new_customers' => $newCustomers,
                'average_order_value' => round($averageOrderValue, 2),
            ]
        ]);
    }

    /**
     * Get all users
     */
    public function users(Request $request)
    {
        $query = User::query();

        if ($request->has('role')) {
            $query->where('role', $request->role);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('email', 'like', "%{$request->search}%");
            });
        }

        $users = $query->latest()->paginate(20);

        return UserResource::collection($users);
    }

    /**
     * Get single user
     */
    public function userShow($id)
    {
        $user = User::with(['orders', 'addresses'])->findOrFail($id);
        return new UserResource($user);
    }

    /**
     * Update user
     */
    public function userUpdate(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $id,
            'role' => 'sometimes|in:customer,vendor,admin',
            'status' => 'sometimes|in:active,inactive,suspended',
        ]);

        $user->update($validated);

        return new UserResource($user);
    }

    /**
     * Delete user
     */
    public function userDestroy($id)
    {
        $user = User::findOrFail($id);

        if ($user->id === Auth::user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot delete your own account'
            ], 400);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully'
        ]);
    }

    /**
     * Get all orders
     */
    public function orders(Request $request)
    {
        $query = Order::with(['user', 'items.product']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        if ($request->has('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('order_number', 'like', "%{$request->search}%")
                  ->orWhereHas('user', function ($q2) use ($request) {
                      $q2->where('name', 'like', "%{$request->search}%");
                  });
            });
        }

        $orders = $query->latest()->paginate(20);

        return OrderResource::collection($orders);
    }

    /**
     * Get single order
     */
    public function orderShow($id)
    {
        $order = Order::with([
            'user',
            'items.product',
            'shippingAddress',
            'billingAddress',
            'payments'
        ])->findOrFail($id);

        return new OrderResource($order);
    }

    /**
     * Update order status
     */
    public function updateOrderStatus(Request $request, $id)
    {
        $order = Order::findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|in:pending,processing,shipped,delivered,cancelled,refunded',
            'tracking_number' => 'nullable|string',
        ]);

        $order->update($validated);

        if ($validated['status'] === 'shipped' && !$order->shipped_at) {
            $order->markAsShipped($validated['tracking_number'] ?? null);
        }

        if ($validated['status'] === 'delivered' && !$order->delivered_at) {
            $order->markAsDelivered();
        }

        return new OrderResource($order->fresh());
    }

    /**
     * Get pending reviews
     */
    public function pendingReviews()
    {
        $reviews = Review::with(['user', 'product'])
            ->pending()
            ->latest()
            ->paginate(20);

        return response()->json([
            'success' => true,
            'reviews' => $reviews
        ]);
    }

    /**
     * Approve review
     */
    public function approveReview($id)
    {
        $review = Review::findOrFail($id);
        $review->update(['is_approved' => true]);
        $review->product->updateRating();

        return response()->json([
            'success' => true,
            'message' => 'Review approved successfully'
        ]);
    }

    /**
     * Reject review
     */
    public function rejectReview($id)
    {
        $review = Review::findOrFail($id);
        $review->delete();

        return response()->json([
            'success' => true,
            'message' => 'Review rejected and deleted'
        ]);
    }

    /**
     * Sales report
     */
    public function salesReport(Request $request)
    {
        $startDate = $request->get('start_date', now()->startOfMonth());
        $endDate = $request->get('end_date', now()->endOfMonth());

        $sales = Order::where('payment_status', 'paid')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as orders_count'),
                DB::raw('SUM(total) as total_revenue'),
                DB::raw('AVG(total) as average_order_value')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json([
            'success' => true,
            'period' => [
                'start' => $startDate,
                'end' => $endDate,
            ],
            'sales' => $sales,
            'summary' => [
                'total_orders' => $sales->sum('orders_count'),
                'total_revenue' => $sales->sum('total_revenue'),
                'average_order_value' => $sales->avg('average_order_value'),
            ]
        ]);
    }

    /**
     * Products report
     */
    public function productsReport()
    {
        $totalProducts = Product::count();
        $activeProducts = Product::active()->count();
        $outOfStock = Product::where('stock_quantity', 0)->count();
        $lowStock = Product::where('stock_quantity', '>', 0)
            ->where('stock_quantity', '<', 10)
            ->count();

        $topSelling = Product::withCount(['orderItems as units_sold' => function ($query) {
            $query->select(DB::raw('SUM(quantity)'));
        }])
            ->withSum('orderItems as total_revenue', DB::raw('quantity * price'))
            ->orderBy('units_sold', 'desc')
            ->take(20)
            ->get();

        return response()->json([
            'success' => true,
            'summary' => [
                'total_products' => $totalProducts,
                'active_products' => $activeProducts,
                'out_of_stock' => $outOfStock,
                'low_stock' => $lowStock,
            ],
            'top_selling' => $topSelling,
        ]);
    }

    /**
     * Customers report
     */
    public function customersReport()
    {
        $totalCustomers = User::customers()->count();
        $newCustomers = User::customers()
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();

        $topCustomers = User::customers()
            ->withCount('orders')
            ->withSum('orders as total_spent', 'total')
            ->orderBy('total_spent', 'desc')
            ->take(20)
            ->get();

        return response()->json([
            'success' => true,
            'summary' => [
                'total_customers' => $totalCustomers,
                'new_this_month' => $newCustomers,
            ],
            'top_customers' => UserResource::collection($topCustomers),
        ]);
    }
}