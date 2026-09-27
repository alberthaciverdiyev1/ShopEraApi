<?php

namespace Modules\Listing\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Listing\Http\Entities\ListingAttribute;
use Modules\Listing\Http\Entities\ListingAttributeOption;
use Modules\Listing\Http\Entities\ListingSection;

/**
 * The two sections the classifieds open with — vehicles and property — with
 * the fields the local sites made everyone expect.
 *
 * It only ever adds what is missing. Run it twice and nothing is duplicated;
 * more importantly, a label the admin has since rewritten, a field they
 * switched off and an option they renamed all stay as they left them.
 */
class ListingCatalogueSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $this->vehicles();
            $this->property();
            $this->badges();
        });
    }

    private function vehicles(): void
    {
        $section = $this->section('vehicles', [
            'az' => 'Nəqliyyat',
            'ru' => 'Транспорт',
            'en' => 'Vehicles',
            'tr' => 'Araçlar',
        ], [
            'template' => 'vehicle',
            // The client asked for every ad to go live at once; the switch
            // stays per section, so a moderator can be put back in front of
            // cars from the panel whenever they want.
            'auto_approve' => true,
            'duration_days' => 30,
            'sort_order' => 1,
            'warning_text' => [
                'az' => 'Maşını görmədən beh və ya öncədən ödəniş göndərməyin.',
                'ru' => 'Не отправляйте задаток или предоплату, не увидев автомобиль.',
                'en' => 'Never send a deposit or prepayment before seeing the car.',
                'tr' => 'Aracı görmeden kapora veya ön ödeme göndermeyin.',
            ],
        ]);

        $make = $this->field($section, 'make', [
            'az' => 'Marka', 'ru' => 'Марка', 'en' => 'Make', 'tr' => 'Marka',
        ], ['type' => 'select', 'is_required' => true, 'in_filter' => true, 'sort_order' => 1]);

        $model = $this->field($section, 'model', [
            'az' => 'Model', 'ru' => 'Модель', 'en' => 'Model', 'tr' => 'Model',
        ], ['type' => 'select', 'is_required' => true, 'in_filter' => true, 'sort_order' => 2, 'parent_id' => $make->id]);

        // Model options hang off the make, which is what narrows the second
        // list once the first is answered.
        foreach (VehicleCatalogue::makes() as $key => [$name, $models]) {
            $parent = $this->option($make, $key, $this->plain($name), null, $this->nextOrder($make));

            foreach ($models as $index => $modelName) {
                $this->option($model, $key . '-' . $this->slug($modelName), $this->plain($modelName), $parent->id, $index + 1);
            }
        }

        $this->field($section, 'year', [
            'az' => 'Buraxılış ili', 'ru' => 'Год выпуска', 'en' => 'Year', 'tr' => 'Model yılı',
        ], ['type' => 'number', 'is_required' => true, 'in_filter' => true, 'is_range' => true, 'in_card' => true, 'sort_order' => 3]);

        $this->field($section, 'mileage', [
            'az' => 'Yürüş', 'ru' => 'Пробег', 'en' => 'Mileage', 'tr' => 'Kilometre',
        ], ['type' => 'number', 'unit' => 'km', 'in_filter' => true, 'is_range' => true, 'in_card' => true, 'sort_order' => 4]);

        $this->choices($section, 'fuel', [
            'az' => 'Yanacaq növü', 'ru' => 'Тип топлива', 'en' => 'Fuel', 'tr' => 'Yakıt',
        ], ['is_required' => true, 'in_filter' => true, 'sort_order' => 5], [
            'petrol' => ['az' => 'Benzin', 'ru' => 'Бензин', 'en' => 'Petrol', 'tr' => 'Benzin'],
            'diesel' => ['az' => 'Dizel', 'ru' => 'Дизель', 'en' => 'Diesel', 'tr' => 'Dizel'],
            'gas' => ['az' => 'Qaz', 'ru' => 'Газ', 'en' => 'Gas', 'tr' => 'LPG'],
            'hybrid' => ['az' => 'Hibrid', 'ru' => 'Гибрид', 'en' => 'Hybrid', 'tr' => 'Hibrit'],
            'electric' => ['az' => 'Elektro', 'ru' => 'Электро', 'en' => 'Electric', 'tr' => 'Elektrik'],
        ]);

        $this->choices($section, 'transmission', [
            'az' => 'Sürətlər qutusu', 'ru' => 'Коробка передач', 'en' => 'Transmission', 'tr' => 'Vites',
        ], ['in_filter' => true, 'sort_order' => 6], [
            'manual' => ['az' => 'Mexaniki', 'ru' => 'Механика', 'en' => 'Manual', 'tr' => 'Manuel'],
            'automatic' => ['az' => 'Avtomat', 'ru' => 'Автомат', 'en' => 'Automatic', 'tr' => 'Otomatik'],
            'robot' => ['az' => 'Robot', 'ru' => 'Робот', 'en' => 'Robotised', 'tr' => 'Robot'],
            'variator' => ['az' => 'Variator', 'ru' => 'Вариатор', 'en' => 'CVT', 'tr' => 'CVT'],
        ]);

        $this->choices($section, 'body', [
            'az' => 'Ban növü', 'ru' => 'Тип кузова', 'en' => 'Body type', 'tr' => 'Kasa tipi',
        ], ['in_filter' => true, 'sort_order' => 7], [
            'sedan' => ['az' => 'Sedan', 'ru' => 'Седан', 'en' => 'Sedan', 'tr' => 'Sedan'],
            'hatchback' => ['az' => 'Hetçbek', 'ru' => 'Хэтчбек', 'en' => 'Hatchback', 'tr' => 'Hatchback'],
            'suv' => ['az' => 'Offroader / SUV', 'ru' => 'Внедорожник / SUV', 'en' => 'SUV', 'tr' => 'SUV'],
            'universal' => ['az' => 'Universal', 'ru' => 'Универсал', 'en' => 'Estate', 'tr' => 'Station wagon'],
            'coupe' => ['az' => 'Kupe', 'ru' => 'Купе', 'en' => 'Coupe', 'tr' => 'Coupe'],
            'minivan' => ['az' => 'Minivan', 'ru' => 'Минивэн', 'en' => 'Minivan', 'tr' => 'Minivan'],
            'pickup' => ['az' => 'Pikap', 'ru' => 'Пикап', 'en' => 'Pickup', 'tr' => 'Pickup'],
            'cabriolet' => ['az' => 'Kabriolet', 'ru' => 'Кабриолет', 'en' => 'Cabriolet', 'tr' => 'Cabrio'],
            'van' => ['az' => 'Furqon', 'ru' => 'Фургон', 'en' => 'Van', 'tr' => 'Panelvan'],
            'bus' => ['az' => 'Avtobus', 'ru' => 'Автобус', 'en' => 'Bus', 'tr' => 'Otobüs'],
            'motorcycle' => ['az' => 'Motosiklet', 'ru' => 'Мотоцикл', 'en' => 'Motorcycle', 'tr' => 'Motosiklet'],
            'truck' => ['az' => 'Yük maşını', 'ru' => 'Грузовик', 'en' => 'Truck', 'tr' => 'Kamyon'],
        ]);

        $this->field($section, 'engine_volume', [
            'az' => 'Mühərrikin həcmi', 'ru' => 'Объём двигателя', 'en' => 'Engine volume', 'tr' => 'Motor hacmi',
        ], ['type' => 'number', 'unit' => 'sm³', 'in_filter' => true, 'is_range' => true, 'in_card' => true, 'sort_order' => 8]);

        $this->field($section, 'power', [
            'az' => 'Mühərrikin gücü', 'ru' => 'Мощность двигателя', 'en' => 'Engine power', 'tr' => 'Motor gücü',
        ], ['type' => 'number', 'unit' => 'a.g.', 'in_filter' => true, 'is_range' => true, 'sort_order' => 9]);

        $this->choices($section, 'drive', [
            'az' => 'Ötürücü', 'ru' => 'Привод', 'en' => 'Drive', 'tr' => 'Çekiş',
        ], ['in_filter' => true, 'sort_order' => 10], [
            'front' => ['az' => 'Ön', 'ru' => 'Передний', 'en' => 'Front', 'tr' => 'Önden'],
            'rear' => ['az' => 'Arxa', 'ru' => 'Задний', 'en' => 'Rear', 'tr' => 'Arkadan'],
            'full' => ['az' => 'Tam', 'ru' => 'Полный', 'en' => 'All-wheel', 'tr' => '4x4'],
        ]);

        $this->choices($section, 'color', [
            'az' => 'Rəng', 'ru' => 'Цвет', 'en' => 'Colour', 'tr' => 'Renk',
        ], ['in_filter' => true, 'sort_order' => 11], [
            'white' => ['az' => 'Ağ', 'ru' => 'Белый', 'en' => 'White', 'tr' => 'Beyaz'],
            'black' => ['az' => 'Qara', 'ru' => 'Чёрный', 'en' => 'Black', 'tr' => 'Siyah'],
            'silver' => ['az' => 'Gümüşü', 'ru' => 'Серебристый', 'en' => 'Silver', 'tr' => 'Gümüş'],
            'grey' => ['az' => 'Boz', 'ru' => 'Серый', 'en' => 'Grey', 'tr' => 'Gri'],
            'blue' => ['az' => 'Mavi', 'ru' => 'Синий', 'en' => 'Blue', 'tr' => 'Mavi'],
            'red' => ['az' => 'Qırmızı', 'ru' => 'Красный', 'en' => 'Red', 'tr' => 'Kırmızı'],
            'green' => ['az' => 'Yaşıl', 'ru' => 'Зелёный', 'en' => 'Green', 'tr' => 'Yeşil'],
            'beige' => ['az' => 'Bej', 'ru' => 'Бежевый', 'en' => 'Beige', 'tr' => 'Bej'],
            'brown' => ['az' => 'Qəhvəyi', 'ru' => 'Коричневый', 'en' => 'Brown', 'tr' => 'Kahverengi'],
            'yellow' => ['az' => 'Sarı', 'ru' => 'Жёлтый', 'en' => 'Yellow', 'tr' => 'Sarı'],
            'other' => ['az' => 'Digər', 'ru' => 'Другой', 'en' => 'Other', 'tr' => 'Diğer'],
        ]);

        $this->choices($section, 'condition', [
            'az' => 'Vəziyyəti', 'ru' => 'Состояние', 'en' => 'Condition', 'tr' => 'Durum',
        ], ['in_filter' => true, 'sort_order' => 12], [
            'new' => ['az' => 'Yeni', 'ru' => 'Новый', 'en' => 'New', 'tr' => 'Sıfır'],
            'used' => ['az' => 'Sürülmüş', 'ru' => 'С пробегом', 'en' => 'Used', 'tr' => 'İkinci el'],
        ]);

        $this->choices($section, 'market', [
            'az' => 'Hansı bazar üçün yığılıb', 'ru' => 'Для какого рынка собран', 'en' => 'Assembled for', 'tr' => 'Hangi pazar için',
        ], ['in_filter' => true, 'sort_order' => 13], [
            'europe' => ['az' => 'Avropa', 'ru' => 'Европа', 'en' => 'Europe', 'tr' => 'Avrupa'],
            'america' => ['az' => 'Amerika', 'ru' => 'Америка', 'en' => 'America', 'tr' => 'Amerika'],
            'russia' => ['az' => 'Rusiya', 'ru' => 'Россия', 'en' => 'Russia', 'tr' => 'Rusya'],
            'korea' => ['az' => 'Koreya', 'ru' => 'Корея', 'en' => 'Korea', 'tr' => 'Kore'],
            'china' => ['az' => 'Çin', 'ru' => 'Китай', 'en' => 'China', 'tr' => 'Çin'],
            'japan' => ['az' => 'Yaponiya', 'ru' => 'Япония', 'en' => 'Japan', 'tr' => 'Japonya'],
            'other' => ['az' => 'Digər', 'ru' => 'Другой', 'en' => 'Other', 'tr' => 'Diğer'],
        ]);

        $this->field($section, 'seats', [
            'az' => 'Oturacaqların sayı', 'ru' => 'Количество мест', 'en' => 'Seats', 'tr' => 'Koltuk sayısı',
        ], ['type' => 'number', 'in_filter' => true, 'sort_order' => 14]);

        // Çoxlu seçim: bir maşında bunların bir neçəsi olur.
        $this->choices($section, 'equipment', [
            'az' => 'Əlavə avadanlıq', 'ru' => 'Дополнительное оборудование', 'en' => 'Extras', 'tr' => 'Ek donanım',
        ], ['type' => 'multiselect', 'in_filter' => true, 'sort_order' => 15], [
            'sunroof' => ['az' => 'Lyuk', 'ru' => 'Люк', 'en' => 'Sunroof', 'tr' => 'Sunroof'],
            'leather' => ['az' => 'Dəri salon', 'ru' => 'Кожаный салон', 'en' => 'Leather seats', 'tr' => 'Deri döşeme'],
            'xenon' => ['az' => 'Ksenon lampalar', 'ru' => 'Ксеноновые лампы', 'en' => 'Xenon lights', 'tr' => 'Xenon farlar'],
            'rear_camera' => ['az' => 'Arxa görüntü kamerası', 'ru' => 'Камера заднего вида', 'en' => 'Rear camera', 'tr' => 'Geri görüş kamerası'],
            'parking_sensor' => ['az' => 'Park radarı', 'ru' => 'Парктроник', 'en' => 'Parking sensors', 'tr' => 'Park sensörü'],
            'seat_heating' => ['az' => 'Oturacaqların isidilməsi', 'ru' => 'Подогрев сидений', 'en' => 'Heated seats', 'tr' => 'Koltuk ısıtma'],
            'seat_ventilation' => ['az' => 'Oturacaqların ventilyasiyası', 'ru' => 'Вентиляция сидений', 'en' => 'Ventilated seats', 'tr' => 'Koltuk havalandırma'],
            'climate' => ['az' => 'Kondisioner', 'ru' => 'Кондиционер', 'en' => 'Air conditioning', 'tr' => 'Klima'],
            'alarm' => ['az' => 'Siqnalizasiya', 'ru' => 'Сигнализация', 'en' => 'Alarm', 'tr' => 'Alarm'],
            'abs' => ['az' => 'ABS', 'ru' => 'ABS', 'en' => 'ABS', 'tr' => 'ABS'],
            'rain_sensor' => ['az' => 'Yağış sensoru', 'ru' => 'Датчик дождя', 'en' => 'Rain sensor', 'tr' => 'Yağmur sensörü'],
            'central_lock' => ['az' => 'Mərkəzi qapanma', 'ru' => 'Центральный замок', 'en' => 'Central locking', 'tr' => 'Merkezi kilit'],
            'side_curtains' => ['az' => 'Yan pərdələr', 'ru' => 'Боковые шторки', 'en' => 'Side curtains', 'tr' => 'Yan perdeler'],
        ]);

        $this->field($section, 'crashed', [
            'az' => 'Vuruğu var', 'ru' => 'Битый', 'en' => 'Damaged', 'tr' => 'Hasarlı',
        ], ['type' => 'boolean', 'in_filter' => true, 'sort_order' => 16]);

        $this->field($section, 'painted', [
            'az' => 'Rənglənib', 'ru' => 'Крашеный', 'en' => 'Repainted', 'tr' => 'Boyalı',
        ], ['type' => 'boolean', 'in_filter' => true, 'sort_order' => 17]);

        $this->field($section, 'credit', [
            'az' => 'Kredit', 'ru' => 'Кредит', 'en' => 'Credit', 'tr' => 'Kredi',
        ], ['type' => 'boolean', 'in_filter' => true, 'sort_order' => 18]);

        $this->field($section, 'barter', [
            'az' => 'Barter', 'ru' => 'Обмен', 'en' => 'Barter', 'tr' => 'Takas',
        ], ['type' => 'boolean', 'in_filter' => true, 'sort_order' => 19]);

        // Nəqliyyatda xəritə sahəsi yoxdur: maşının yeri elanda mənalı deyil,
        // bölmə açıldıqca admin onu özü əlavə edə bilər.
        $this->video($section, 20);
    }

    private function property(): void
    {
        $section = $this->section('property', [
            'az' => 'Daşınmaz əmlak',
            'ru' => 'Недвижимость',
            'en' => 'Property',
            'tr' => 'Emlak',
        ], [
            'template' => 'property',
            'auto_approve' => true,
            'duration_days' => 30,
            'sort_order' => 2,
            'warning_text' => [
                'az' => 'Mənzili görmədən beh göndərməyin, sənədləri yoxlayın.',
                'ru' => 'Не отправляйте задаток, не осмотрев жильё, и проверяйте документы.',
                'en' => 'Never send a deposit before viewing, and check the documents.',
                'tr' => 'Evi görmeden kapora göndermeyin, belgeleri kontrol edin.',
            ],
        ]);

        $this->choices($section, 'deal', [
            'az' => 'Əməliyyat', 'ru' => 'Сделка', 'en' => 'Deal', 'tr' => 'İşlem',
        ], ['is_required' => true, 'in_filter' => true, 'sort_order' => 1], [
            'sale' => ['az' => 'Satılır', 'ru' => 'Продаётся', 'en' => 'For sale', 'tr' => 'Satılık'],
            'rent_monthly' => ['az' => 'Aylıq kirayə', 'ru' => 'Аренда помесячно', 'en' => 'Monthly rent', 'tr' => 'Aylık kiralık'],
            'rent_daily' => ['az' => 'Günlük kirayə', 'ru' => 'Посуточно', 'en' => 'Daily rent', 'tr' => 'Günlük kiralık'],
        ]);

        $this->choices($section, 'property_type', [
            'az' => 'Əmlakın növü', 'ru' => 'Тип недвижимости', 'en' => 'Property type', 'tr' => 'Emlak tipi',
        ], ['is_required' => true, 'in_filter' => true, 'sort_order' => 2], [
            'new_building' => ['az' => 'Yeni tikili', 'ru' => 'Новостройка', 'en' => 'New building', 'tr' => 'Yeni bina'],
            'old_building' => ['az' => 'Köhnə tikili', 'ru' => 'Старый фонд', 'en' => 'Old building', 'tr' => 'Eski bina'],
            'house' => ['az' => 'Həyət evi / Villa', 'ru' => 'Дом / Вилла', 'en' => 'House / Villa', 'tr' => 'Müstakil ev / Villa'],
            'office' => ['az' => 'Ofis', 'ru' => 'Офис', 'en' => 'Office', 'tr' => 'Ofis'],
            'garage' => ['az' => 'Qaraj', 'ru' => 'Гараж', 'en' => 'Garage', 'tr' => 'Garaj'],
            'land' => ['az' => 'Torpaq', 'ru' => 'Земля', 'en' => 'Land', 'tr' => 'Arsa'],
            'commercial' => ['az' => 'Obyekt', 'ru' => 'Коммерческий объект', 'en' => 'Commercial', 'tr' => 'İşyeri'],
        ]);

        $this->field($section, 'rooms', [
            'az' => 'Otaq sayı', 'ru' => 'Количество комнат', 'en' => 'Rooms', 'tr' => 'Oda sayısı',
        ], ['type' => 'number', 'unit' => 'otaqlı', 'in_filter' => true, 'is_range' => true, 'in_card' => true, 'sort_order' => 3]);

        $this->field($section, 'area', [
            'az' => 'Sahə', 'ru' => 'Площадь', 'en' => 'Area', 'tr' => 'Alan',
        ], ['type' => 'number', 'unit' => 'm²', 'is_required' => true, 'in_filter' => true, 'is_range' => true, 'in_card' => true, 'sort_order' => 4]);

        $this->field($section, 'floor', [
            'az' => 'Mərtəbə', 'ru' => 'Этаж', 'en' => 'Floor', 'tr' => 'Bulunduğu kat',
        ], ['type' => 'number', 'in_filter' => true, 'is_range' => true, 'in_card' => true, 'sort_order' => 5]);

        $this->field($section, 'floors_total', [
            'az' => 'Mərtəbə sayı', 'ru' => 'Этажность', 'en' => 'Floors in building', 'tr' => 'Kat sayısı',
        ], ['type' => 'number', 'unit' => 'mərtəbə', 'in_card' => true, 'sort_order' => 6]);

        $this->choices($section, 'repair', [
            'az' => 'Təmir', 'ru' => 'Ремонт', 'en' => 'Condition', 'tr' => 'Durum',
        ], ['in_filter' => true, 'sort_order' => 7], [
            'repaired' => ['az' => 'Təmirli', 'ru' => 'С ремонтом', 'en' => 'Renovated', 'tr' => 'Tadilatlı'],
            'unrepaired' => ['az' => 'Təmirsiz', 'ru' => 'Без ремонта', 'en' => 'Not renovated', 'tr' => 'Tadilatsız'],
        ]);

        $this->field($section, 'land_area', [
            'az' => 'Torpaq sahəsi', 'ru' => 'Площадь участка', 'en' => 'Land area', 'tr' => 'Arsa alanı',
        ], ['type' => 'number', 'unit' => 'sot', 'sort_order' => 8]);

        $this->field($section, 'furnished', [
            'az' => 'Əşyalı', 'ru' => 'С мебелью', 'en' => 'Furnished', 'tr' => 'Eşyalı',
        ], ['type' => 'boolean', 'in_filter' => true, 'sort_order' => 9]);

        $this->field($section, 'documents', [
            'az' => 'Çıxarış var', 'ru' => 'Есть выписка', 'en' => 'Title deed', 'tr' => 'Tapu var',
        ], ['type' => 'boolean', 'in_filter' => true, 'sort_order' => 10]);

        $this->field($section, 'mortgage', [
            'az' => 'İpoteka', 'ru' => 'Ипотека', 'en' => 'Mortgage', 'tr' => 'İpotek',
        ], ['type' => 'boolean', 'in_filter' => true, 'sort_order' => 11]);

        $this->video($section, 12);

        $this->field($section, 'location', [
            'az' => 'Xəritədə yeri', 'ru' => 'Место на карте', 'en' => 'Location on the map', 'tr' => 'Haritada konum',
        ], ['type' => 'location', 'sort_order' => 13]);
    }

    /**
     * A YouTube link. Optional and unfilterable - an ad without it looks the
     * same, only without the player.
     */
    private function video(ListingSection $section, int $order): void
    {
        $this->field($section, 'video', [
            'az' => 'Video (YouTube)', 'ru' => 'Видео (YouTube)', 'en' => 'Video (YouTube)', 'tr' => 'Video (YouTube)',
        ], ['type' => 'youtube', 'sort_order' => $order]);
    }

    /**
     * Ties answers to the badges the app draws, and gives the colour choices a
     * swatch each.
     *
     * Written only where the admin has left it empty, so a mapping they change
     * in the panel survives a re-run. no_damage sits on both "vuruğu var" and
     * "rənglənib" with badge_when=false, which is how a badge ends up meaning
     * "neither" rather than "either".
     */
    private function badges(): void
    {
        $fields = [
            'vehicles' => [
                'credit' => ['credit', 'true'],
                'barter' => ['barter', 'true'],
                'crashed' => ['no_damage', 'false'],
                'painted' => ['no_damage', 'false'],
            ],
            'property' => [
                'mortgage' => ['mortgage', 'true'],
                'documents' => ['extract', 'true'],
            ],
        ];

        foreach ($fields as $sectionKey => $map) {
            $section = ListingSection::where('key', $sectionKey)->first();

            if (! $section) {
                continue;
            }

            foreach ($map as $fieldKey => [$badge, $when]) {
                ListingAttribute::where('section_id', $section->id)
                    ->where('key', $fieldKey)
                    ->whereNull('badge_key')
                    ->update(['badge_key' => $badge, 'badge_when' => $when]);
            }
        }

        // A single choice can stand for a badge on its own: a renovated flat
        // is worth marking, an unrenovated one is not.
        $this->optionBadge('property', 'repair', 'repaired', 'repair');

        // An ad names itself from its own answers.
        $titles = ['vehicles' => '{make} {model}', 'property' => '{property_type}, {city}'];

        foreach ($titles as $key => $template) {
            ListingSection::where('key', $key)
                ->whereNull('title_template')
                ->update(['title_template' => $template]);
        }

        $colours = [
            'white' => '#FFFFFF', 'black' => '#111111', 'silver' => '#C0C4CC',
            'grey' => '#8A94A6', 'blue' => '#1E3A8A', 'red' => '#8B2020',
            'green' => '#1E6F3C', 'beige' => '#E3D5B8', 'brown' => '#6B4A2F',
            'yellow' => '#E8B71A',
        ];

        $field = $this->fieldOf('vehicles', 'color');

        if ($field) {
            foreach ($colours as $value => $hex) {
                ListingAttributeOption::where('attribute_id', $field->id)
                    ->where('value', $value)
                    ->whereNull('color_hex')
                    ->update(['color_hex' => $hex]);
            }
        }
    }

    private function optionBadge(string $sectionKey, string $fieldKey, string $optionValue, string $badge): void
    {
        $field = $this->fieldOf($sectionKey, $fieldKey);

        if (! $field) {
            return;
        }

        ListingAttributeOption::where('attribute_id', $field->id)
            ->where('value', $optionValue)
            ->whereNull('badge_key')
            ->update(['badge_key' => $badge]);
    }

    private function fieldOf(string $sectionKey, string $fieldKey): ?ListingAttribute
    {
        $section = ListingSection::where('key', $sectionKey)->first();

        return $section
            ? ListingAttribute::where('section_id', $section->id)->where('key', $fieldKey)->first()
            : null;
    }

    private function section(string $key, array $name, array $extra): ListingSection
    {
        $section = ListingSection::withTrashed()->where('key', $key)->first();

        if ($section) {
            return $section;
        }

        return ListingSection::create(array_merge(['key' => $key, 'name' => $name, 'is_active' => true], $extra));
    }

    private function field(ListingSection $section, string $key, array $label, array $extra): ListingAttribute
    {
        $field = ListingAttribute::where('section_id', $section->id)->where('key', $key)->first();

        if ($field) {
            return $field;
        }

        return ListingAttribute::create(array_merge([
            'section_id' => $section->id,
            'key' => $key,
            'label' => $label,
            'type' => 'text',
            'is_active' => true,
        ], $extra));
    }

    /** A choice field and its options in one go. */
    private function choices(ListingSection $section, string $key, array $label, array $extra, array $options): ListingAttribute
    {
        $field = $this->field($section, $key, $label, array_merge(['type' => 'select'], $extra));
        $order = $this->nextOrder($field) - 1;

        foreach ($options as $value => $optionLabel) {
            $this->option($field, (string) $value, $optionLabel, null, ++$order);
        }

        return $field;
    }

    private function option(ListingAttribute $field, string $value, array $label, ?int $parentId, int $sortOrder): ListingAttributeOption
    {
        $option = ListingAttributeOption::where('attribute_id', $field->id)->where('value', $value)->first();

        if ($option) {
            return $option;
        }

        return ListingAttributeOption::create([
            'attribute_id' => $field->id,
            'parent_option_id' => $parentId,
            'value' => $value,
            'label' => $label,
            'is_active' => true,
            'sort_order' => $sortOrder,
        ]);
    }

    private function nextOrder(ListingAttribute $field): int
    {
        return ((int) ListingAttributeOption::where('attribute_id', $field->id)->max('sort_order')) + 1;
    }

    /** A make or a model reads the same in every language. */
    private function plain(string $text): array
    {
        return ['az' => $text, 'ru' => $text, 'en' => $text, 'tr' => $text];
    }

    private function slug(string $text): string
    {
        $slug = preg_replace('/[^a-z0-9]+/', '-', mb_strtolower($text));

        return trim((string) $slug, '-');
    }
}
