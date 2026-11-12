<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Jenssegers\ImageHash\ImageHash;
use Jenssegers\ImageHash\Implementations\AverageHash;
use Jenssegers\ImageHash\Implementations\DifferenceHash;

if (! function_exists('hash_hex_to_signed_bigint')) {
    function hash_hex_to_signed_bigint(string $hex): string
    {
        $hex = strtolower(trim($hex));
        $hex = str_pad($hex, 16, '0', STR_PAD_LEFT);

        if (strlen($hex) < 16 || hexdec(substr($hex, 0, 1)) < 8) {
            return (string) hexdec($hex);
        }

        $high = hexdec(substr($hex, 0, 8));
        $low = hexdec(substr($hex, 8, 8));

        $value = ($high * 4294967296) + $low;
        $signed = $value - 18446744073709551616;

        return sprintf('%.0f', $signed);
    }
}

if (! function_exists('generate_image_hashes')) {
    function generate_image_hashes(string $path): array
    {
        $aHasher = new ImageHash(new AverageHash);
        $dHasher = new ImageHash(new DifferenceHash);

        $aHash = $aHasher->hash($path);
        $dHash = $dHasher->hash($path);

        return [
            'ahash' => hash_hex_to_signed_bigint($aHash->toHex()),
            'dhash' => hash_hex_to_signed_bigint($dHash->toHex()),
        ];
    }
}

if (! function_exists('generateImageEmbedding')) {
    function generateImageEmbedding(string $path): ?string
    {
        try {
            $response = Http::timeout(15)
                ->attach('file', file_get_contents($path), 'image.jpg')
                ->post('http://127.0.0.1:8765/embed/file');

            if (! $response->successful() || ! empty($response->json('error'))) {
                return null;
            }

            $embedding = $response->json('embedding');

            if (empty($embedding) || count($embedding) !== 512) {
                return null;
            }

            return '['.implode(',', $embedding).']';
        } catch (Throwable $e) {
            logger()->warning('Embedding generation failed', ['error' => $e->getMessage()]);

            return null;
        }
    }
}

if (! function_exists('prepare_search_image_for_hash')) {
    function prepare_search_image_for_hash(string $path): string
    {
        if (! file_exists($path)) {
            return $path;
        }

        $info = @getimagesize($path);

        if (! $info || empty($info['mime'])) {
            return $path;
        }

        $source = match ($info['mime']) {
            'image/jpeg' => @imagecreatefromjpeg($path),
            'image/png' => @imagecreatefrompng($path),
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : null,
            default => null,
        };

        if (! $source) {
            return $path;
        }

        $width = imagesx($source);
        $height = imagesy($source);

        if ($width < 40 || $height < 40) {
            imagedestroy($source);

            return $path;
        }

        $isEmptyPixel = function ($rgb): bool {
            $r = ($rgb >> 16) & 0xFF;
            $g = ($rgb >> 8) & 0xFF;
            $b = $rgb & 0xFF;

            return ($r < 28 && $g < 28 && $b < 28)
                || ($r > 242 && $g > 242 && $b > 242);
        };

        $rowIsEmpty = function (int $y) use ($source, $width, $isEmptyPixel): bool {
            $empty = 0;
            $checked = 0;
            $step = max(1, (int) floor($width / 90));

            for ($x = 0; $x < $width; $x += $step) {
                if ($isEmptyPixel(imagecolorat($source, $x, $y))) {
                    $empty++;
                }
                $checked++;
            }

            return $checked > 0 && ($empty / $checked) >= 0.92;
        };

        $colIsEmpty = function (int $x) use ($source, $height, $isEmptyPixel): bool {
            $empty = 0;
            $checked = 0;
            $step = max(1, (int) floor($height / 90));

            for ($y = 0; $y < $height; $y += $step) {
                if ($isEmptyPixel(imagecolorat($source, $x, $y))) {
                    $empty++;
                }
                $checked++;
            }

            return $checked > 0 && ($empty / $checked) >= 0.92;
        };

        $top = 0;
        while ($top < $height - 1 && $rowIsEmpty($top)) {
            $top++;
        }

        $bottom = $height - 1;
        while ($bottom > $top && $rowIsEmpty($bottom)) {
            $bottom--;
        }

        $left = 0;
        while ($left < $width - 1 && $colIsEmpty($left)) {
            $left++;
        }

        $right = $width - 1;
        while ($right > $left && $colIsEmpty($right)) {
            $right--;
        }

        $cropWidth = $right - $left + 1;
        $cropHeight = $bottom - $top + 1;

        if (
            $cropWidth < 40 ||
            $cropHeight < 40 ||
            ($cropWidth >= $width * 0.98 && $cropHeight >= $height * 0.98)
        ) {
            imagedestroy($source);

            return $path;
        }

        $cropped = imagecrop($source, [
            'x' => $left,
            'y' => $top,
            'width' => $cropWidth,
            'height' => $cropHeight,
        ]);

        if (! $cropped) {
            imagedestroy($source);

            return $path;
        }

        $tempPath = storage_path('app/search_hash_prepared_'.uniqid('', true).'.jpg');

        imagejpeg($cropped, $tempPath, 92);

        imagedestroy($source);
        imagedestroy($cropped);

        return $tempPath;
    }
}

