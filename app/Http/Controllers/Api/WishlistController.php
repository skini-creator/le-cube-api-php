<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Wishlist;
use App\Http\Resources\ProductResource;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    public function index(Request $request)
    {
        $wishlist = $request->user()
            ->wishlist()
            ->with('product.primaryImage')
            ->get();

        return ProductResource::collection($wishlist->pluck('product'));
    }

    public function add(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        Wishlist::firstOrCreate([
            'user_id' => $request->user()->id,
            'product_id' => $validated['product_id'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Product added to wishlist'
        ]);
    }

    public function remove(Request $request, $id)
    {
        $wishlist = $request->user()
            ->wishlist()
            ->where('product_id', $id)
            ->firstOrFail();

        $wishlist->delete();

        return response()->json([
            'success' => true,
            'message' => 'Product removed from wishlist'
        ]);
    }
}