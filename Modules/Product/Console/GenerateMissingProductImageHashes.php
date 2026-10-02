<?php

namespace Modules\Product\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Product\Entities\ProductImage;

class GenerateMissingProductImageHashes extends Command
{
    protected $signature = 'product-images:generate-missing-hashes 
                            {--limit= : Maksimum neçə şəkil işlənəcək}
                            {--chunk=50 : Hər dəfə neçə şəkil işlənəcək}
                            {--dry-run : Sadəcə yoxla, DB update etmə}';

    protected $description = 'Generate missing ahash and dhash values for product images';

    public function handle(): int
    {
        $limit = $this->option('limit') ? (int) $this->option('limit') : null;
        $chunk = (int) $this->option('chunk');
        $dryRun = (bool) $this->option('dry-run');

        $query = ProductImage::query()
            ->where(function ($q) {
                $q->whereNull('ahash')
                    ->orWhereNull('dhash');
            })
            ->orderBy('id');

        $total = (clone $query)->count();

        if ($limit) {
            $total = min($total, $limit);
        }

        $this->info("Hash olmayan şəkil sayı: {$total}");

        if ($total === 0) {
            return self::SUCCESS;
        }

        $processed = 0;
        $success = 0;
        $failed = 0;
        $skipped = 0;

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $query->chunkById($chunk, function ($images) use (
            &$processed,
            &$success,
            &$failed,
            &$skipped,
            $limit,
            $dryRun,
            $bar
        ) {
            foreach ($images as $image) {
                if ($limit && $processed >= $limit) {
                    return false;
                }

                $processed++;

                try {
                    $tempPath = $this->getImageTempPath($image);

                    if (! $tempPath || ! file_exists($tempPath)) {
                        $skipped++;
                        $bar->advance();

                        continue;
                    }

                    $hashes = generate_image_hashes($tempPath);

                    @unlink($tempPath);

                    if (empty($hashes['ahash']) || empty($hashes['dhash'])) {
                        $failed++;
                        $bar->advance();

                        continue;
                    }

                    if (! $dryRun) {
                        $image->update([
                            'ahash' => $hashes['ahash'],
                            'dhash' => $hashes['dhash'],
                        ]);
                    }

                    $success++;
                } catch (\Throwable $e) {
                    $failed++;

                    logger()->error('Product image hash generate failed', [
                        'image_id' => $image->id,
                        'image_path' => $image->image_path,
                        'error' => $e->getMessage(),
                    ]);
                }

                $bar->advance();
            }

            return true;
        });

        $bar->finish();

        $this->newLine(2);
        $this->info("Processed: {$processed}");
        $this->info("Success: {$success}");
        $this->info("Skipped: {$skipped}");
        $this->info("Failed: {$failed}");

        if ($dryRun) {
            $this->warn('Dry-run idi. Bazada dəyişiklik edilmədi.');
        }

        return self::SUCCESS;
    }

    private function getImageTempPath(ProductImage $image): ?string
    {
        $imagePath = $image->image_path;

        if (! $imagePath) {
            return null;
        }

        $tempDir = storage_path('app/temp_product_hashes');

        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $tempPath = $tempDir.'/product_image_'.$image->id.'_'.md5($imagePath).'.jpg';

        $cdnBaseUrl = rtrim(config('filesystems.disks.bunnycdn.pull_zone'), '/');

        if (Str::startsWith($imagePath, ['http://', 'https://'])) {
            $relativePath = ltrim(str_replace($cdnBaseUrl, '', $imagePath), '/');

            if ($relativePath && Storage::disk('bunnycdn')->exists($relativePath)) {
                file_put_contents($tempPath, Storage::disk('bunnycdn')->get($relativePath));

                return $tempPath;
            }

            $response = Http::timeout(30)->get($imagePath);

            if ($response->successful()) {
                file_put_contents($tempPath, $response->body());

                return $tempPath;
            }

            return null;
        }

        $relativePath = ltrim($imagePath, '/');

        if (Storage::disk('bunnycdn')->exists($relativePath)) {
            file_put_contents($tempPath, Storage::disk('bunnycdn')->get($relativePath));

            return $tempPath;
        }

        return null;
    }
}
