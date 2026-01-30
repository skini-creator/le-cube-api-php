<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class CartControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function createUserAndActAs()
    {
        /** @var \App\Models\User $user */
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user, 'api');
        return $user;
    }

    public function test_user_can_get_their_cart()
    {
        $this->createUserAndActAs();
        $response = $this->getJson('/api/cart');

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'cart', 'subtotal', 'items_count']);
    }

    public function test_user_can_add_item_to_cart()
    {
        $this->createUserAndActAs();
        $product = \App\Models\Product::factory()->create(['stock_quantity' => 10]);

        $itemData = [
            'product_id' => $product->id,
            'quantity' => 1,
        ];

        $response = $this->postJson('/api/cart/add', $itemData);

        $response->assertStatus(200)
            ->assertJson(['success' => true, 'message' => 'Item added to cart']);

        $this->assertDatabaseHas('cart_items', ['product_id' => $product->id, 'quantity' => 1]);
    }

    public function test_user_can_update_cart_item_quantity()
    {
        $user = $this->createUserAndActAs();
        $product = \App\Models\Product::factory()->create(['stock_quantity' => 10]);
        $cart = \App\Models\Cart::factory()->create(['user_id' => $user->id]);
        $cartItem = \App\Models\CartItem::factory()->create(['cart_id' => $cart->id, 'product_id' => $product->id, 'quantity' => 1]);

        $updateData = ['quantity' => 3];

        $response = $this->putJson('/api/cart/update/' . $cartItem->id, $updateData);

        $response->assertStatus(200)
            ->assertJson(['success' => true, 'message' => 'Cart updated']);

        $this->assertDatabaseHas('cart_items', ['id' => $cartItem->id, 'quantity' => 3]);
    }

    public function test_user_can_remove_item_from_cart()
    {
        $user = $this->createUserAndActAs();
        $product = \App\Models\Product::factory()->create();
        $cart = \App\Models\Cart::factory()->create(['user_id' => $user->id]);
        $cartItem = \App\Models\CartItem::factory()->create(['cart_id' => $cart->id, 'product_id' => $product->id]);

        $response = $this->deleteJson('/api/cart/remove/' . $cartItem->id);

        $response->assertStatus(200)
            ->assertJson(['success' => true, 'message' => 'Item removed from cart']);

        $this->assertDatabaseMissing('cart_items', ['id' => $cartItem->id]);
    }

    public function test_user_can_clear_cart()
    {
        $user = $this->createUserAndActAs();
        $product = \App\Models\Product::factory()->create();
        $cart = \App\Models\Cart::factory()->create(['user_id' => $user->id]);
        \App\Models\CartItem::factory()->count(3)->create(['cart_id' => $cart->id, 'product_id' => $product->id]);

        $response = $this->postJson('/api/cart/clear');

        $response->assertStatus(200)
            ->assertJson(['success' => true, 'message' => 'Cart cleared']);

        $this->assertEquals(0, $cart->items()->count());
    }

    public function test_user_can_apply_valid_coupon()
    {
        $this->createUserAndActAs();
        $coupon = \App\Models\Coupon::factory()->create(['code' => 'TEST10', 'discount_percent' => 10]);

        $response = $this->postJson('/api/cart/apply-coupon', ['coupon_code' => 'TEST10']);

        $response->assertStatus(200)
            ->assertJson(['success' => true, 'message' => 'Coupon applied']);
    }
}
