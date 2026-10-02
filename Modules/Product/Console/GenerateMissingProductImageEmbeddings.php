<?php

namespace Modules\Product\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Modules\Product\Entities\ProductImage;

class GenerateMissingProductImageEmbeddings extends Command
{
    protected $signature = 'product-images:generate-missing-embeddings
                            {--limit= : Maksimum neçə şəkil işlənəcək}
                            {--chunk=20 : Hər dəfə neçə şəkil işlənəcək}
                            {--dry-run : Sadəcə yoxla, DB update etmə}';

    protected $description = 'Generate missing CLIP embedding values for product images';

    private string $clipServiceUrl = 'http://127.0.0.1:8765';

    public function handle(): int
    {
        $limit = $this->option('limit') ? (int) $this->option('limit') : null;
        $chunk = (int) $this->option('chunk');
        $dryRun = (bool) $this->option('dry-run');

        $query = ProductImage::query()
            ->whereNull('embedding')
            ->orderBy('id');

        $total = (clone $query)->count();

        if ($limit) {
            $total = min($total, $limit);
        }

        $this->info("Embedding olmayan şəkil sayı: {$total}");

        if ($total === 0) {
            return self::SUCCESS;
        }

        // CLIP service-i yoxla
        try {
            $health = Http::timeout(5)->get("{$this->clipServiceUrl}/health");
            if (! $health->successful()) {
                $this->error('CLIP service cavab vermir. Supervisor-u yoxla: supervisorctl status clip-service');

                return self::FAILURE;
            }
            $this->info('CLIP service: OK');
        } catch (\Throwable $e) {
            $this->error('CLIP service-ə qoşulmaq olmadı: '.$e->getMessage());

            return self::FAILURE;
        }

        $processed = 0;
        $success = 0;
        $failed = 0;
        $skipped = 0;

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $query->chunkById($chunk, function ($images) use (
            &$processed, &$success, &$failed, &$skipped,
            $limit, $dryRun, $bar
        ) {
            foreach ($images as $image) {
                if ($limit && $processed >= $limit) {
                    return false;
                }

                $processed++;

                try {
                    $imageUrl = $this->resolveImageUrl($image);

                    if (! $imageUrl) {
                        $skipped++;
                        $bar->advance();

                        continue;
                    }

                    $response = Http::timeout(30)->post("{$this->clipServiceUrl}/embed/url", [
                        'url' => $imageUrl,
                    ]);

                    if (! $response->successful() || ! empty($response->json('error'))) {
                        $failed++;
                        logger()->warning('Embedding failed', [
                            'image_id' => $image->id,
                            'url' => $imageUrl,
                            'error' => $response->json('error') ?? $response->status(),
                        ]);
                        $bar->advance();

                        continue;
                    }

                    $embedding = $response->json('embedding');

                    if (empty($embedding) || count($embedding) !== 512) {
                        $failed++;
                        $bar->advance();

                        continue;
                    }

                    if (! $dryRun) {
                        // Vector formatı: '[0.1, 0.2, ...]'
                        $vectorString = '['.implode(',', $embedding).']';

                        $image->updateQuietly(['embedding' => $vectorString]);
                    }

                    $success++;
                } catch (\Throwable $e) {
                    $failed++;
                    logger()->error('Product image embedding generate failed', [
                        'image_id' => $image->id,
                        'error' => $e->getMessage(),
                    ]);
                }

                $bar->advance();
            }

            return true;
        });

        $bar->finish();
        $this->newLine(2);
        $this->info("Processed : {$processed}");
        $this->info("Success   : {$success}");
        $this->info("Skipped   : {$skipped}");
        $this->info("Failed    : {$failed}");

        if ($dryRun) {
            $this->warn('Dry-run idi. Bazada dəyişiklik edilmədi.');
        }

        return self::SUCCESS;
    }

    private function resolveImageUrl(ProductImage $image): ?string
    {
        $imagePath = $image->image_path;

        if (! $imagePath) {
            return null;
        }

        // Artıq tam URL-dirsə birbaşa qaytar
        if (Str::startsWith($imagePath, ['http://', 'https://'])) {
            return $imagePath;
        }

        // Relative path — Bunny CDN URL-inə çevir
        $cdnBase = rtrim(config('filesystems.disks.bunnycdn.pull_zone'), '/');

        if ($cdnBase) {
            return $cdnBase.'/'.ltrim($imagePath, '/');
        }

        return null;
    }
}
