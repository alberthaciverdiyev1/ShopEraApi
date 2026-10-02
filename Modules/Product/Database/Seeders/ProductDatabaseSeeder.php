<?php

namespace Modules\Product\Database\Seeders;

use App\Enums\Gender;
use Illuminate\Database\Seeder;
use Modules\Brand\Entities\Brand;
use Modules\Category\Entities\Category;
use Modules\Color\Entities\Color;
use Modules\Product\Entities\Product;
use Modules\Product\Entities\ProductImage;
use Modules\Size\Entities\Size;
use Modules\User\Entities\User;

class ProductDatabaseSeeder extends Seeder
{
    /**
     * The real product catalogue. Every title/description is curated in four
     * languages and each product is linked to an existing brand, category,
     * colour set and (where it makes sense) size run.
     */
    public function run(): void
    {
        $categories = Category::query()->get()->keyBy(fn (Category $c) => $c->getTranslation('name', 'en'));
        $brands = Brand::query()->pluck('id', 'name');
        $colors = Color::query()->get()->keyBy(fn (Color $c) => $c->getTranslation('name', 'en'));
        $sizes = Size::query()->get()->keyBy(fn (Size $s) => $s->getTranslation('name', 'en'));
        $sellerId = User::query()->where('email', 'alberthaciverdiyev55@gmail.com')->value('id')
            ?? User::query()->value('id');

        $descriptions = $this->descriptions();
        $images = $this->images();

        $catalogue = require __DIR__.'/catalogue.php';

        $sku = 1000;

        foreach ($catalogue as $row) {
            $category = $categories[$row['category']] ?? null;
            $brand = $brands[$row['brand'] ?? ''] ?? null;

            if (! $category) {
                continue;
            }

            $title = $row['title'];
            $description = [];
            foreach (['az', 'en', 'ru', 'tr'] as $locale) {
                $description[$locale] = str_replace(
                    '{title}',
                    $title[$locale] ?? $title['en'],
                    $descriptions[$row['category']][$locale] ?? $descriptions['default'][$locale]
                );
            }

            $price = (float) $row['price'];
            $discount = (float) ($row['discount'] ?? 0);

            $product = Product::firstOrNew(['title->en' => $title['en']]);

            $product->fill([
                'brand_id' => $brand,
                'category_id' => $category->id,
                'title' => $title,
                'description' => $description,
                'gender' => $row['gender'] ?? Gender::UNISEX->value,
                'price' => $price,
                'discount' => $discount,
                'discount_expire_date' => $discount > 0 ? now()->addDays(30) : null,
                'stock_count' => $row['stock'],
                'weight' => $row['weight'] ?? null,
                'is_active' => true,
                'is_suggest' => $row['suggest'] ?? false,
                'is_pinned' => $row['pinned'] ?? false,
                'views' => $row['views'] ?? 0,
                'sales_count' => $row['sales'] ?? 0,
                'user_id' => $sellerId,
                'approval_status' => 'approved',
                'approved_by' => $sellerId,
                'approved_at' => now(),
                'last_approved_at' => now(),
            ]);

            if (! $product->exists) {
                $product->sku = 'SHP-'.(++$sku);
            }

            $product->save();

            // Colours.
            $colorIds = [];
            foreach ($row['colors'] ?? [] as $colorName) {
                if (isset($colors[$colorName])) {
                    $colorIds[] = $colors[$colorName]->id;
                }
            }
            if ($colorIds) {
                $product->colors()->sync($colorIds);
            }

            // Sizes (with per-size pricing).
            if (! empty($row['sizes'])) {
                $sync = [];
                foreach ($row['sizes'] as $sizeName) {
                    if (isset($sizes[$sizeName])) {
                        $sync[$sizes[$sizeName]->id] = [
                            'price' => $price,
                            'wholesale_price' => round($price * 0.8, 2),
                            'discount' => $discount ?: null,
                        ];
                    }
                }
                if ($sync) {
                    $product->sizes()->sync($sync);
                }
            }

            // Images (rebuilt on every run so the set stays in step).
            $pool = $images[$row['category']] ?? $images['default'];
            ProductImage::where('product_id', $product->id)->delete();
            $productImages = [];
            foreach ($pool as $path) {
                $productImages[] = [
                    'product_id' => $product->id,
                    'color_id' => $colorIds[0] ?? null,
                    'image_path' => 'https://images.unsplash.com/photo-'.$path.'?auto=format&fit=crop&w=900&q=80',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            ProductImage::insert($productImages);
        }
    }

    private function descriptions(): array
    {
        return [
            'default' => [
                'az' => '{title} — orijinal məhsul, rəsmi zəmanətlə təqdim olunur.',
                'en' => '{title} — an original product supplied with official warranty.',
                'ru' => '{title} — оригинальный товар с официальной гарантией.',
                'tr' => '{title} — resmi garantili orijinal ürün.',
            ],
            'Phones' => [
                'az' => '{title} — güclü prosessor, aydın ekran və uzunmüddətli batareya. Sürətli çatdırılma və rəsmi zəmanət.',
                'en' => '{title} — a powerful processor, a sharp display and all-day battery life. Fast delivery with official warranty.',
                'ru' => '{title} — мощный процессор, яркий экран и долгое время работы. Быстрая доставка и официальная гарантия.',
                'tr' => '{title} — güçlü işlemci, net ekran ve uzun pil ömrü. Hızlı teslimat ve resmi garanti.',
            ],
            'Laptops' => [
                'az' => '{title} — iş və təhsil üçün yığcam noutbuk. Sürətli SSD yaddaş və enerjiyə qənaətli prosessor.',
                'en' => '{title} — a slim laptop built for work and study, with fast SSD storage and an efficient processor.',
                'ru' => '{title} — компактный ноутбук для работы и учёбы: быстрый SSD и энергоэффективный процессор.',
                'tr' => '{title} — iş ve eğitim için ince bir dizüstü bilgisayar; hızlı SSD ve verimli işlemci.',
            ],
            'Tablets' => [
                'az' => '{title} — film izləmək, oxumaq və iş üçün universal planşet.',
                'en' => '{title} — a versatile tablet for streaming, reading and getting work done.',
                'ru' => '{title} — универсальный планшет для просмотра, чтения и работы.',
                'tr' => '{title} — film, kitap ve iş için çok yönlü bir tablet.',
            ],
            'TVs' => [
                'az' => '{title} — 4K dəqiqlik, smart funksiyalar və təmiz səs.',
                'en' => '{title} — 4K clarity, smart features and clean sound.',
                'ru' => '{title} — чёткость 4K, смарт-функции и чистый звук.',
                'tr' => '{title} — 4K netlik, akıllı özellikler ve temiz ses.',
            ],
            'Men' => [
                'az' => '{title} — keyfiyyətli parça, rahat kəsim və uzunömürlü tikiliş.',
                'en' => '{title} — quality fabric, a comfortable cut and durable stitching.',
                'ru' => '{title} — качественная ткань, удобный крой и прочные швы.',
                'tr' => '{title} — kaliteli kumaş, rahat kesim ve dayanıklı dikişler.',
            ],
            'Women' => [
                'az' => '{title} — zərif dizayn və gündəlik geyim üçün rahatlıq.',
                'en' => '{title} — an elegant design that stays comfortable all day long.',
                'ru' => '{title} — изящный дизайн и комфорт на весь день.',
                'tr' => '{title} — zarif tasarım ve gün boyu konfor.',
            ],
            'Kids' => [
                'az' => '{title} — uşaqlar üçün yumşaq, davamlı və təhlükəsiz materiallar.',
                'en' => '{title} — soft, durable and child-safe materials.',
                'ru' => '{title} — мягкие, прочные и безопасные для ребёнка материалы.',
                'tr' => '{title} — yumuşak, dayanıklı ve çocuk dostu malzemeler.',
            ],
            'Shoes' => [
                'az' => '{title} — gündəlik istifadə üçün yüngül, dözümlü ayaqqabı.',
                'en' => '{title} — a lightweight, hard-wearing shoe for everyday wear.',
                'ru' => '{title} — лёгкая и износостойкая обувь на каждый день.',
                'tr' => '{title} — günlük kullanım için hafif ve dayanıklı ayakkabı.',
            ],
            'Furniture' => [
                'az' => '{title} — ev üçün funksional mebel, asan yığılır və uzun xidmət edir.',
                'en' => '{title} — functional furniture that assembles easily and lasts.',
                'ru' => '{title} — функциональная мебель, легко собирается и долго служит.',
                'tr' => '{title} — kolay kurulan ve uzun ömürlü fonksiyonel mobilya.',
            ],
            'Kitchen' => [
                'az' => '{title} — mətbəxdə gündəlik istifadə üçün praktik və davamlı.',
                'en' => '{title} — practical and durable for everyday kitchen use.',
                'ru' => '{title} — практично и надёжно для ежедневного использования на кухне.',
                'tr' => '{title} — günlük mutfak kullanımı için pratik ve dayanıklı.',
            ],
            'Decor' => [
                'az' => '{title} — evinizə rahatlıq və gözəllik qatan dekor.',
                'en' => '{title} — a decor piece that brings warmth and character home.',
                'ru' => '{title} — декор, придающий дому уют и характер.',
                'tr' => '{title} — eve sıcaklık ve karakter katan bir dekor parçası.',
            ],
            'Lighting' => [
                'az' => '{title} — enerjiyə qənaətli işıqlandırma, isti və rahat atmosfer.',
                'en' => '{title} — energy-efficient lighting with a warm, cosy glow.',
                'ru' => '{title} — энергоэффективное освещение с тёплым светом.',
                'tr' => '{title} — sıcak ve samimi bir ışık veren enerji tasarruflu aydınlatma.',
            ],
            'Makeup' => [
                'az' => '{title} — uzunmüddətli və təbii görünüş üçün yüksək piqmentli formula.',
                'en' => '{title} — a high-pigment formula for a long-lasting, natural look.',
                'ru' => '{title} — высокопигментированная формула для стойкого естественного макияжа.',
                'tr' => '{title} — kalıcı ve doğal görünüm için yüksek pigmentli formül.',
            ],
            'Skincare' => [
                'az' => '{title} — dərinin gündəlik qulluq ehtiyacları üçün nəmləndirici formula.',
                'en' => '{title} — a hydrating formula for everyday skin care.',
                'ru' => '{title} — увлажняющая формула для ежедневного ухода за кожей.',
                'tr' => '{title} — günlük cilt bakımı için nemlendirici formül.',
            ],
            'Fragrance' => [
                'az' => '{title} — gündəlik istifadə üçün davamlı və zərif ətri kompozisiya.',
                'en' => '{title} — a long-lasting, refined scent composition.',
                'ru' => '{title} — стойкая и изящная парфюмерная композиция.',
                'tr' => '{title} — kalıcı ve zarif bir koku kompozisyonu.',
            ],
            'Hair' => [
                'az' => '{title} — saçın gündəlik qulluq və qidalanma ehtiyacı üçün.',
                'en' => '{title} — daily care and nourishment for your hair.',
                'ru' => '{title} — ежедневный уход и питание для волос.',
                'tr' => '{title} — saçınız için günlük bakım ve beslenme.',
            ],
            'Fitness' => [
                'az' => '{title} — məşq zamanı rahatlıq və dözümlülük üçün nəzərdə tutulub.',
                'en' => '{title} — engineered for comfort and endurance during training.',
                'ru' => '{title} — создано для комфорта и выносливости во время тренировок.',
                'tr' => '{title} — antrenmanda konfor ve dayanıklılık için tasarlandı.',
            ],
            'Football' => [
                'az' => '{title} — meydançada maksimum nəzarət və dəqiqlik.',
                'en' => '{title} — maximum control and precision on the pitch.',
                'ru' => '{title} — максимальный контроль и точность на поле.',
                'tr' => '{title} — sahada maksimum kontrol ve isabet.',
            ],
            'Cycling' => [
                'az' => '{title} — şəhər və təbiət marşrutları üçün etibarlı velosiped.',
                'en' => '{title} — a dependable bike for city and countryside routes.',
                'ru' => '{title} — надёжный велосипед для города и загородных маршрутов.',
                'tr' => '{title} — şehir ve doğa rotaları için güvenilir bisiklet.',
            ],
            'Outdoor' => [
                'az' => '{title} — təbiətdə aktiv istirahət üçün davamlı avadanlıq.',
                'en' => '{title} — durable gear for active days outdoors.',
                'ru' => '{title} — прочное снаряжение для активного отдыха на природе.',
                'tr' => '{title} — doğada aktif günler için dayanıklı ekipman.',
            ],
            'Kids Toys' => [
                'az' => '{title} — uşaqların təxəyyülünü inkişaf etdirən təhlükəsiz oyuncaq.',
                'en' => '{title} — a safe toy that sparks a child\'s imagination.',
                'ru' => '{title} — безопасная игрушка, развивающая воображение ребёнка.',
                'tr' => '{title} — çocuğun hayal gücünü geliştiren güvenli oyuncak.',
            ],
            'Educational' => [
                'az' => '{title} — oyun vasitəsilə öyrədən inkişaf etdirici dəst.',
                'en' => '{title} — a hands-on set that teaches through play.',
                'ru' => '{title} — набор, обучающий через игру.',
                'tr' => '{title} — oyun yoluyla öğreten bir set.',
            ],
            'Puzzles' => [
                'az' => '{title} — ailə üçün əyləncəli və diqqət tələb edən puzzle.',
                'en' => '{title} — an entertaining puzzle that rewards focus.',
                'ru' => '{title} — увлекательный пазл, требующий внимания.',
                'tr' => '{title} — dikkat gerektiren eğlenceli bir puzzle.',
            ],
            'Fiction' => [
                'az' => '{title} — oxucunu sona qədər əlində saxlayan hekayə.',
                'en' => '{title} — a story that holds the reader to the last page.',
                'ru' => '{title} — история, которая не отпускает до последней страницы.',
                'tr' => '{title} — okuyucuyu son sayfaya kadar tutan bir hikâye.',
            ],
            'Science' => [
                'az' => '{title} — dünyanı dərk etməyə kömək edən elmi əsər.',
                'en' => '{title} — a work of science that helps make sense of the world.',
                'ru' => '{title} — научный труд, помогающий понять мир.',
                'tr' => '{title} — dünyayı anlamaya yardımcı olan bir bilim eseri.',
            ],
            'Children' => [
                'az' => '{title} — kiçik oxucular üçün rəngarəng və öyrədici kitab.',
                'en' => '{title} — a colourful and instructive book for young readers.',
                'ru' => '{title} — красочная и познавательная книга для маленьких читателей.',
                'tr' => '{title} — küçük okurlar için renkli ve öğretici bir kitap.',
            ],
            'Fruits & Vegetables' => [
                'az' => '{title} — hər gün təzə və keyfiyyətli, sifarişlə seçilir.',
                'en' => '{title} — fresh and quality-checked, selected to order.',
                'ru' => '{title} — свежие и качественные продукты, отбираются под заказ.',
                'tr' => '{title} — her gün taze ve kaliteli, siparişe göre seçilir.',
            ],
            'Dairy' => [
                'az' => '{title} — soyuducuda saxlanılan təzə süd məhsulu.',
                'en' => '{title} — a fresh dairy product kept refrigerated until delivery.',
                'ru' => '{title} — свежий молочный продукт, хранится в холоде до доставки.',
                'tr' => '{title} — teslimata kadar soğuk tutulan taze süt ürünü.',
            ],
            'Beverages' => [
                'az' => '{title} — sərinlədici içki, sifarişlə çatdırılır.',
                'en' => '{title} — a refreshing drink delivered chilled to your door.',
                'ru' => '{title} — освежающий напиток с доставкой.',
                'tr' => '{title} — kapınıza soğuk teslim edilen ferahlatıcı içecek.',
            ],
        ];
    }

    private function images(): array
    {
        return [
            'default' => ['1511707171634-5f897ff02aa9', '1592750475338-74b7b21085ab'],
            'Phones' => ['1511707171634-5f897ff02aa9', '1592750475338-74b7b21085ab', '1580910051074-3eb694886505'],
            'Laptops' => ['1496181133206-80ce9b88a853', '1517336714731-489689fd1ca8', '1611186871348-b1ce696e52c9'],
            'Tablets' => ['1544244015-0df4b3ffc6b0', '1561154464-82e9adf32764'],
            'TVs' => ['1593359677879-a4bb92f829d1', '1461151304267-38535e780c79'],
            'Men' => ['1516826957135-700dedea698c', '1521572163474-6864f9cf17ab'],
            'Women' => ['1529139574466-a303027c1d8b', '1483985988355-763728e1935b'],
            'Kids' => ['1503919005314-30d93d07d823', '1519238263530-99bdd11df2ea'],
            'Shoes' => ['1542291026-7eec264c27ff', '1549298916-b41d501d3772'],
            'Furniture' => ['1555041469-a586c61ea9bc', '1567538096630-e0c55bd6374c'],
            'Kitchen' => ['1556909114-f6e7ad7d3136', '1584990347449-a0a4a4f8f0e2'],
            'Decor' => ['1513519245088-0e12902e5a38', '1416879595882-3373a0480b5b'],
            'Lighting' => ['1507473885765-e6ed057f782c', '1513506003901-1e6a229e2d15'],
            'Makeup' => ['1522335789203-aabd1fc54bc9', '1512496015851-a90fb38ba796'],
            'Skincare' => ['1570194065650-d99fb4bedf0a', '1556228578-8c89e6adf883'],
            'Fragrance' => ['1541643600914-78b084683601', '1592945403244-b3fbafd7f539'],
            'Hair' => ['1522337360788-8b13dee7a37e', '1571875257727-256c39da42af'],
            'Fitness' => ['1517836357463-d25dfeac3438', '1571019613454-1cb2f99b2d8b'],
            'Football' => ['1431324155629-1a6deb1dec8d', '1579952363873-27f3bade9f55'],
            'Cycling' => ['1485965120184-e220f721d03e', '1571068318344-89f9a1c9d1e6'],
            'Outdoor' => ['1500530855697-b586d89ba3ee', '1551632811-561732d1e306'],
            'Kids Toys' => ['1587654780291-39c9404d746b', '1596461404969-9ae70f2830c1'],
            'Educational' => ['1503676260728-1c00da094a0b', '1588072432836-e10032774350'],
            'Puzzles' => ['1611996575749-79a3a250f948', '1606092195730-5d7b9af1efc5'],
            'Fiction' => ['1512820790803-83ca734da794', '1544947950-fa07a98d237f'],
            'Science' => ['1532012197267-da84d127e765', '1497633762265-9d179a990aa6'],
            'Children' => ['1516627145497-ae6968895b74', '1503919005314-30d93d07d823'],
            'Fruits & Vegetables' => ['1610348725531-843dff563e2c', '1560806887-1e4cd0b6cbd6'],
            'Dairy' => ['1563636619-e9143da7973b', '1550583724-b2692b85b150'],
            'Beverages' => ['1544145945-f90425340c7e', '1554866585-cd94860890b7'],
        ];
    }
}
