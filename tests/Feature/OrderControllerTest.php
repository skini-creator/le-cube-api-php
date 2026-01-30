<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class OrderControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function createUserAndActAs()
    {
        /** @var \App\Models\User $user */
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user, 'api');
        return $user;
    }

    public function test_user_can_get_their_orders()
    {
        $user = $this->createUserAndActAs();
        \App\Models\Order::factory()->count(3)->create(['user_id' => $user->id]);

        $response = $this->getJson('/api/orders');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_user_can_get_a_single_order()
    {
        $user = $this->createUserAndActAs();
        $order = \App\Models\Order::factory()->create(['user_id' => $user->id]);

        $response = $this->getJson('/api/orders/' . $order->id);

        $response->assertStatus(200)
            ->assertJson(['data' => ['id' => $order->id]]);
    }

    public function test_user_can_create_an_order()
    {
        $user = $this->createUserAndActAs();
        $address = \App\Models\Address::factory()->create(['user_id' => $user->id]);
        $cart = \App\Models\Cart::factory()->create(['user_id' => $user->id]);
        $product = \App\Models\Product::factory()->create(['stock_quantity' => 10]);
        \App\Models\CartItem::factory()->create(['cart_id' => $cart->id, 'product_id' => $product->id]);

        $orderData = [
            'shipping_address_id' => $address->id,
            'payment_method' => 'cash_on_delivery',
        ];

        $response = $this->postJson('/api/orders', $orderData);

        $response->assertStatus(201)
            ->assertJson(['success' => true, 'message' => 'Order created successfully']);

        $this->assertDatabaseHas('orders', ['user_id' => $user->id]);
    }

    public function test_user_can_cancel_an_order()
    {
        $user = $this->createUserAndActAs();
        $order = \App\Models\Order::factory()->create(['user_id' => $user->id, 'status' => 'pending']);

        $response = $this->postJson('/api/orders/' . $order->id . '/cancel');

        $response->assertStatus(200)
            ->assertJson(['success' => true, 'message' => 'Order cancelled successfully']);

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'cancelled']);
    }

    public function test_can_track_an_order()
    {
        $order = \App\Models\Order::factory()->create();

        $response = $this->getJson('/api/orders/track/' . $order->order_number);

        $response->assertStatus(200)
            ->assertJson(['success' => true, 'order' => ['order_number' => $order->order_number]]);
    }
}
