<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AddressControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_get_their_addresses()
    {
        /** @var \App\Models\User $user */
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user, 'api');

        \App\Models\Address::factory()->count(3)->create(['user_id' => $user->id]);

        $response = $this->getJson('/api/addresses');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_user_can_get_a_single_address()
    {
        /** @var \App\Models\User $user */
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user, 'api');

        $address = \App\Models\Address::factory()->create(['user_id' => $user->id]);

        $response = $this->getJson('/api/addresses/' . $address->id);

        $response->assertStatus(200)
            ->assertJson(['data' => ['id' => $address->id]]);
    }

    public function test_user_can_create_an_address()
    {
        /** @var \App\Models\User $user */
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user, 'api');

        $addressData = [
            'type' => 'shipping',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'phone' => '1234567890',
            'address_line_1' => '123 Main St',
            'city' => 'Anytown',
            'state' => 'CA',
            'postal_code' => '12345',
            'country' => 'US',
        ];

        $response = $this->postJson('/api/addresses', $addressData);

        $response->assertStatus(201)
            ->assertJsonFragment($addressData);

        $this->assertDatabaseHas('addresses', $addressData);
    }

    public function test_user_can_update_an_address()
    {
        /** @var \App\Models\User $user */
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user, 'api');

        $address = \App\Models\Address::factory()->create(['user_id' => $user->id]);

        $updateData = ['first_name' => 'Jane'];

        $response = $this->putJson('/api/addresses/' . $address->id, $updateData);

        $response->assertStatus(200)
            ->assertJsonFragment($updateData);

        $this->assertDatabaseHas('addresses', ['id' => $address->id, 'first_name' => 'Jane']);
    }

    public function test_user_can_delete_an_address()
    {
        /** @var \App\Models\User $user */
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user, 'api');

        $address = \App\Models\Address::factory()->create(['user_id' => $user->id]);

        $response = $this->deleteJson('/api/addresses/' . $address->id);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('addresses', ['id' => $address->id]);
    }

    public function test_user_can_set_default_address()
    {
        /** @var \App\Models\User $user */
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user, 'api');

        $address1 = \App\Models\Address::factory()->create(['user_id' => $user->id, 'type' => 'shipping', 'is_default' => true]);
        $address2 = \App\Models\Address::factory()->create(['user_id' => $user->id, 'type' => 'shipping', 'is_default' => false]);

        $response = $this->postJson('/api/addresses/' . $address2->id . '/set-default');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('addresses', ['id' => $address2->id, 'is_default' => true]);
        $this->assertDatabaseHas('addresses', ['id' => $address1->id, 'is_default' => false]);
    }
}
