<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ShippingMethodControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_get_all_active_shipping_methods()
    {
        \App\Models\ShippingMethod::factory()->count(3)->create(['is_active' => true]);
        \App\Models\ShippingMethod::factory()->count(2)->create(['is_active' => false]);

        $response = $this->getJson('/api/shipping-methods');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'shipping_methods');
    }

    public function test_admin_can_create_a_shipping_method()
    {
        /** @var \App\Models\User $admin */
        $admin = \App\Models\User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin, 'api');

        $methodData = [
            'name' => 'Express Shipping',
            'cost' => 25.00,
        ];

        $response = $this->postJson('/api/admin/shipping-methods', $methodData);

        $response->assertStatus(201)
            ->assertJsonFragment($methodData);

        $this->assertDatabaseHas('shipping_methods', $methodData);
    }

    public function test_admin_can_update_a_shipping_method()
    {
        /** @var \App\Models\User $admin */
        $admin = \App\Models\User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin, 'api');

        $method = \App\Models\ShippingMethod::factory()->create();

        $updateData = ['cost' => 30.00];

        $response = $this->putJson('/api/admin/shipping-methods/' . $method->id, $updateData);

        $response->assertStatus(200)
            ->assertJsonFragment($updateData);

        $this->assertDatabaseHas('shipping_methods', ['id' => $method->id, 'cost' => 30.00]);
    }

    public function test_admin_can_delete_a_shipping_method()
    {
        /** @var \App\Models\User $admin */
        $admin = \App\Models\User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin, 'api');

        $method = \App\Models\ShippingMethod::factory()->create();

        $response = $this->deleteJson('/api/admin/shipping-methods/' . $method->id);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('shipping_methods', ['id' => $method->id]);
    }
}
