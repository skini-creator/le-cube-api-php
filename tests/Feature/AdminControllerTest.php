<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AdminControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function createAdminUser()
    {
        return \App\Models\User::factory()->create(['role' => 'admin']);
    }

    public function test_admin_can_access_dashboard()
    {
        /** @var \App\Models\User $admin */
        $admin = $this->createAdminUser();
        $this->actingAs($admin, 'api');

        $response = $this->getJson('/api/admin/dashboard');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'statistics' => [
                    'total_revenue',
                    'total_orders',
                    'pending_orders',
                    'total_products',
                    'total_users',
                    'low_stock_products',
                ],
                'recent_orders',
                'sales_trend',
                'top_products',
            ]);
    }

    public function test_admin_can_get_statistics()
    {
        /** @var \App\Models\User $admin */
        $admin = $this->createAdminUser();
        $this->actingAs($admin, 'api');

        $response = $this->getJson('/api/admin/statistics?period=month');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'period',
                'statistics' => [
                    'revenue',
                    'orders',
                    'new_customers',
                    'average_order_value',
                ]
            ]);
    }

    public function test_admin_can_get_all_users()
    {
        /** @var \App\Models\User $admin */
        $admin = $this->createAdminUser();
        $this->actingAs($admin, 'api');

        \App\Models\User::factory()->count(5)->create();

        $response = $this->getJson('/api/admin/users');

        $response->assertStatus(200)
            ->assertJsonCount(6, 'data'); // 5 + admin
    }

    public function test_admin_can_get_a_single_user()
    {
        /** @var \App\Models\User $admin */
        $admin = $this->createAdminUser();
        $this->actingAs($admin, 'api');

        $user = \App\Models\User::factory()->create();

        $response = $this->getJson('/api/admin/users/' . $user->id);

        $response->assertStatus(200)
            ->assertJson(['data' => ['id' => $user->id]]);
    }

    public function test_admin_can_update_a_user()
    {
        /** @var \App\Models\User $admin */
        $admin = $this->createAdminUser();
        $this->actingAs($admin, 'api');

        $user = \App\Models\User::factory()->create();

        $updateData = ['name' => 'Updated Name'];

        $response = $this->putJson('/api/admin/users/' . $user->id, $updateData);

        $response->assertStatus(200)
            ->assertJsonFragment($updateData);

        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'Updated Name']);
    }

    public function test_admin_can_delete_a_user()
    {
        /** @var \App\Models\User $admin */
        $admin = $this->createAdminUser();
        $this->actingAs($admin, 'api');

        $user = \App\Models\User::factory()->create();

        $response = $this->deleteJson('/api/admin/users/' . $user->id);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertSoftDeleted('users', ['id' => $user->id]);
    }

    public function test_admin_can_get_all_orders()
    {
        /** @var \App\Models\User $admin */
        $admin = $this->createAdminUser();
        $this->actingAs($admin, 'api');

        \App\Models\Order::factory()->count(5)->create();

        $response = $this->getJson('/api/admin/orders');

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data');
    }

    public function test_admin_can_get_a_single_order()
    {
        /** @var \App\Models\User $admin */
        $admin = $this->createAdminUser();
        $this->actingAs($admin, 'api');

        $order = \App\Models\Order::factory()->create();

        $response = $this->getJson('/api/admin/orders/' . $order->id);

        $response->assertStatus(200)
            ->assertJson(['data' => ['id' => $order->id]]);
    }

    public function test_admin_can_update_order_status()
    {
        /** @var \App\Models\User $admin */
        $admin = $this->createAdminUser();
        $this->actingAs($admin, 'api');

        $order = \App\Models\Order::factory()->create();

        $updateData = ['status' => 'shipped'];

        $response = $this->putJson('/api/admin/orders/' . $order->id . '/status', $updateData);

        $response->assertStatus(200)
            ->assertJsonFragment($updateData);

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'shipped']);
    }

    public function test_admin_can_get_pending_reviews()
    {
        /** @var \App\Models\User $admin */
        $admin = $this->createAdminUser();
        $this->actingAs($admin, 'api');

        \App\Models\Review::factory()->count(3)->create(['is_approved' => false]);

        $response = $this->getJson('/api/admin/reviews/pending');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'reviews.data');
    }

    public function test_admin_can_approve_a_review()
    {
        /** @var \App\Models\User $admin */
        $admin = $this->createAdminUser();
        $this->actingAs($admin, 'api');

        $review = \App\Models\Review::factory()->create(['is_approved' => false]);

        $response = $this->postJson('/api/admin/reviews/' . $review->id . '/approve');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('reviews', ['id' => $review->id, 'is_approved' => true]);
    }

    public function test_admin_can_reject_a_review()
    {
        /** @var \App\Models\User $admin */
        $admin = $this->createAdminUser();
        $this->actingAs($admin, 'api');

        $review = \App\Models\Review::factory()->create();

        $response = $this->deleteJson('/api/admin/reviews/' . $review->id . '/reject');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('reviews', ['id' => $review->id]);
    }

    public function test_admin_can_get_sales_report()
    {
        /** @var \App\Models\User $admin */
        $admin = $this->createAdminUser();
        $this->actingAs($admin, 'api');

        $response = $this->getJson('/api/admin/reports/sales');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'period',
                'sales',
                'summary',
            ]);
    }

    public function test_admin_can_get_products_report()
    {
        /** @var \App\Models\User $admin */
        $admin = $this->createAdminUser();
        $this->actingAs($admin, 'api');

        $response = $this->getJson('/api/admin/reports/products');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'summary',
                'top_selling',
            ]);
    }

    public function test_admin_can_get_customers_report()
    {
        /** @var \App\Models\User $admin */
        $admin = $this->createAdminUser();
        $this->actingAs($admin, 'api');

        $response = $this->getJson('/api/admin/reports/customers');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'summary',
                'top_customers',
            ]);
    }
}
