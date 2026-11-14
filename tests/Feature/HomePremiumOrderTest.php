<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Modules\Category\Entities\Category;
use Modules\Category\Http\Controllers\CategoryController;
use Modules\Product\Entities\Product;
use Modules\User\Entities\User;
use Tests\TestCase;

class HomePremiumOrderTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        return User::query()->create([
            'name' => 'Test',
            'surname' => 'User',
            'email' => 'user'.random_int(1000, 999999).'@example.test',
            'phone' => '+9945'.str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT),
            'password' => 'secret',
        ]);
    }

    private function category(string $slug, ?int $parentId = null): Category
    {
        return Category::query()->create([
            'name' => ['az' => $slug, 'en' => $slug, 'ru' => $slug, 'tr' => $slug],
            'slug' => $slug,
            'parent_id' => $parentId,
            'is_active' => true,
        ]);
    }

    private function product(User $user, Category $category, int $sales, bool $premium): Product
    {
        return Product::query()->create([
            'title' => ['az' => 'Elan', 'en' => 'Listing', 'ru' => 'Объявление', 'tr' => 'İlan'],
            'price' => 10,
            'user_id' => $user->id,
            'category_id' => $category->id,
            'is_active' => true,
            'approval_status' => 'approved',
            'sales_count' => $sales,
            'is_premium' => $premium,
        ]);
    }

    /** @return array<int, array<string, mixed>> */
    private function sections(): array
    {
        return app(CategoryController::class)
            ->listWithProducts(Request::create('/', 'GET'))
            ->getData(true)['data'] ?? [];
    }

    public function test_home_sections_show_premium_listings_first(): void
    {
        $user = $this->user();
        $category = $this->category('premium-order');
        $best = $this->product($user, $category, 500, false);
        $premium = $this->product($user, $category, 1, true);

        $section = $this->sections()[0];

        $this->assertSame([$premium->id, $best->id], array_column($section['products'], 'id'));
    }

    public function test_home_sections_fill_with_other_listings_when_premium_is_scarce(): void
    {
        $user = $this->user();
        $category = $this->category('premium-fill');
        $this->product($user, $category, 10, false);
        $this->product($user, $category, 30, false);
        $this->product($user, $category, 20, false);

        $section = $this->sections()[0];

        $this->assertCount(3, $section['products']);
        $this->assertSame([30, 20, 10], array_column($section['products'], 'sales_count'));
    }

    public function test_child_category_premium_listings_lead_the_merged_row(): void
    {
        $user = $this->user();
        $parent = $this->category('parent-store');
        $child = $this->category('child-store', $parent->id);

        $this->product($user, $parent, 900, false);
        $premium = $this->product($user, $child, 1, true);

        $section = $this->sections()[0];

        $this->assertSame($premium->id, $section['products'][0]['id']);
    }
}
