<?php

namespace Modules\Category\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Category\Entities\Category;

class CategoryDatabaseSeeder extends Seeder
{
    /**
     * Curated, translatable catalog of categories (parents + children).
     */
    public function run(): void
    {
        $catalog = [
            [
                'az' => 'Elektronika', 'en' => 'Electronics', 'ru' => 'Электроника', 'tr' => 'Elektronik',
                'image' => 'https://images.unsplash.com/photo-1550009158-9ebf69173e03?auto=format&fit=crop&w=640&q=80',
                'children' => [
                    [
                        'az' => 'Telefonlar', 'en' => 'Phones', 'ru' => 'Телефоны', 'tr' => 'Telefonlar',
                        'image' => 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?auto=format&fit=crop&w=640&q=80',
                        'children' => [
                            ['az' => 'Smartfonlar', 'en' => 'Smartphones', 'ru' => 'Смартфоны', 'tr' => 'Akıllı Telefonlar', 'image' => 'https://images.unsplash.com/photo-1598327105666-5b89351aff97?auto=format&fit=crop&w=640&q=80'],
                            ['az' => 'Telefon aksesuarları', 'en' => 'Phone Accessories', 'ru' => 'Аксессуары для телефонов', 'tr' => 'Telefon Aksesuarları', 'image' => 'https://images.unsplash.com/photo-1601972599720-36938d4ecd31?auto=format&fit=crop&w=640&q=80'],
                            ['az' => 'Düyməli telefonlar', 'en' => 'Feature Phones', 'ru' => 'Кнопочные телефоны', 'tr' => 'Tuşlu Telefonlar', 'image' => 'https://images.unsplash.com/photo-1535303311164-664fc9ec6532?auto=format&fit=crop&w=640&q=80'],
                        ],
                    ],
                    [
                        'az' => 'Noutbuklar', 'en' => 'Laptops', 'ru' => 'Ноутбуки', 'tr' => 'Dizüstü Bilgisayarlar',
                        'image' => 'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?auto=format&fit=crop&w=640&q=80',
                        'children' => [
                            ['az' => 'Gaming noutbuklar', 'en' => 'Gaming Laptops', 'ru' => 'Игровые ноутбуки', 'tr' => 'Oyuncu Laptopları', 'image' => 'https://images.unsplash.com/photo-1603302576837-37561b2e2302?auto=format&fit=crop&w=640&q=80'],
                            ['az' => 'Ultrabuklar', 'en' => 'Ultrabooks', 'ru' => 'Ультрабуки', 'tr' => 'Ultrabooklar', 'image' => 'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?auto=format&fit=crop&w=640&q=80'],
                            ['az' => 'Noutbuk aksesuarları', 'en' => 'Laptop Accessories', 'ru' => 'Аксессуары для ноутбуков', 'tr' => 'Laptop Aksesuarları', 'image' => 'https://images.unsplash.com/photo-1527864550417-7fd91fc51a46?auto=format&fit=crop&w=640&q=80'],
                        ],
                    ],
                    ['az' => 'Planşetlər', 'en' => 'Tablets', 'ru' => 'Планшеты', 'tr' => 'Tabletler', 'image' => 'https://images.unsplash.com/photo-1544244015-0df4b3ffc6b0?auto=format&fit=crop&w=640&q=80'],
                    ['az' => 'Televizorlar', 'en' => 'TVs', 'ru' => 'Телевизоры', 'tr' => 'Televizyonlar', 'image' => 'https://images.unsplash.com/photo-1593359677879-a4bb92f829d1?auto=format&fit=crop&w=640&q=80'],
                ],
            ],
            [
                'az' => 'Geyim', 'en' => 'Fashion', 'ru' => 'Одежда', 'tr' => 'Giyim',
                'image' => 'https://images.unsplash.com/photo-1489987707025-afc232f7ea0f?auto=format&fit=crop&w=640&q=80',
                'children' => [
                    [
                        'az' => 'Kişi geyimi', 'en' => 'Men', 'ru' => 'Мужское', 'tr' => 'Erkek',
                        'image' => 'https://images.unsplash.com/photo-1516826957135-700dedea698c?auto=format&fit=crop&w=640&q=80',
                        'children' => [
                            ['az' => 'Köynəklər', 'en' => 'Shirts', 'ru' => 'Рубашки', 'tr' => 'Gömlekler', 'image' => 'https://images.unsplash.com/photo-1602810318383-e386cc2a3ccf?auto=format&fit=crop&w=640&q=80'],
                            ['az' => 'Şalvarlar', 'en' => 'Trousers', 'ru' => 'Брюки', 'tr' => 'Pantolonlar', 'image' => 'https://images.unsplash.com/photo-1473966968600-fa801b869a1a?auto=format&fit=crop&w=640&q=80'],
                            ['az' => 'Gödəkçələr', 'en' => 'Jackets', 'ru' => 'Куртки', 'tr' => 'Ceketler', 'image' => 'https://images.unsplash.com/photo-1551028719-00167b16eac5?auto=format&fit=crop&w=640&q=80'],
                        ],
                    ],
                    [
                        'az' => 'Qadın geyimi', 'en' => 'Women', 'ru' => 'Женское', 'tr' => 'Kadın',
                        'image' => 'https://images.unsplash.com/photo-1529139574466-a303027c1d8b?auto=format&fit=crop&w=640&q=80',
                        'children' => [
                            ['az' => 'Donlar', 'en' => 'Dresses', 'ru' => 'Платья', 'tr' => 'Elbiseler', 'image' => 'https://images.unsplash.com/photo-1496747611176-843222e1e57c?auto=format&fit=crop&w=640&q=80'],
                            ['az' => 'Bluzlar', 'en' => 'Blouses', 'ru' => 'Блузки', 'tr' => 'Bluzlar', 'image' => 'https://images.unsplash.com/photo-1551489186-cf8726f514f8?auto=format&fit=crop&w=640&q=80'],
                            ['az' => 'Ətəklər', 'en' => 'Skirts', 'ru' => 'Юбки', 'tr' => 'Etekler', 'image' => 'https://images.unsplash.com/photo-1594633312681-425c7b97ccd1?auto=format&fit=crop&w=640&q=80'],
                        ],
                    ],
                    ['az' => 'Uşaq geyimi', 'en' => 'Kids', 'ru' => 'Детское', 'tr' => 'Çocuk', 'image' => 'https://images.unsplash.com/photo-1503919005314-30d93d07d823?auto=format&fit=crop&w=640&q=80'],
                    ['az' => 'Ayaqqabı', 'en' => 'Shoes', 'ru' => 'Обувь', 'tr' => 'Ayakkabı', 'image' => 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?auto=format&fit=crop&w=640&q=80'],
                ],
            ],
            [
                'az' => 'Ev və Yaşayış', 'en' => 'Home & Living', 'ru' => 'Дом и быт', 'tr' => 'Ev ve Yaşam',
                'image' => 'https://images.unsplash.com/photo-1484101403633-562f891dc89a?auto=format&fit=crop&w=640&q=80',
                'children' => [
                    [
                        'az' => 'Mebel', 'en' => 'Furniture', 'ru' => 'Мебель', 'tr' => 'Mobilya',
                        'image' => 'https://images.unsplash.com/photo-1555041469-a586c61ea9bc?auto=format&fit=crop&w=640&q=80',
                        'children' => [
                            ['az' => 'Divanlar', 'en' => 'Sofas', 'ru' => 'Диваны', 'tr' => 'Kanepeler', 'image' => 'https://images.unsplash.com/photo-1555041469-a586c61ea9bc?auto=format&fit=crop&w=640&q=80'],
                            ['az' => 'Masalar', 'en' => 'Tables', 'ru' => 'Столы', 'tr' => 'Masalar', 'image' => 'https://images.unsplash.com/photo-1533090481720-856c6e3c1fdc?auto=format&fit=crop&w=640&q=80'],
                            ['az' => 'Şkaflar', 'en' => 'Wardrobes', 'ru' => 'Шкафы', 'tr' => 'Dolaplar', 'image' => 'https://images.unsplash.com/photo-1595428774223-ef52624120d2?auto=format&fit=crop&w=640&q=80'],
                        ],
                    ],
                    [
                        'az' => 'Mətbəx', 'en' => 'Kitchen', 'ru' => 'Кухня', 'tr' => 'Mutfak',
                        'image' => 'https://images.unsplash.com/photo-1556909114-f6e7ad7d3136?auto=format&fit=crop&w=640&q=80',
                        'children' => [
                            ['az' => 'Qab-qacaq', 'en' => 'Tableware', 'ru' => 'Посуда', 'tr' => 'Sofra Takımı', 'image' => 'https://images.unsplash.com/photo-1603199506016-b9a594b593c0?auto=format&fit=crop&w=640&q=80'],
                            ['az' => 'Mətbəx alətləri', 'en' => 'Kitchen Tools', 'ru' => 'Кухонные инструменты', 'tr' => 'Mutfak Gereçleri', 'image' => 'https://images.unsplash.com/photo-1556911220-bff31c812dba?auto=format&fit=crop&w=640&q=80'],
                            ['az' => 'Saxlama qabları', 'en' => 'Storage Containers', 'ru' => 'Контейнеры для хранения', 'tr' => 'Saklama Kapları', 'image' => 'https://images.unsplash.com/photo-1556911261-6bd341186b2f?auto=format&fit=crop&w=640&q=80'],
                        ],
                    ],
                    ['az' => 'Dekor', 'en' => 'Decor', 'ru' => 'Декор', 'tr' => 'Dekorasyon', 'image' => 'https://images.unsplash.com/photo-1513519245088-0e12902e5a38?auto=format&fit=crop&w=640&q=80'],
                    ['az' => 'İşıqlandırma', 'en' => 'Lighting', 'ru' => 'Освещение', 'tr' => 'Aydınlatma', 'image' => 'https://images.unsplash.com/photo-1507473885765-e6ed057f782c?auto=format&fit=crop&w=640&q=80'],
                ],
            ],
            [
                'az' => 'Gözəllik', 'en' => 'Beauty', 'ru' => 'Красота', 'tr' => 'Güzellik',
                'image' => 'https://images.unsplash.com/photo-1596462502278-27bfdc403348?auto=format&fit=crop&w=640&q=80',
                'children' => [
                    ['az' => 'Makiyaj', 'en' => 'Makeup', 'ru' => 'Макияж', 'tr' => 'Makyaj', 'image' => 'https://images.unsplash.com/photo-1522335789203-aabd1fc54bc9?auto=format&fit=crop&w=640&q=80'],
                    ['az' => 'Dəriyə qulluq', 'en' => 'Skincare', 'ru' => 'Уход за кожей', 'tr' => 'Cilt Bakımı', 'image' => 'https://images.unsplash.com/photo-1570194065650-d99fb4bedf0a?auto=format&fit=crop&w=640&q=80'],
                    ['az' => 'Ətir', 'en' => 'Fragrance', 'ru' => 'Парфюмерия', 'tr' => 'Parfüm', 'image' => 'https://images.unsplash.com/photo-1541643600914-78b084683601?auto=format&fit=crop&w=640&q=80'],
                    ['az' => 'Saç', 'en' => 'Hair', 'ru' => 'Волосы', 'tr' => 'Saç', 'image' => 'https://images.unsplash.com/photo-1522337360788-8b13dee7a37e?auto=format&fit=crop&w=640&q=80'],
                ],
            ],
            [
                'az' => 'İdman', 'en' => 'Sports', 'ru' => 'Спорт', 'tr' => 'Spor',
                'image' => 'https://images.unsplash.com/photo-1517649763962-0c623066013b?auto=format&fit=crop&w=640&q=80',
                'children' => [
                    ['az' => 'Fitnes', 'en' => 'Fitness', 'ru' => 'Фитнес', 'tr' => 'Fitness', 'image' => 'https://images.unsplash.com/photo-1517836357463-d25dfeac3438?auto=format&fit=crop&w=640&q=80'],
                    ['az' => 'Futbol', 'en' => 'Football', 'ru' => 'Футбол', 'tr' => 'Futbol', 'image' => 'https://images.unsplash.com/photo-1431324155629-1a6deb1dec8d?auto=format&fit=crop&w=640&q=80'],
                    ['az' => 'Velosiped', 'en' => 'Cycling', 'ru' => 'Велоспорт', 'tr' => 'Bisiklet', 'image' => 'https://images.unsplash.com/photo-1485965120184-e220f721d03e?auto=format&fit=crop&w=640&q=80'],
                    ['az' => 'Açıq hava', 'en' => 'Outdoor', 'ru' => 'Туризм', 'tr' => 'Outdoor', 'image' => 'https://images.unsplash.com/photo-1500530855697-b586d89ba3ee?auto=format&fit=crop&w=640&q=80'],
                ],
            ],
            [
                'az' => 'Oyuncaqlar', 'en' => 'Toys', 'ru' => 'Игрушки', 'tr' => 'Oyuncaklar',
                'image' => 'https://images.unsplash.com/photo-1558877385-81a1c7e67d72?auto=format&fit=crop&w=640&q=80',
                'children' => [
                    ['az' => 'Uşaq oyuncaqları', 'en' => 'Kids Toys', 'ru' => 'Детские игрушки', 'tr' => 'Çocuk Oyuncakları', 'image' => 'https://images.unsplash.com/photo-1596461404969-9ae70f2830c1?auto=format&fit=crop&w=640&q=80'],
                    ['az' => 'Təhsil', 'en' => 'Educational', 'ru' => 'Развивающие', 'tr' => 'Eğitici', 'image' => 'https://images.unsplash.com/photo-1503676260728-1c00da094a0b?auto=format&fit=crop&w=640&q=80'],
                    ['az' => 'Puzzle', 'en' => 'Puzzles', 'ru' => 'Пазлы', 'tr' => 'Puzzle', 'image' => 'https://images.unsplash.com/photo-1611996575749-79a3a250f948?auto=format&fit=crop&w=640&q=80'],
                ],
            ],
            [
                'az' => 'Kitablar', 'en' => 'Books', 'ru' => 'Книги', 'tr' => 'Kitaplar',
                'image' => 'https://images.unsplash.com/photo-1495446815901-a7297e633e8d?auto=format&fit=crop&w=640&q=80',
                'children' => [
                    ['az' => 'Bədii ədəbiyyat', 'en' => 'Fiction', 'ru' => 'Художественная', 'tr' => 'Roman', 'image' => 'https://images.unsplash.com/photo-1512820790803-83ca734da794?auto=format&fit=crop&w=640&q=80'],
                    ['az' => 'Elm', 'en' => 'Science', 'ru' => 'Наука', 'tr' => 'Bilim', 'image' => 'https://images.unsplash.com/photo-1532012197267-da84d127e765?auto=format&fit=crop&w=640&q=80'],
                    ['az' => 'Uşaq kitabları', 'en' => 'Children', 'ru' => 'Детские', 'tr' => 'Çocuk', 'image' => 'https://images.unsplash.com/photo-1516627145497-ae6968895b74?auto=format&fit=crop&w=640&q=80'],
                ],
            ],
            [
                'az' => 'Ərzaq', 'en' => 'Grocery', 'ru' => 'Продукты', 'tr' => 'Gıda',
                'image' => 'https://images.unsplash.com/photo-1542838132-92c53300491e?auto=format&fit=crop&w=640&q=80',
                'children' => [
                    ['az' => 'Meyvə-tərəvəz', 'en' => 'Fruits & Vegetables', 'ru' => 'Фрукты и овощи', 'tr' => 'Meyve & Sebze', 'image' => 'https://images.unsplash.com/photo-1610348725531-843dff563e2c?auto=format&fit=crop&w=640&q=80'],
                    ['az' => 'Süd məhsulları', 'en' => 'Dairy', 'ru' => 'Молочные', 'tr' => 'Süt Ürünleri', 'image' => 'https://images.unsplash.com/photo-1563636619-e9143da7973b?auto=format&fit=crop&w=640&q=80'],
                    ['az' => 'İçkilər', 'en' => 'Beverages', 'ru' => 'Напитки', 'tr' => 'İçecekler', 'image' => 'https://images.unsplash.com/photo-1544145945-f90425340c7e?auto=format&fit=crop&w=640&q=80'],
                ],
            ],
        ];

        $sort = count($catalog);

        foreach ($catalog as $parent) {
            $parentCategory = Category::updateOrCreate([
                'name->en' => $parent['en'],
                'parent_id' => null,
            ], [
                'name' => [
                    'az' => $parent['az'], 'en' => $parent['en'],
                    'ru' => $parent['ru'], 'tr' => $parent['tr'],
                ],
                'image' => $parent['image'],
                'is_active' => true,
                'sort_order' => $sort--,
            ]);

            $this->syncChildren($parent['children'], $parentCategory->id);
        }
    }

    private function syncChildren(array $children, int $parentId): void
    {
        $sort = count($children);

        foreach ($children as $child) {
            $category = Category::updateOrCreate([
                'name->en' => $child['en'],
                'parent_id' => $parentId,
            ], [
                'name' => [
                    'az' => $child['az'], 'en' => $child['en'],
                    'ru' => $child['ru'], 'tr' => $child['tr'],
                ],
                'image' => $child['image'],
                'is_active' => true,
                'sort_order' => $sort--,
            ]);

            if (! empty($child['children'])) {
                $this->syncChildren($child['children'], $category->id);
            }
        }
    }
}
