<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ProductImageControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function createVendorAndProduct()
    {
        /** @var \App\Models\User $vendor */
        $vendor = \App\Models\User::factory()->create(['role' => 'vendor']);
        $this->actingAs($vendor, 'api');
        $product = \App\Models\Product::factory()->create(['user_id' => $vendor->id]);
        return [$vendor, $product];
    }

    public function test_vendor_can_set_primary_image()
    {
        [$vendor, $product] = $this->createVendorAndProduct();
        $image1 = \App\Models\ProductImage::factory()->create(['product_id' => $product->id, 'is_primary' => true]);
        $image2 = \App\Models\ProductImage::factory()->create(['product_id' => $product->id, 'is_primary' => false]);

        $response = $this->postJson('/api/product-images/' . $image2->id . '/set-primary');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('product_images', ['id' => $image2->id, 'is_primary' => true]);
        $this->assertDatabaseHas('product_images', ['id' => $image1->id, 'is_primary' => false]);
    }

    public function test_vendor_can_delete_an_image()
    {
        [$vendor, $product] = $this->createVendorAndProduct();
        $image1 = \App\Models\ProductImage::factory()->create(['product_id' => $product->id]);
        $image2 = \App\Models\ProductImage::factory()->create(['product_id' => $product->id]);

        $response = $this->deleteJson('/api/product-images/' . $image1->id);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('product_images', ['id' => $image1->id]);
    }

    public function test_vendor_can_reorder_images()
    {
        [$vendor, $product] = $this->createVendorAndProduct();
        $image1 = \App\Models\ProductImage::factory()->create(['product_id' => $product->id, 'order' => 0]);
        $image2 = \App\Models\ProductImage::factory()->create(['product_id' => $product->id, 'order' => 1]);

        $reorderData = ['images' => [$image2->id, $image1->id]];

        $response = $this->postJson('/api/products/' . $product->id . '/reorder-images', $reorderData);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('product_images', ['id' => $image1->id, 'order' => 1]);
        $this->assertDatabaseHas('product_images', ['id' => $image2->id, 'order' => 0]);
    }
}
