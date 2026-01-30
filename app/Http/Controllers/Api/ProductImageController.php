<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductImage;
use Illuminate\Http\Request;

class ProductImageController extends Controller
{
    public function setPrimary($id)
    {
        $image = ProductImage::findOrFail($id);
        
        // Retirer primary des autres images
        ProductImage::where('product_id', $image->product_id)
            ->update(['is_primary' => false]);
        
        $image->update(['is_primary' => true]);

        return response()->json(['success' => true, 'message' => 'Primary image updated']);
    }

    public function destroy($id)
    {
        $image = ProductImage::findOrFail($id);
        
        // Ne pas supprimer si c'est la seule image
        $count = ProductImage::where('product_id', $image->product_id)->count();
        if ($count <= 1) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete the only image'
            ], 400);
        }

        $image->delete();
        return response()->json(['success' => true, 'message' => 'Image deleted']);
    }

    public function reorder(Request $request, $productId)
    {
        $request->validate(['images' => 'required|array']);

        foreach ($request->images as $index => $imageId) {
            ProductImage::where('id', $imageId)
                ->where('product_id', $productId)
                ->update(['order' => $index]);
        }

        return response()->json(['success' => true, 'message' => 'Images reordered']);
    }
}