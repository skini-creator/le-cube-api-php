<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register()
    {
        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ];

        $response = $this->postJson('/api/auth/register', $userData);

        $response->assertStatus(201)
            ->assertJsonStructure(['success', 'message', 'user', 'token']);

        $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
    }

    public function test_user_can_login()
    {
        $user = \App\Models\User::factory()->create(['password' => bcrypt('password')]);

        $loginData = ['email' => $user->email, 'password' => 'password'];

        $response = $this->postJson('/api/auth/login', $loginData);

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'access_token', 'token_type', 'expires_in', 'user']);
    }

    public function test_user_can_get_their_profile()
    {
        /** @var \App\Models\User $user */
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user, 'api');

        $response = $this->getJson('/api/auth/me');

        $response->assertStatus(200)
            ->assertJson(['success' => true, 'user' => ['id' => $user->id]]);
    }

    public function test_user_can_logout()
    {
        /** @var \App\Models\User $user */
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user, 'api');

        $response = $this->postJson('/api/auth/logout');

        $response->assertStatus(200)
            ->assertJson(['success' => true, 'message' => 'Successfully logged out']);
    }

    public function test_user_can_refresh_token()
    {
        /** @var \App\Models\User $user */
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user, 'api');

        $response = $this->postJson('/api/auth/refresh');

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'access_token', 'token_type', 'expires_in', 'user']);
    }

    public function test_user_can_update_profile()
    {
        /** @var \App\Models\User $user */
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user, 'api');

        $updateData = ['name' => 'Updated Name'];

        $response = $this->putJson('/api/auth/profile', $updateData);

        $response->assertStatus(200)
            ->assertJsonFragment($updateData);

        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'Updated Name']);
    }

    public function test_user_can_change_password()
    {
        /** @var \App\Models\User $user */
        $user = \App\Models\User::factory()->create(['password' => bcrypt('password')]);
        $this->actingAs($user, 'api');

        $passwordData = [
            'current_password' => 'password',
            'new_password' => 'new-password',
            'new_password_confirmation' => 'new-password',
        ];

        $response = $this->putJson('/api/auth/password', $passwordData);

        $response->assertStatus(200)
            ->assertJson(['success' => true, 'message' => 'Password changed successfully']);
    }
}
