<?php

namespace Tests\Unit;

use App\Models\Topic;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SoftDeletesTest extends TestCase
{
    use RefreshDatabase;

    public function test_topic_can_be_soft_deleted()
    {
        $topic = Topic::factory()->create();
        $topic->delete();

        $this->assertSoftDeleted('topics', ['id' => $topic->id]);
        $this->assertCount(0, Topic::all());
        $this->assertCount(1, Topic::withTrashed()->get());
    }

    public function test_category_can_be_soft_deleted()
    {
        $category = Category::factory()->create();
        $category->delete();

        $this->assertSoftDeleted('categories', ['id' => $category->id]);
        $this->assertCount(0, Category::all());
        $this->assertCount(1, Category::withTrashed()->get());
    }

    public function test_topic_deletion_does_not_cascade_to_category_soft_delete()
    {
        // This confirms our structural decision: 
        // We don't want hard deletes, and soft deletes should be managed.
        $topic = Topic::factory()->create();
        $category = Category::factory()->create(['topic_id' => $topic->id]);

        $topic->delete();

        // Category should still exist (not soft deleted automatically)
        $this->assertDatabaseHas('categories', ['id' => $category->id, 'deleted_at' => null]);
    }
}
