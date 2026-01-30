<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class PaymentControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function createUserAndActAs()
    {
        /** @var \App\Models\User $user */
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user, 'api');
        return $user;
    }

    public function test_user_can_get_their_payments()
    {
        $user = $this->createUserAndActAs();
        $order = \App\Models\Order::factory()->create(['user_id' => $user->id]);
        \App\Models\Payment::factory()->count(2)->create(['order_id' => $order->id]);

        $response = $this->getJson('/api/payments');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'payments');
    }

    public function test_user_can_get_a_single_payment()
    {
        $user = $this->createUserAndActAs();
        $order = \App\Models\Order::factory()->create(['user_id' => $user->id]);
        $payment = \App\Models\Payment::factory()->create(['order_id' => $order->id]);

        $response = $this->getJson('/api/payments/' . $payment->id);

        $response->assertStatus(200)
            ->assertJson(['success' => true, 'payment' => ['id' => $payment->id]]);
    }
}
