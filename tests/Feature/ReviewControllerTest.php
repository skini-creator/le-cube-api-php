<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ReviewControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function createUserAndActAs()
    {
        /** @var \App\Models\User $user */
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user, 'api');
        return $user;
    }

    public function test_can_get_reviews_for_a_product()
    {
        $product = \App\Models\Product::factory()->create();
        \App\Models\Review::factory()->count(3)->create(['product_id' => $product->id, 'is_approved' => true]);

        $response = $this->getJson('/api/products/' . $product->id . '/reviews');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_user_can_submit_a_review()
    {
        $user = $this->createUserAndActAs();
        $product = \App\Models\Product::factory()->create();

        $reviewData = [
            'rating' => 5,
            'comment' => 'This is a great product!',
        ];

        $response = $this->postJson('/api/products/' . $product->id . '/reviews', $reviewData);

        $response->assertStatus(201)
            ->assertJson(['success' => true, 'message' => 'Review submitted for approval.']);

        $this->assertDatabaseHas('reviews', ['product_id' => $product->id, 'user_id' => $user->id]);
    }

    public function test_user_can_update_their_own_review()
    {
        $user = $this->createUserAndActAs();
        $review = \App\Models\Review::factory()->create(['user_id' => $user->id]);

        $updateData = ['comment' => 'This is an updated comment.'];

        $response = $this->putJson('/api/reviews/' . $review->id, $updateData);

        $response->assertStatus(200)
            ->assertJsonFragment($updateData);

        $this->assertDatabaseHas('reviews', ['id' => $review->id, 'comment' => 'This is an updated comment.']);
    }

    public function test_user_can_delete_their_own_review()
    {
        $user = $this->createUserAndActAs();
        $review = \App\Models\Review::factory()->create(['user_id' => $user->id]);

        $response = $this->deleteJson('/api/reviews/' . $review->id);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('reviews', ['id' => $review->id]);
    }

    public function test_user_can_mark_a_review_as_helpful()
    {
        $this->createUserAndActAs();
        $review = \App\Models\Review::factory()->create();

        $response = $this->postJson('/api/reviews/' . $review->id . '/helpful');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('reviews', ['id' => $review->id, 'helpful_count' => 1]);
    }
}
