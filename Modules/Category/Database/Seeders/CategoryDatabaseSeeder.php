<?php

namespace Modules\Category\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Category\Entities\Category;

class CategoryDatabaseSeeder extends Seeder
{
    private const DEFAULT_IMAGE = 'https://images.unsplash.com/photo-1483985988355-763728e1935b?auto=format&fit=crop&w=640&q=80';

    public function run(): void
    {
        $catalog = $this->catalog();
        $sort = count($catalog);

        foreach ($catalog as $parent) {
            $parentCategory = Category::updateOrCreate([
                'name->az' => $parent['name'],
                'parent_id' => null,
            ], [
                'name' => $this->translations($parent['name']),
                'image' => $parent['image'] ?? self::DEFAULT_IMAGE,
                'is_active' => true,
                'sort_order' => $sort--,
            ]);

            $this->syncChildren($parent['children'] ?? [], $parentCategory->id);
        }
    }

    private function syncChildren(array $children, int $parentId): void
    {
        $sort = count($children);

        foreach ($children as $child) {
            $category = Category::updateOrCreate([
                'name->az' => $child['name'],
                'parent_id' => $parentId,
            ], [
                'name' => $this->translations($child['name']),
                'image' => $child['image'] ?? self::DEFAULT_IMAGE,
                'is_active' => true,
                'sort_order' => $sort--,
            ]);

            if (! empty($child['children'])) {
                $this->syncChildren($child['children'], $category->id);
            }
        }
    }

    private function translations(string $name): array
    {
        return ['az' => $name, 'en' => $name, 'ru' => $name, 'tr' => $name];
    }

    private function category(string $name, array $children = [], ?string $image = null): array
    {
        return array_filter([
            'name' => $name,
            'image' => $image,
            'children' => $children,
        ], fn ($value) => $value !== null && $value !== []);
    }

