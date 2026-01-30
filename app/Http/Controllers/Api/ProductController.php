<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductController extends Controller
{
    /**
     * Display a listing of products
     */
    public function index(Request $request)
    {
        $query = Product::with(['category', 'images', 'primaryImage'])
            ->active();

        // Search
        if ($request->has('search')) {
            $query->search($request->search);
        }

        // Category filter
        if ($request->has('category')) {
            $query->byCategory($request->category);
        }

        // Price range filter
        if ($request->has('min_price') && $request->has('max_price')) {
            $query->priceRange($request->min_price, $request->max_price);
        }

        // Featured products
        if ($request->has('featured')) {
            $query->featured();
        }

        // On sale products
        if ($request->has('on_sale')) {
            $query->onSale();
        }

        // In stock only
        if ($request->has('in_stock')) {
            $query->inStock();
        }

        // Sorting
        $sortBy = $request->get('sort', 'created_at');
        $order = $request->get('order', 'desc');
        
        $allowedSorts = ['name', 'price', 'created_at', 'average_rating', 'views_count'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $order);
        }

        $perPage = $request->get('per_page', 15);
        $products = $query->paginate($perPage);

        return ProductResource::collection($products);
    }

    /**
     * Display the specified product
     */
    public function show($id)
    {
        $product = Product::with([
            'category',
            'images',
            'variants',
            'reviews' => function ($query) {
                $query->where('is_approved', true)
                    ->with('user')
                    ->latest()
                    ->take(10);
            }
        ])->findOrFail($id);

        // Increment views
        $product->incrementViews();

        return new ProductResource($product);
    }

    /**
     * Store a newly created product
     */
    public function store(Request $request)
    {
        // ✅ Vérification simple (déjà protégé par le middleware role:vendor,admin dans les routes)
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'short_description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0|lt:price',
            'stock_quantity' => 'required|integer|min:0',
            'sku' => 'required|string|unique:products,sku',
            'category_id' => 'required|exists:categories,id',
            'weight' => 'nullable|numeric',
            'dimensions' => 'nullable|array',
            'images' => 'nullable|array',
            'images.*' => 'image|max:2048',
        ]);

        $validated['slug'] = \Illuminate\Support\Str::slug($request->name);
        $validated['user_id'] = Auth::id();

        $product = Product::create($validated);

        // Handle images
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $index => $image) {
                $path = $image->store('products', 'public');
                $product->images()->create([
                    'image_path' => $path,
                    'is_primary' => $index === 0,
                    'order' => $index,
                ]);
            }
        }

        return new ProductResource($product->load('images', 'category'));
    }

    /**
     * Update the specified product
     */
    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);
        
        // ✅ Vérification : seul le propriétaire ou admin peut modifier
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        if ($product->user_id !== $user->id && !$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'short_description' => 'nullable|string',
            'price' => 'sometimes|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0',
            'stock_quantity' => 'sometimes|integer|min:0',
            'category_id' => 'sometimes|exists:categories,id',
            'is_active' => 'sometimes|boolean',
            'is_featured' => 'sometimes|boolean',
        ]);

        if (isset($validated['name'])) {
            $validated['slug'] = \Illuminate\Support\Str::slug($validated['name']);
        }

        $product->update($validated);

        return new ProductResource($product->load('images', 'category'));
    }

    /**
     * Remove the specified product
     */
    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        
        // ✅ Vérification : seul le propriétaire ou admin peut supprimer
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        if ($product->user_id !== $user->id && !$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $product->delete();

        return response()->json([
            'success' => true,
            'message' => 'Product deleted successfully'
        ]);
    }

    /**
     * Get featured products
     */
    public function featured()
    {
        $products = Product::with(['category', 'primaryImage'])
            ->active()
            ->featured()
            ->inStock()
            ->take(12)
            ->get();

        return ProductResource::collection($products);
    }

    /**
     * Get related products
     */
    public function related($id)
    {
        $product = Product::findOrFail($id);

        $relatedProducts = Product::with(['category', 'primaryImage'])
            ->active()
            ->inStock()
            ->where('id', '!=', $id)
            ->where('category_id', $product->category_id)
            ->take(8)
            ->get();

        return ProductResource::collection($relatedProducts);
    }
}