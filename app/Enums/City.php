<?php

namespace App\Enums;

enum City: string
{
    case BAKU = 'Baku';
    case GANJA = 'Ganja';
    case SUMQAYIT = 'Sumgayit';
    case MINGACEVIR = 'Mingachevir';
    case LENKARAN = 'Lankaran';
    case SHAKI = 'Shaki';
    case SHIRVAN = 'Shirvan';
    case NAFTALAN = 'Naftalan';
    case YEVLAX = 'Yevlakh';
    case KHANKENDI = 'Khankendi';
    case NAKHCHIVAN = 'Nakhchivan';
    case KHIRDALAN = 'Khirdalan';
    case AGDAM = 'Agdam';
    case AGDASH = 'Agdash';
    case AGCABEDI = 'Agjabadi';
    case AGSU = 'Agsu';
    case ASTARA = 'Astara';
    case BABEK = 'Babek';
    case BALAKAN = 'Balakan';
    case BEYLAGAN = 'Beylagan';
    case BILASUVAR = 'Bilasuvar';
    case DASHKESAN = 'Dashkasan';
    case FUZULI = 'Fuzuli';
    case GADABAY = 'Gadabay';
    case GOYCHAY = 'Goychay';
    case GOYGOL = 'Goygol';
    case HAJIGABUL = 'Hajigabul';
    case IMISHLI = 'Imishli';
    case ISMAYILLI = 'Ismayilli';
    case JALILABAD = 'Jalilabad';
    case KALBAJAR = 'Kalbajar';
    case KURDAMIR = 'Kurdamir';
    case LACHIN = 'Lachin';
    case LERIK = 'Lerik';
    case MASALLI = 'Masalli';
    case NEFTCHALA = 'Neftchala';
    case OGUZ = 'Oguz';
    case QABALA = 'Qabala';
    case QAX = 'Qakh';
    case GAZAKH = 'Gazakh';
    case QUBA = 'Quba';
    case QUBADLI = 'Qubadli';
    case QUSAR = 'Qusar';
    case SAATLI = 'Saatli';
    case SABIRABAD = 'Sabirabad';
    case SHABRAN = 'Shabran';
    case SHAMAKHI = 'Shamakhi';
    case SHAMKIR = 'Shamkir';
    case SIYAZAN = 'Siyazan';
    case TARTAR = 'Tartar';
    case TOVUZ = 'Tovuz';
    case UJAR = 'Ujar';
    case YARDIMLI = 'Yardimli';
    case ZAKATALA = 'Zakatala';
    case ZARDAB = 'Zardab';
    case ZANGILAN = 'Zangilan';
    case JULFA = 'Julfa';
    case ORDUBAD = 'Ordubad';
    case SHAHBUZ = 'Shahbuz';
    case SEDARAK = 'Sadarak';
    case SHARUR = 'Sharur';
    case SAGOL = 'Sagol';
    case SIMCITY = 'Simcity';
    case ALBERT = 'Albert';
    case HAYDAY = 'Hayday';

    // DB-dən gələn əlavə dəyərlər
    case AGSTAFA = 'Agstafa';
    case BERDE = 'Berde';
    case SARAY_QESEBE = 'SarayQesebe';
    case XACMAZ = 'Xacmaz';
    case BALAKEN = 'Balaken';
    case SAMUX = 'Samux';
    case GORANBOY = 'Goranboy';
    case MASAZIR = 'Masazir';
    case BAKI_XETAI = 'BakiXetaiRayonu';
    case QOBUSTAN = 'Qobustan';
    case USKUDAR = 'Uskudar';
    case SERUR = 'Serur';
    case XIZI = 'Xizi';
    case BAKI_SABAYIL = 'BakiSabayilRayon';
    case BAKI_XEZER = 'BakiXezerRayonu';
    case BAKI_NESIMI = 'BakiNesimiRayon';
    case BAKI_QARADAG = 'BakiQaradagRayon';
    case DASKESEN = 'Daskesen';
    case HACIQABUL = 'Haciqabul';
    case BAKI_NIZAMI = 'BakiNizamiRayonu';
    case BAKI_BINEQEDI = 'BakiBineqediRayon';
    case PIRALLAHI = 'PirallahiRayonu';
    case BAKI_YASAMAL = 'BakiYasamalRayonu';
    case TERTER = 'Terter';
    case KENGERLI = 'Kengerli';
    case QEBELE = 'Qebele';
    case BAKI_SABUNCU = 'BakiSabuncuRayonu';
    case ABSERON = 'Abseron';
    case SUSA = 'Susa';
    case CEBRAYIL = 'Cebrayil';
    case MEHDIABAD = 'Mehdiabad';
    case SALYAN = 'Salyan';
    case BAKI_NERIMANOV = 'BakiNerimanovRayonu';
    case BAKI_SURAXANI = 'BakiSuraxaniRayon';
    case XIRDALAN = 'Xirdalan';
    case XOCALI = 'Xocali';
    case XOCAVEND = 'Xocavend';
    case SUMQAYIT_AZ = 'Sumqayit';

