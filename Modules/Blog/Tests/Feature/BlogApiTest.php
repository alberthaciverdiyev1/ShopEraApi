<?php

namespace Modules\Blog\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Blog\Entities\Blog;
use Tests\TestCase;

class BlogApiTest extends TestCase
{
    public function test_can_list_published_blogs(): void
    {
        $response = $this->getJson('/api/blog');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'status_code',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'slug',
                        'description',
                        'image',
                        'category',
                        'author_name',
                        'tags',
                        'views',
                        'published_at',
                        'created_at',
                    ],
                ],
            ]);
    }

    public function test_can_filter_blogs_by_category(): void
    {
        $response = $this->getJson('/api/blog?category=Texnologiya');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertNotEmpty($data);
        foreach ($data as $item) {
            $this->assertEquals('Texnologiya', $item['category']);
        }
    }

    public function test_can_get_blog_details_and_increments_views(): void
    {
        $blog = Blog::query()->where('is_active', true)->first();
        $this->assertNotNull($blog);

        $initialViews = $blog->views;

        $response = $this->getJson('/api/blog/' . $blog->slug);

        $response->assertStatus(200)
            ->assertJsonPath('data.slug', $blog->slug)
            ->assertJsonPath('data.views', $initialViews + 1);
    }

    public function test_returns_404_for_non_existent_blog(): void
    {
        $response = $this->getJson('/api/blog/non-existent-blog-slug-9999');

        $response->assertStatus(404);
    }

    public function test_can_get_recent_blogs(): void
    {
        $response = $this->getJson('/api/blog/recent?limit=3');

        $response->assertStatus(200);
        $this->assertLessThanOrEqual(3, count($response->json('data')));
    }
}
