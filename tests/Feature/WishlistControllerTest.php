<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class WishlistControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function createUserAndActAs()
    {
        /** @var \App\Models\User $user */
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user, 'api');
        return $user;
    }

    public function test_user_can_get_their_wishlist()
    {
        $user = $this->createUserAndActAs();
        $product = \App\Models\Product::factory()->create();
        $user->wishlist()->create(['product_id' => $product->id]);

        $response = $this->getJson('/api/wishlist');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_user_can_add_a_product_to_their_wishlist()
    {
        $user = $this->createUserAndActAs();
        $product = \App\Models\Product::factory()->create();

        $response = $this->postJson('/api/wishlist/add', ['product_id' => $product->id]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('wishlists', ['user_id' => $user->id, 'product_id' => $product->id]);
    }

    public function test_user_can_remove_a_product_from_their_wishlist()
    {
        $user = $this->createUserAndActAs();
        $product = \App\Models\Product::factory()->create();
        $user->wishlist()->create(['product_id' => $product->id]);

        $response = $this->deleteJson('/api/wishlist/remove/' . $product->id);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('wishlists', ['user_id' => $user->id, 'product_id' => $product->id]);
    }
}
