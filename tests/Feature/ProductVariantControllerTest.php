<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ProductVariantControllerTest extends TestCase
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

    public function test_can_get_variants_for_a_product()
    {
        $product = \App\Models\Product::factory()->create();
        \App\Models\ProductVariant::factory()->count(3)->create(['product_id' => $product->id]);

        $response = $this->getJson('/api/products/' . $product->id . '/variants');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'variants');
    }

    public function test_vendor_can_create_a_variant()
    {
        [$vendor, $product] = $this->createVendorAndProduct();

        $variantData = [
            'sku' => 'TEST-SKU-01',
            'name' => 'Test Variant',
            'attributes' => ['color' => 'blue', 'size' => 'M'],
            'price' => 120,
            'stock_quantity' => 5,
        ];

        $response = $this->postJson('/api/products/' . $product->id . '/variants', $variantData);

        $response->assertStatus(201)
            ->assertJsonFragment(['sku' => 'TEST-SKU-01']);

        $this->assertDatabaseHas('product_variants', ['sku' => 'TEST-SKU-01']);
    }

    public function test_vendor_can_update_a_variant()
    {
        [$vendor, $product] = $this->createVendorAndProduct();
        $variant = \App\Models\ProductVariant::factory()->create(['product_id' => $product->id]);

        $updateData = ['price' => 150];

        $response = $this->putJson('/api/product-variants/' . $variant->id, $updateData);

        $response->assertStatus(200)
            ->assertJsonFragment($updateData);

        $this->assertDatabaseHas('product_variants', ['id' => $variant->id, 'price' => 150]);
    }

    public function test_vendor_can_delete_a_variant()
    {
        [$vendor, $product] = $this->createVendorAndProduct();
        $variant = \App\Models\ProductVariant::factory()->create(['product_id' => $product->id]);

        $response = $this->deleteJson('/api/product-variants/' . $variant->id);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('product_variants', ['id' => $variant->id]);
    }
}
