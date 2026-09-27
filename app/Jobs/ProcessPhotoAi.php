<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Modules\Product\Http\Entities\AiPhoto;
use Modules\Product\Services\AiService;

class ProcessPhotoAi implements ShouldQueue
{
    use Dispatchable, Queueable, SerializesModels;

    public int $timeout = 600;

    public int $tries = 2;

    public function __construct(public AiPhoto $photo)
    {
    }

    public function handle(AiService $aiService)
    {
        $this->photo->update(['status' => 'processing']);

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
            $result = $aiService->analyze($this->photo->image_path, $prompt);

            $this->photo->update([
                'title' => $result['title'],
                'description' => $result['description'],
                'status' => 'completed'
            ]);

            if ($this->photo->image_path) {
                $paths = is_array($this->photo->image_path) ? $this->photo->image_path : [$this->photo->image_path];
                foreach ($paths as $path) {
                    Storage::disk('public')->delete($path);
                }
            }

        } catch (\Exception $e) {
            $this->photo->update(['status' => 'failed']);
            \Log::error("AI Job Error: " . $e->getMessage());
        }
    }
}
