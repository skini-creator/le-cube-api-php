<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReviewResource;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReviewController extends Controller
{
    public function productReviews($productId)
    {
        $reviews = Review::with('user')
            ->where('product_id', $productId)
            ->where('is_approved', true)
            ->latest()
            ->paginate(15);

        return ReviewResource::collection($reviews);
    }

    public function store(Request $request, $productId)
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'required|string',
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Optional: Check if user has purchased the product
        // if (!$user->hasPurchasedProduct($productId)) {
        //     return response()->json(['success' => false, 'message' => 'You can only review products you have purchased.'], 403);
        // }

        $review = Review::create([
            'user_id' => $user->id,
            'product_id' => $productId,
            'rating' => $request->rating,
            'comment' => $request->comment,
            'is_approved' => false, // Reviews require admin approval
        ]);

        return response()->json(['success' => true, 'message' => 'Review submitted for approval.', 'review' => new ReviewResource($review)], 201);
    }

    public function update(Request $request, $id)
    {
        $review = Review::findOrFail($id);

        if ($review->user_id !== Auth::id()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'rating' => 'sometimes|integer|min:1|max:5',
            'comment' => 'sometimes|string',
        ]);

        $review->update($request->only('rating', 'comment'));

        return response()->json(['success' => true, 'message' => 'Review updated.', 'review' => new ReviewResource($review)]);
    }

    public function destroy($id)
    {
        $review = Review::findOrFail($id);
        
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($review->user_id !== $user->id && !$user->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $review->delete();

        return response()->json(['success' => true, 'message' => 'Review deleted.']);
    }

    public function markHelpful($id)
    {
        $review = Review::findOrFail($id);
        $review->increment('helpful_count');

        return response()->json(['success' => true, 'message' => 'Review marked as helpful.']);
    }
}
