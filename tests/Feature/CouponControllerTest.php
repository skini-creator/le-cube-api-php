<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class CouponControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_validate_a_coupon()
    {
        $coupon = \App\Models\Coupon::factory()->create(['code' => 'VALID10']);

        $response = $this->postJson('/api/coupons/validate', ['code' => 'VALID10', 'subtotal' => 100]);

        $response->assertStatus(200)
            ->assertJson(['success' => true, 'message' => 'Coupon is valid']);
    }

    public function test_admin_can_get_all_coupons()
    {
        /** @var \App\Models\User $admin */
        $admin = \App\Models\User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin, 'api');

        \App\Models\Coupon::factory()->count(5)->create();

        $response = $this->getJson('/api/admin/coupons');

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data');
    }

    public function test_admin_can_create_a_coupon()
    {
        /** @var \App\Models\User $admin */
        $admin = \App\Models\User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin, 'api');

        $couponData = [
            'code' => 'NEWCOUPON',
            'type' => 'fixed',
            'value' => 50,
        ];

        $response = $this->postJson('/api/admin/coupons', $couponData);

        $response->assertStatus(201)
            ->assertJsonFragment(['code' => 'NEWCOUPON']);

        $this->assertDatabaseHas('coupons', ['code' => 'NEWCOUPON']);
    }

    public function test_admin_can_update_a_coupon()
    {
        /** @var \App\Models\User $admin */
        $admin = \App\Models\User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin, 'api');

        $coupon = \App\Models\Coupon::factory()->create();

        $updateData = ['value' => 75];

        $response = $this->putJson('/api/admin/coupons/' . $coupon->id, $updateData);

        $response->assertStatus(200)
            ->assertJsonFragment($updateData);

        $this->assertDatabaseHas('coupons', ['id' => $coupon->id, 'value' => 75]);
    }

    public function test_admin_can_delete_a_coupon()
    {
        /** @var \App\Models\User $admin */
        $admin = \App\Models\User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin, 'api');

        $coupon = \App\Models\Coupon::factory()->create();

        $response = $this->deleteJson('/api/admin/coupons/' . $coupon->id);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('coupons', ['id' => $coupon->id]);
    }
}
