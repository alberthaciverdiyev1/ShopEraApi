<?php

namespace App\Jobs;

use App\Jobs\Concerns\TenantAware;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\Product\Entities\AiPhoto;
use Modules\Product\Services\AiService;

class ProcessPhotoAi implements ShouldQueue
{
    use Dispatchable, Queueable, SerializesModels, TenantAware;

    public int $timeout = 600;

    public int $tries = 2;

    /**
     * Only the id is serialized: a model would be restored from the central
     * database before the tenant connection is in place.
     */
    public function __construct(public int $photoId)
    {
        $this->captureTenant();
    }

    public function handle(AiService $aiService): void
    {
        $photo = AiPhoto::query()->find($this->photoId);

        if (! $photo) {
            return;
        }

        $photo->update(['status' => 'processing']);

        $prompt = "Identify the product in this image and describe its specific features.
            DO NOT use placeholder text.
            DO NOT use the word 'Məhsulun adı'.
            Describe the ACTUAL product you see.

            Output strictly this JSON structure:
            {
              \"title\": {
                \"az\": \"(Məhsulun spesifik adı bura yazılmalıdır)\",
                \"en\": \"(Actual product name in English)\",
                \"ru\": \"(Название товара)\",
                \"tr\": \"(Ürün adı)\"
              },
              \"description\": {
                \"az\": \"(Məhsulun materialı, rəngi və dizaynı haqqında ətraflı məlumat)\",
                \"en\": \"(Detailed analysis of material, color, and design)\",
                \"ru\": \"(Подробное описание)\",
                \"tr\": \"(Detaylı açıklama)\"
              }
            }";

        try {
            $result = $aiService->analyze($photo->image_path, $prompt);

            $photo->update([
                'title' => $result['title'],
                'description' => $result['description'],
                'status' => 'completed',
            ]);

            if ($photo->image_path) {
                $paths = is_array($photo->image_path) ? $photo->image_path : [$photo->image_path];
                foreach ($paths as $path) {
                    Storage::disk('public')->delete($path);
                }
            }
        } catch (\Exception $e) {
            $photo->update(['status' => 'failed']);
            Log::error('AI Job Error: '.$e->getMessage());
        }
    }
}
