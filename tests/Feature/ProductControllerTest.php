<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ProductControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_get_all_products()
    {
        \App\Models\Product::factory()->count(5)->create();

        $response = $this->getJson('/api/products');

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data');
    }

    public function test_can_get_a_single_product()
    {
        $product = \App\Models\Product::factory()->create();

        $response = $this->getJson('/api/products/' . $product->id);

        $response->assertStatus(200)
            ->assertJson(['data' => ['id' => $product->id]]);
    }

    public function test_vendor_can_create_a_product()
    {
        /** @var \App\Models\User $vendor */
        $vendor = \App\Models\User::factory()->create(['role' => 'vendor']);
        $this->actingAs($vendor, 'api');
        $category = \App\Models\Category::factory()->create();

        $productData = [
            'name' => 'New Product',
            'description' => 'Product description',
            'price' => 100,
            'stock_quantity' => 10,
            'sku' => 'NP-01',
            'category_id' => $category->id,
        ];

        $response = $this->postJson('/api/products', $productData);

        $response->assertStatus(201)
            ->assertJsonFragment(['name' => 'New Product']);

        $this->assertDatabaseHas('products', ['sku' => 'NP-01']);
    }

    public function test_vendor_can_update_their_product()
    {
        /** @var \App\Models\User $vendor */
        $vendor = \App\Models\User::factory()->create(['role' => 'vendor']);
        $this->actingAs($vendor, 'api');
        $product = \App\Models\Product::factory()->create(['user_id' => $vendor->id]);

        $updateData = ['name' => 'Updated Product'];

        $response = $this->putJson('/api/products/' . $product->id, $updateData);

        $response->assertStatus(200)
            ->assertJsonFragment($updateData);

        $this->assertDatabaseHas('products', ['id' => $product->id, 'name' => 'Updated Product']);
    }

    public function test_vendor_can_delete_their_product()
    {
        /** @var \App\Models\User $vendor */
        $vendor = \App\Models\User::factory()->create(['role' => 'vendor']);
        $this->actingAs($vendor, 'api');
        $product = \App\Models\Product::factory()->create(['user_id' => $vendor->id]);

        $response = $this->deleteJson('/api/products/' . $product->id);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }

    public function test_can_get_featured_products()
    {
        \App\Models\Product::factory()->count(3)->create(['is_featured' => true]);

        $response = $this->getJson('/api/products/featured');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_can_get_related_products()
    {
        $category = \App\Models\Category::factory()->create();
        $product = \App\Models\Product::factory()->create(['category_id' => $category->id]);
        \App\Models\Product::factory()->count(4)->create(['category_id' => $category->id]);

        $response = $this->getJson('/api/products/' . $product->id . '/related');

        $response->assertStatus(200)
            ->assertJsonCount(4, 'data');
    }
}
