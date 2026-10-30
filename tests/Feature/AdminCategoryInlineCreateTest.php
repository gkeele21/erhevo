<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCategoryInlineCreateTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_admin_can_create_a_top_level_category_over_json(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)
            ->postJson('/admin/categories', ['name' => 'Emotional Health', 'parent_id' => null]);

        $response->assertCreated()->assertJsonPath('category.name', 'Emotional Health');

        $category = Category::findOrFail($response->json('category.id'));
        $this->assertNull($category->parent_id);
        $this->assertNull($category->user_id);
        $this->assertTrue($category->is_approved);
    }

    public function test_parent_id_may_be_omitted(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->postJson('/admin/categories', ['name' => 'Emotional Health'])
            ->assertCreated();
    }

    public function test_duplicate_names_are_rejected(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        Category::create(['name' => 'Gratitude']);

        $this->actingAs($admin)
            ->postJson('/admin/categories', ['name' => 'Gratitude'])
            ->assertUnprocessable();
    }

    public function test_non_admins_cannot_create_categories_through_the_admin_endpoint(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/admin/categories', ['name' => 'Emotional Health'])
            ->assertForbidden();

        $this->assertDatabaseMissing('categories', ['name' => 'Emotional Health']);
    }
}
