<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class NotificationControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function createUserAndActAs()
    {
        /** @var \App\Models\User $user */
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user, 'api');
        return $user;
    }

    public function test_user_can_get_their_notifications()
    {
        $user = $this->createUserAndActAs();
        $user->notifications()->create(['data' => ['message' => 'Test Notification']]);

        $response = $this->getJson('/api/notifications');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'notifications.data');
    }

    public function test_user_can_get_their_unread_notifications()
    {
        $user = $this->createUserAndActAs();
        $user->notifications()->create(['data' => ['message' => 'Test Notification'], 'read_at' => null]);
        $user->notifications()->create(['data' => ['message' => 'Test Notification 2'], 'read_at' => now()]);

        $response = $this->getJson('/api/notifications/unread');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'notifications');
    }

    public function test_user_can_mark_a_notification_as_read()
    {
        $user = $this->createUserAndActAs();
        $notification = $user->notifications()->create(['data' => ['message' => 'Test Notification']]);

        $response = $this->postJson('/api/notifications/' . $notification->id . '/read');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_user_can_mark_all_notifications_as_read()
    {
        $user = $this->createUserAndActAs();
        $user->notifications()->create(['data' => ['message' => 'Test Notification']]);

        $response = $this->postJson('/api/notifications/read-all');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertEquals(0, $user->unreadNotifications()->count());
    }

    public function test_user_can_delete_a_notification()
    {
        $user = $this->createUserAndActAs();
        $notification = $user->notifications()->create(['data' => ['message' => 'Test Notification']]);

        $response = $this->deleteJson('/api/notifications/' . $notification->id);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('notifications', ['id' => $notification->id]);
    }
}
