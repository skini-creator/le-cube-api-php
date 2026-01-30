<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class CategoryControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_get_all_categories()
    {
        \App\Models\Category::factory()->count(5)->create();

        $response = $this->getJson('/api/categories');

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data');
    }

    public function test_can_get_a_single_category()
    {
        $category = \App\Models\Category::factory()->create();

        $response = $this->getJson('/api/categories/' . $category->id);

        $response->assertStatus(200)
            ->assertJson(['data' => ['id' => $category->id]]);
    }

    public function test_admin_can_create_a_category()
    {
        /** @var \App\Models\User $admin */
        $admin = \App\Models\User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin, 'api');

        $categoryData = ['name' => 'New Category'];

        $response = $this->postJson('/api/categories', $categoryData);

        $response->assertStatus(201)
            ->assertJsonFragment($categoryData);

        $this->assertDatabaseHas('categories', $categoryData);
    }

    public function test_admin_can_update_a_category()
    {
        /** @var \App\Models\User $admin */
        $admin = \App\Models\User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin, 'api');

        $category = \App\Models\Category::factory()->create();

        $updateData = ['name' => 'Updated Category'];

        $response = $this->putJson('/api/categories/' . $category->id, $updateData);

        $response->assertStatus(200)
            ->assertJsonFragment($updateData);

        $this->assertDatabaseHas('categories', ['id' => $category->id, 'name' => 'Updated Category']);
    }

    public function test_admin_can_delete_a_category()
    {
        /** @var \App\Models\User $admin */
        $admin = \App\Models\User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin, 'api');

        $category = \App\Models\Category::factory()->create();

        $response = $this->deleteJson('/api/categories/' . $category->id);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }
}