    public static function list(): array
    {
        return [
            'Hayday' => 'Hayday',
            'Albert' => 'albert',
            'Simcity' => 'Simcity edit',
            'Sagol' => 'Sagol',
            'Baku' => 'Bakı',
            'Ganja' => 'Gəncə',
            'Sumgayit' => 'Sumqayıt',
            'Mingachevir' => 'Mingəçevir',
            'Lankaran' => 'Lənkəran',
            'Shaki' => 'Şəki',
            'Shirvan' => 'Şirvan',
            'Naftalan' => 'Naftalan',
            'Yevlakh' => 'Yevlax',
            'Khankendi' => 'Xankəndi',
            'Nakhchivan' => 'Naxçıvan',
            'Khirdalan' => 'Xırdalan',
            'Agdam' => 'Ağdam',
            'Agdash' => 'Ağdaş',
            'Agjabadi' => 'Ağcabədi',
            'Agsu' => 'Ağsu',
            'Astara' => 'Astara',
            'Babek' => 'Babək',
            'Balakan' => 'Balakən',
            'Beylagan' => 'Beyləqan',
            'Bilasuvar' => 'Biləsuvar',
            'Dashkasan' => 'Daşkəsən',
            'Fuzuli' => 'Füzuli',
            'Gadabay' => 'Gədəbəy',
            'Goychay' => 'Göyçay',
            'Goygol' => 'Göygöl',
            'Hajigabul' => 'Hacıqabul',
            'Imishli' => 'İmişli',
            'Ismayilli' => 'İsmayıllı',
            'Jalilabad' => 'Cəlilabad',
            'Kalbajar' => 'Kəlbəcər',
            'Kurdamir' => 'Kürdəmir',
            'Lachin' => 'Laçın',
            'Lerik' => 'Lerik',
            'Masalli' => 'Masallı',
            'Neftchala' => 'Neftçala',
            'Oguz' => 'Oğuz',
            'Qabala' => 'Qəbələ',
            'Qakh' => 'Qax',
            'Qazakh' => 'Qazax',
            'Quba' => 'Quba',
            'Qubadli' => 'Qubadlı',
            'Qusar' => 'Qusar',
            'Saatli' => 'Saatlı',
            'Sabirabad' => 'Sabirabad',
            'Shabran' => 'Şabran',
            'Shamakhi' => 'Şamaxı',
            'Shamkir' => 'Şəmkir',
            'Siyazan' => 'Siyəzən',
            'Tartar' => 'Tərtər',
            'Tovuz' => 'Tovuz',
            'Ujar' => 'Ucar',
            'Yardimli' => 'Yardımlı',
            'Zakatala' => 'Zaqatala',
            'Zardab' => 'Zərdab',
            'Zangilan' => 'Zəngilan',
            'Julfa' => 'Culfa',
            'Ordubad' => 'Ordubad',
            'Shahbuz' => 'Şahbuz',
            'Sadarak' => 'Sədərək',
            'Sharur' => 'Şərur',
            // Əlavə dəyərlər
            'Agstafa' => 'Ağstafa',
            'Berde' => 'Bərdə',
            'SarayQesebe' => 'Saray Qəsəbəsi',
            'Xacmaz' => 'Xaçmaz',
            'Balaken' => 'Balakən',
            'Samux' => 'Samux',
            'Goranboy' => 'Goranboy',
            'Masazir' => 'Maşazır',
            'BakiXetaiRayonu' => 'Bakı Xətai Rayonu',
            'Qobustan' => 'Qobustan',
            'Uskudar' => 'Üskudar',
            'Serur' => 'Şərur',
            'Xizi' => 'Xızı',
            'BakiSabayilRayon' => 'Bakı Səbail Rayonu',
            'BakiXezerRayonu' => 'Bakı Xəzər Rayonu',
            'BakiNesimiRayon' => 'Bakı Nəsimi Rayonu',
            'BakiQaradagRayon' => 'Bakı Qaradağ Rayonu',
            'Daskesen' => 'Daşkəsən',
            'Haciqabul' => 'Hacıqabul',
            'BakiNizamiRayonu' => 'Bakı Nizami Rayonu',
            'BakiBineqediRayon' => 'Bakı Binəqədi Rayonu',
            'PirallahiRayonu' => 'Pirallahı Rayonu',
            'BakiYasamalRayonu' => 'Bakı Yasamal Rayonu',
            'Terter' => 'Tərtər',
            'Kengerli' => 'Kəngərli',
            'Qebele' => 'Qəbələ',
            'BakiSabuncuRayonu' => 'Bakı Sabunçu Rayonu',
            'Abseron' => 'Abşeron',
            'Susa' => 'Şuşa',
            'Cebrayil' => 'Cəbrayıl',
            'Mehdiabad' => 'Mehdiabad',
            'Salyan' => 'Salyan',
            'BakiNerimanovRayonu' => 'Bakı Nərimanov Rayonu',
            'BakiSuraxaniRayon' => 'Bakı Suraxanı Rayonu',
            'Xirdalan' => 'Xırdalan',
            'Xocali' => 'Xocalı',
            'Xocavend' => 'Xocavənd',
            'Sumqayit' => 'Sumqayıt',
        ];
    }

    public static function search(string $input): ?self
    {
        $input = trim(mb_strtolower($input));

        $list = static::list();

        foreach ($list as $en => $az) {
            $enLower = mb_strtolower($en);
            $azLower = mb_strtolower($az);

            if ($input === $enLower || $input === $azLower) {
                return self::from($en);
            }
        }

        foreach ($list as $en => $az) {
            $enLower = mb_strtolower($en);
            $azLower = mb_strtolower($az);
            if (str_contains($enLower, $input) || str_contains($azLower, $input)) {
                return self::from($en);
            }
        }

        return null;
    }
}
