<?php

namespace Modules\Product\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiService
{
    public function analyze(array $imagePaths, string $prompt): ?array
    {
        ini_set('max_execution_time', 0);
        ini_set('default_socket_timeout', 600);
        set_time_limit(0);

        $driver = config('services.ai.driver', 'ollama');

        if ($driver === 'gemini') {
            return $this->analyzeWithGemini($imagePaths, $prompt);
        }

        return $this->analyzeWithOllama($imagePaths, $prompt);
    }

    private function analyzeWithGemini(array $imagePaths, string $prompt): ?array
    {
        $apiKey = config('services.ai.api_key');
        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . $apiKey;

        $inlineData = [];
        foreach ($imagePaths as $path) {
            $fullPath = storage_path('app/public/' . $path);
            if (file_exists($fullPath)) {
                $inlineData[] = [
                    'mime_type' => 'image/jpeg',
                    'data' => base64_encode(file_get_contents($fullPath))
                ];
            }
        }

        $payload = [
            'contents' => [
                [
                    'parts' => array_merge(
                        [['text' => $prompt]],
                        $inlineData
                    )
                ]
            ],
            'generationConfig' => [
                'response_mime_type' => 'application/json',
            ]
        ];

        try {
            $response = Http::timeout(60)->post($url, $payload);

            if ($response->successful()) {
                $result = $response->json();
                $textResponse = $result['candidates'][0]['content']['parts'][0]['text'] ?? null;
                return json_decode($textResponse, true);
            }

            Log::error("Gemini Hatası: " . $response->body());
        } catch (\Exception $e) {
            Log::error("Gemini Bağlantı Hatası: " . $e->getMessage());
        }

        return null;
    }

    private function analyzeWithOllama(array $imagePaths, string $prompt): ?array
    {
        $baseUrl = config('services.ai.ollama_url') ?? 'http://172.17.0.1:11434';
        $model = config('services.ai.model') ?? 'llava-hybrid';

        $base64Images = [];
        foreach ($imagePaths as $path) {
            $fullPath = storage_path('app/public/' . $path);
            if (file_exists($fullPath)) {
                $base64Images[] = trim(base64_encode(file_get_contents($fullPath)));
            }
        }

        try {
            $response = Http::withOptions([
                'verify' => false,
                'timeout' => 300,
                'curl' => [
                    CURLOPT_FORBID_REUSE => true,
                    CURLOPT_FRESH_CONNECT => true,
                ],
            ])->post('http://172.17.0.1:11434/api/generate', [
                'model'  => 'llava-hybrid:latest',
                'prompt' => $prompt,
                'images' => $base64Images,
                'stream' => false,
                'format' => 'json'
            ]);

            if ($response->successful()) {
                $rawResponse = $response->json('response');

                if (is_string($rawResponse)) {
                    return json_decode($rawResponse, true);
                }
                return $rawResponse;
            }

            Log::error("Ollama HTTP Hatası: " . $response->status() . " - " . $response->body());
        } catch (\Exception $e) {
            Log::error("Ollama Bağlantı Hatası: " . $e->getMessage());
        }

        return null;
    }
}