if (! function_exists('create_search_image_variants')) {
    function create_search_image_variants(string $path): array
    {
        if (! file_exists($path)) {
            return [$path];
        }

        $info = @getimagesize($path);

        if (! $info || empty($info['mime'])) {
            return [$path];
        }

        $source = match ($info['mime']) {
            'image/jpeg' => @imagecreatefromjpeg($path),
            'image/png' => @imagecreatefrompng($path),
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : null,
            default => null,
        };

        if (! $source) {
            return [$path];
        }

        $width = imagesx($source);
        $height = imagesy($source);

        if ($width < 80 || $height < 80) {
            imagedestroy($source);

            return [$path];
        }

        $variants = [$path];

        $addCrop = function (int $x, int $y, int $w, int $h) use ($source, $width, $height, &$variants) {
            $x = max(0, min($x, $width - 1));
            $y = max(0, min($y, $height - 1));
            $w = max(40, min($w, $width - $x));
            $h = max(40, min($h, $height - $y));

            if ($w < 40 || $h < 40) {
                return;
            }

            $cropped = imagecrop($source, [
                'x' => $x,
                'y' => $y,
                'width' => $w,
                'height' => $h,
            ]);

            if (! $cropped) {
                return;
            }

            $tempPath = storage_path('app/search_variant_'.uniqid('', true).'.jpg');

            imagejpeg($cropped, $tempPath, 90);
            imagedestroy($cropped);

            $variants[] = $tempPath;
        };

        foreach ([0.9, 0.8, 0.7, 0.6] as $scale) {
            $cropW = (int) ($width * $scale);
            $cropH = (int) ($height * $scale);
            $x = (int) (($width - $cropW) / 2);
            $y = (int) (($height - $cropH) / 2);

            $addCrop($x, $y, $cropW, $cropH);
        }

        foreach ([0.65, 0.75] as $scale) {
            $cropW = (int) ($width * $scale);
            $cropH = (int) ($height * $scale);

            $positionsX = [
                0,
                (int) (($width - $cropW) / 2),
                $width - $cropW,
            ];

            $positionsY = [
                0,
                (int) (($height - $cropH) / 2),
                $height - $cropH,
            ];

            foreach ($positionsY as $y) {
                foreach ($positionsX as $x) {
                    $addCrop($x, $y, $cropW, $cropH);
                }
            }
        }

        imagedestroy($source);

        return array_values(array_unique($variants));
    }
}

if (! function_exists('filterByImage')) {
    function filterByImage($baseQuery, $imageFile, int $limit = 50, float $maxDistance = 0.35)
    {
        $path = is_object($imageFile) ? $imageFile->getRealPath() : $imageFile;

        if (! $path || ! file_exists($path)) {
            return $baseQuery->whereRaw('1 = 0');
        }

        // CLIP service-ə şəkli göndər, embedding al
        try {
            $response = Http::timeout(15)
                ->attach('file', file_get_contents($path), 'search.jpg')
                ->post('http://127.0.0.1:8765/embed/file');

            if (! $response->successful() || ! empty($response->json('error'))) {
                logger()->warning('CLIP embed/file failed', [
                    'error' => $response->json('error') ?? $response->status(),
                ]);

                return $baseQuery->whereRaw('1 = 0');
            }

            $embedding = $response->json('embedding');

            if (empty($embedding) || count($embedding) !== 512) {
                return $baseQuery->whereRaw('1 = 0');
            }
        } catch (Throwable $e) {
            logger()->error('CLIP service request failed', ['error' => $e->getMessage()]);

            return $baseQuery->whereRaw('1 = 0');
        }

        $vectorString = '['.implode(',', $embedding).']';

        $tableName = $baseQuery->getModel()->getTable();

        // Cosine distance ilə ən yaxın şəkilləri tap
        $results = DB::select('
            SELECT DISTINCT ON (product_id) product_id, (embedding <=> ?::vector) AS distance
            FROM product_image
            WHERE embedding IS NOT NULL
              AND (embedding <=> ?::vector) <= ?
            ORDER BY product_id, distance ASC
        ', [$vectorString, $vectorString, $maxDistance]);

        if (empty($results)) {
            return $baseQuery->whereRaw('1 = 0');
        }

        // Distance-ə görə sırala
        usort($results, fn ($a, $b) => $a->distance <=> $b->distance);

        $ids = array_slice(
            array_map(fn ($r) => $r->product_id, $results),
            0,
            $limit
        );

        // Sıranı qoru — ən yaxından uzağa
        $idsImploded = implode(',', $ids);

        return $baseQuery
            ->whereIn("{$tableName}.id", $ids)
            ->orderByRaw("ARRAY_POSITION(ARRAY[{$idsImploded}]::bigint[], {$tableName}.id::bigint)");
    }
}