    private function catalog(): array
    {
        return [
            $this->category('Elektronika', [
                $this->category('Telefonlar', [
                    $this->category('Mobil telefonlar'),
                    $this->category('Smartfonlar'),
                    $this->category('Düyməli telefonlar'),
                    $this->category('Telefon aksesuarları'),
                    $this->category('Ehtiyat hissələri'),
                    $this->category('Nömrələr və SIM-kartlar'),
                ]),
                $this->category('Kompüterlər, noutbuklar və planşetlər', [
                    $this->category('Noutbuklar'),
                    $this->category('Masaüstü kompüterlər'),
                    $this->category('Planşetlər'),
                    $this->category('Monitorlar'),
                    $this->category('Kompüter hissələri'),
                    $this->category('Kompüter aksesuarları'),
                    $this->category('Printerlər və skanerlər'),
                    $this->category('Şəbəkə avadanlığı'),
                ]),
                $this->category('TV, audio və video', [
                    $this->category('Televizorlar'),
                    $this->category('Audio sistemlər'),
                    $this->category('Səsgücləndiricilər'),
                    $this->category('Qulaqlıqlar'),
                    $this->category('Proyektorlar'),
                    $this->category('Video kameralar'),
                    $this->category('Fotoaparatlar'),
                ]),
                $this->category('Məişət texnikası', [
                    $this->category('Soyuducular'),
                    $this->category('Paltaryuyan maşınlar'),
                    $this->category('Qabyuyan maşınlar'),
                    $this->category('Kondisionerlər'),
                    $this->category('Tozsoranlar'),
                    $this->category('Mətbəx texnikası'),
                    $this->category('Ütülər'),
                    $this->category('Su qızdırıcıları'),
                ]),
                $this->category('Oyun konsolları və oyunlar', [
                    $this->category('PlayStation'),
                    $this->category('Xbox'),
                    $this->category('Nintendo'),
                    $this->category('Oyunlar'),
                    $this->category('Oyun aksesuarları'),
                ]),
                $this->category('Ağıllı saatlar və qadcetlər'),
            ], 'https://images.unsplash.com/photo-1550009158-9ebf69173e03?auto=format&fit=crop&w=640&q=80'),
            $this->category('Mebel', [
                $this->category('Qonaq otağı mebeli', [
                    $this->category('Divanlar və kreslolar'),
                    $this->category('Jurnal masaları'),
                    $this->category('TV stendləri'),
                    $this->category('Vitrinlər və rəflər'),
                ]),
                $this->category('Yataq otağı mebeli', [
                    $this->category('Çarpayılar'),
                    $this->category('Döşəklər'),
                    $this->category('Şkaflar'),
                    $this->category('Komodlar'),
                    $this->category('Güzgülər'),
                ]),
                $this->category('Mətbəx mebeli', [
                    $this->category('Mətbəx dəstləri'),
                    $this->category('Masa və stullar'),
                    $this->category('Bar stulları'),
                ]),
                $this->category('Ofis mebeli', [
                    $this->category('Ofis masaları'),
                    $this->category('Ofis stulları'),
                    $this->category('Dolablar və arxivlər'),
                ]),
                $this->category('Uşaq mebeli'),
                $this->category('Bağ mebeli'),
            ], 'https://images.unsplash.com/photo-1555041469-a586c61ea9bc?auto=format&fit=crop&w=640&q=80'),
            $this->category('Ev və bağ', [
                $this->category('Ev tekstili', [
                    $this->category('Yataq dəstləri'),
                    $this->category('Pərdələr'),
                    $this->category('Xalçalar'),
                    $this->category('Dəsmallar'),
                    $this->category('Yastıqlar və yorğanlar'),
                ]),
                $this->category('Qab-qacaq və mətbəx', [
                    $this->category('Qazan və tavalar'),
                    $this->category('Servis dəstləri'),
                    $this->category('Bıçaq və alətlər'),
                    $this->category('Saxlama qabları'),
                ]),
                $this->category('Dekor və interyer', [
                    $this->category('Rəsmlər və posterlər'),
                    $this->category('Güldanlar'),
                    $this->category('Saatlar'),
                    $this->category('Şamdanlar'),
                ]),
                $this->category('Bağ və bostan', [
                    $this->category('Bitkilər və toxumlar'),
                    $this->category('Bağ alətləri'),
                    $this->category('Suvarma sistemləri'),
                    $this->category('Manqal və aksesuarlar'),
                ]),
                $this->category('Təmizlik vasitələri'),
                $this->category('İşıqlandırma'),
            ], 'https://images.unsplash.com/photo-1484101403633-562f891dc89a?auto=format&fit=crop&w=640&q=80'),
            $this->category('Təmir və tikinti', [
                $this->category('Tikinti materialları', [
                    $this->category('Sement və qum'),
                    $this->category('Kərpic və bloklar'),
                    $this->category('Taxta materiallar'),
                    $this->category('İzolyasiya materialları'),
                ]),
                $this->category('Alətlər', [
                    $this->category('Elektrik alətləri'),
                    $this->category('Əl alətləri'),
                    $this->category('Ölçü alətləri'),
                    $this->category('Bağ alətləri'),
                ]),
                $this->category('Santexnika', [
                    $this->category('Kranlar'),
                    $this->category('Unitaz və çanaqlar'),
                    $this->category('Duş kabinləri'),
                    $this->category('Borular və fitinqlər'),
                ]),
                $this->category('Elektrik malları', [
                    $this->category('Kabellər'),
                    $this->category('Rozetka və açarlar'),
                    $this->category('Lampalar'),
                    $this->category('Avtomatlar'),
                ]),
                $this->category('Qapılar və pəncərələr'),
                $this->category('Boya və laklar'),
            ], 'https://images.unsplash.com/photo-1503387762-592deb58ef4e?auto=format&fit=crop&w=640&q=80'),
            $this->category('Daşınmaz əmlak', [
                $this->category('Mənzillər', [
                    $this->category('Yeni tikili'),
                    $this->category('Köhnə tikili'),
                    $this->category('Günlük kirayə'),
                    $this->category('Uzunmüddətli kirayə'),
                ]),
                $this->category('Ev və villalar', [
                    $this->category('Satılır'),
                    $this->category('Kirayə verilir'),
                    $this->category('Bağ evləri'),
                ]),
                $this->category('Torpaq sahələri'),
                $this->category('Qarajlar'),
                $this->category('Obyektlər və ofislər'),
                $this->category('Xaricdə əmlak'),
            ], 'https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?auto=format&fit=crop&w=640&q=80'),
            $this->category('Nəqliyyat', [
                $this->category('Avtomobillər', [
                    $this->category('Sedan'),
                    $this->category('SUV və krossover'),
                    $this->category('Hetçbek'),
                    $this->category('Universal'),
                    $this->category('Elektromobil'),
                ]),
                $this->category('Ehtiyat hissələri və aksesuarlar', [
                    $this->category('Təkərlər və disklər'),
                    $this->category('Akkumulyatorlar'),
                    $this->category('Avto kosmetika'),
                    $this->category('Audio və video'),
                    $this->category('Siqnalizasiya'),
                    $this->category('GPS naviqatorlar'),
                    $this->category('Avtomobil üçün alətlər'),
                ]),
                $this->category('Motosikletlər və mopedlər', [
                    $this->category('Motosikletlər'),
                    $this->category('Skuterlər'),
                    $this->category('Mopedlər'),
                    $this->category('Moto aksesuarlar'),
                ]),
                $this->category('Su nəqliyyatı'),
                $this->category('Yük maşınları və qoşqular'),
                $this->category('Avtobuslar'),
                $this->category('Xüsusi texnika'),
            ], 'https://images.unsplash.com/photo-1503736334956-4c8f8e92946d?auto=format&fit=crop&w=640&q=80'),
            $this->category('İdman və hobbi', [
                $this->category('İdman malları', [
                    $this->category('Trenajorlar'),
                    $this->category('Velosipedlər'),
                    $this->category('Toplar'),
                    $this->category('İdman geyimi'),
                    $this->category('Balıqçılıq'),
                ]),
                $this->category('Musiqi alətləri', [
                    $this->category('Gitara'),
                    $this->category('Piano və sintezator'),
                    $this->category('Zərb alətləri'),
                    $this->category('Səs avadanlığı'),
                ]),
                $this->category('Kitablar və jurnallar'),
                $this->category('Kolleksiya'),
                $this->category('Turizm və kampinq'),
                $this->category('Biletlər'),
            ], 'https://images.unsplash.com/photo-1517649763962-0c623066013b?auto=format&fit=crop&w=640&q=80'),
            $this->category('Şəxsi əşyalar', [
                $this->category('Geyim', [
                    $this->category('Qadın geyimi'),
                    $this->category('Kişi geyimi'),
                    $this->category('Uşaq geyimi'),
                    $this->category('Üst geyimi'),
                    $this->category('Alt geyimi'),
                ]),
                $this->category('Ayaqqabı', [
                    $this->category('Qadın ayaqqabısı'),
                    $this->category('Kişi ayaqqabısı'),
                    $this->category('Uşaq ayaqqabısı'),
                    $this->category('İdman ayaqqabısı'),
                ]),
                $this->category('Aksesuarlar', [
                    $this->category('Çantalar'),
                    $this->category('Saatlar'),
                    $this->category('Eynəklər'),
                    $this->category('Zərgərlik'),
                ]),
                $this->category('Gözəllik və sağlamlıq', [
                    $this->category('Kosmetika'),
                    $this->category('Ətirlər'),
                    $this->category('Saç baxımı'),
                    $this->category('Dəri baxımı'),
                ]),
            ], 'https://images.unsplash.com/photo-1489987707025-afc232f7ea0f?auto=format&fit=crop&w=640&q=80'),
            $this->category('İş', [
                $this->category('Vakansiyalar', [
                    $this->category('Satış'),
                    $this->category('Ofis işi'),
                    $this->category('Restoran və turizm'),
                    $this->category('Sürücü və kuryer'),
                    $this->category('Tikinti'),
                    $this->category('Təhsil'),
                    $this->category('İT və telekom'),
                ]),
                $this->category('İş axtarıram', [
                    $this->category('CV-lər'),
                    $this->category('Part-time iş'),
                    $this->category('Uzaqdan iş'),
                ]),
            ], 'https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?auto=format&fit=crop&w=640&q=80'),
            $this->category('Xidmətlər', [
                $this->category('Təmir və tikinti xidmətləri', [
                    $this->category('Mənzil təmiri'),
                    $this->category('Santexnik'),
                    $this->category('Elektrik'),
                    $this->category('Kondisioner ustası'),
                    $this->category('Mebel yığımı'),
                ]),
                $this->category('Nəqliyyat və logistika', [
                    $this->category('Yükdaşıma'),
                    $this->category('Evakuator'),
                    $this->category('Taksi və sürücü'),
                    $this->category('Kuryer'),
                ]),
                $this->category('Təlim və kurslar', [
                    $this->category('Dil kursları'),
                    $this->category('Kompüter kursları'),
                    $this->category('Musiqi dərsləri'),
                    $this->category('Repetitorlar'),
                ]),
                $this->category('Gözəllik xidmətləri', [
                    $this->category('Bərbər'),
                    $this->category('Manikür'),
                    $this->category('Vizajist'),
                    $this->category('Masaj'),
                ]),
                $this->category('Foto və video'),
                $this->category('Təmizlik xidməti'),
                $this->category('Hüquq və mühasibatlıq'),
            ], 'https://images.unsplash.com/photo-1521791136064-7986c2920216?auto=format&fit=crop&w=640&q=80'),
            $this->category('Heyvanlar', [
                $this->category('İtlər', [
                    $this->category('Cins itlər'),
                    $this->category('Bala itlər'),
                    $this->category('İt aksesuarları'),
                    $this->category('İt yemləri'),
                ]),
                $this->category('Pişiklər', [
                    $this->category('Cins pişiklər'),
                    $this->category('Bala pişiklər'),
                    $this->category('Pişik aksesuarları'),
                    $this->category('Pişik yemləri'),
                ]),
                $this->category('Quşlar'),
                $this->category('Balıqlar və akvariumlar'),
                $this->category('Kənd təsərrüfatı heyvanları'),
                $this->category('Heyvanlar üçün məhsullar'),
            ], 'https://images.unsplash.com/photo-1450778869180-41d0601e046e?auto=format&fit=crop&w=640&q=80'),
            $this->category('Biznes üçün avadanlıq', [
                $this->category('Mağaza avadanlığı', [
                    $this->category('Vitrinlər'),
                    $this->category('Kassa aparatları'),
                    $this->category('Rəflər'),
                    $this->category('Barkod skanerləri'),
                ]),
                $this->category('Restoran avadanlığı', [
                    $this->category('Sobalar'),
                    $this->category('Soyuducu vitrinlər'),
                    $this->category('Qəhvə aparatları'),
                    $this->category('Mətbəx avadanlığı'),
                ]),
                $this->category('Ofis avadanlığı'),
                $this->category('İstehsalat avadanlığı'),
                $this->category('Tibbi avadanlıq'),
                $this->category('Salon avadanlığı'),
            ], 'https://images.unsplash.com/photo-1497366754035-f200968a6e72?auto=format&fit=crop&w=640&q=80'),
            $this->category('Uşaq dünyası', [
                $this->category('Uşaq geyimi və ayaqqabısı', [
                    $this->category('Uşaq geyimi'),
                    $this->category('Uşaq ayaqqabısı'),
                    $this->category('Məktəb forması'),
                    $this->category('Yeni doğulanlar üçün'),
                ]),
                $this->category('Uşaq arabaları və oturacaqlar', [
                    $this->category('Uşaq arabaları'),
                    $this->category('Avtokreslolar'),
                    $this->category('Uşaq çantaları'),
                ]),
                $this->category('Oyuncaqlar', [
                    $this->category('Konstruktorlar'),
                    $this->category('Kuklalar'),
                    $this->category('Maşın oyuncaqları'),
                    $this->category('İnkişaf etdirici oyuncaqlar'),
                ]),
                $this->category('Uşaq mebeli'),
                $this->category('Məktəb ləvazimatları'),
                $this->category('Uşaq qidası və gigiyena'),
            ], 'https://images.unsplash.com/photo-1515488042361-ee00e0ddd4e4?auto=format&fit=crop&w=640&q=80'),
            $this->category('Tibbi məhsullar', [
                $this->category('Tibbi avadanlıq'),
                $this->category('Ortopedik məhsullar'),
                $this->category('Gigiyena məhsulları'),
                $this->category('Vitaminlər və əlavələr'),
                $this->category('Maskalar və qoruyucu vasitələr'),
            ], 'https://images.unsplash.com/photo-1584362917165-526a968579e8?auto=format&fit=crop&w=640&q=80'),
        ];
    }
}
