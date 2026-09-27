<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Illuminate\Support\Str;

if (!function_exists('compressAndUploadImage')) {

    /**
     * Compress, resize (width=900) and upload image to BunnyCDN
     *
     * @param UploadedFile $file
     * @param string|null $subDir
     * @param string|null $fileNamePrefix
     * @return string
     */
    function compressAndUploadImage(
        UploadedFile $file,
        string $subDir = null,
        string $fileNamePrefix = null
    ): string {

        $subDir = $subDir ? trim($subDir, '/') : '';
        $storagePath = $subDir ? "public/{$subDir}" : 'public';

        $targetDir = storage_path("app/{$storagePath}");
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0775, true);
        }

        $extension = strtolower($file->getClientOriginalExtension());
        $fileName  = ($fileNamePrefix ? $fileNamePrefix . '-' : '') . Str::uuid();
        $quality   = 60;
        $imageManager = new ImageManager(['driver' => 'gd']);

        try {

            switch ($extension) {

                case 'jpg':
                case 'jpeg':
                    $fileName .= '.' . $extension;

                    $imageManager->make($file->getRealPath())
                        ->orientate()
                        ->resize(900, null, function ($constraint) {
                            $constraint->aspectRatio();
                            $constraint->upsize();
                        })
                        ->save(
                            storage_path("app/{$storagePath}/{$fileName}"),
                            $quality
                        );
                    break;

                case 'png':
                    $fileName .= '.webp';

                    $imageManager->make($file->getRealPath())
                        ->orientate()
                        ->resize(900, null, function ($constraint) {
                            $constraint->aspectRatio();
                            $constraint->upsize();
                        })
                        ->encode('webp', $quality)
                        ->save(
                            storage_path("app/{$storagePath}/{$fileName}")
                        );
                    break;

                case 'webp':
                    $fileName .= '.webp';

                    $imageManager->make($file->getRealPath())
                        ->orientate()
                        ->resize(900, null, function ($constraint) {
                            $constraint->aspectRatio();
                            $constraint->upsize();
                        })
                        ->save(
                            storage_path("app/{$storagePath}/{$fileName}"),
                            $quality
                        );
                    break;

                case 'svg':
                    $fileName .= '.svg';
                    $file->move($targetDir, $fileName);
                    break;

                default:
                    $fileName .= '.' . $extension;
                    $file->move($targetDir, $fileName);
            }

        } catch (\Throwable $e) {

            \Log::error('Image processing failed: ' . $e->getMessage());

            if (!str_ends_with(strtolower($fileName), '.' . $extension)) {
                $fileName .= '.' . $extension;
            }
            $file->move($targetDir, $fileName);
        }

        $localPath = storage_path("app/{$storagePath}/{$fileName}");
        $cdnPath   = ($subDir ? $subDir . '/' : '') . $fileName;

        Storage::disk('bunnycdn')->put(
            $cdnPath,
            file_get_contents($localPath)
        );

        @unlink($localPath);

        $cdnBaseUrl = config('filesystems.disks.bunnycdn.pull_zone');

        return rtrim($cdnBaseUrl, '/') . '/' . $cdnPath;
    }


//    function compressAndUploadImage(
//        UploadedFile $file,
//        string $subDir = null,
//        string $fileNamePrefix = null
//    ): string {
//        $subDir = $subDir ? trim($subDir, '/') : '';
//        $storagePath = $subDir ? "public/{$subDir}" : 'public';
//
//        if (!Storage::exists($storagePath)) {
//            Storage::makeDirectory($storagePath, 0755, true);
//        }
//
//        $extension = strtolower($file->getClientOriginalExtension());
//        // Uzantıyı en başta eklemeyin, switch içinde netleştirin
//        $baseName = ($fileNamePrefix ? $fileNamePrefix . '-' : '') . Str::uuid();
//        $quality = 60;
//        $imageManager = new ImageManager(['driver' => 'gd']);
//
//        try {
//            switch ($extension) {
//                case 'jpg':
//                case 'jpeg':
//                    $fileName = $baseName . '.' . $extension;
//                    $imageManager->make($file->getRealPath())
//                        ->orientate()
//                        ->resize(900, null, function ($constraint) {
//                            $constraint->aspectRatio();
//                            $constraint->upsize();
//                        })
//                        ->save(storage_path("app/{$storagePath}/{$fileName}"), $quality);
//                    break;
//
//                case 'png':
//                case 'webp':
//                    // PNG ve WebP'yi her zaman webp yapalım
//                    $fileName = $baseName . '.webp';
//                    $imageManager->make($file->getRealPath())
//                        ->orientate()
//                        ->resize(900, null, function ($constraint) {
//                            $constraint->aspectRatio();
//                            $constraint->upsize();
//                        })
//                        ->encode('webp', $quality)
//                        ->save(storage_path("app/{$storagePath}/{$fileName}"));
//                    break;
//
//                case 'svg':
//                    $fileName = $baseName . '.svg';
//                    $file->storeAs($storagePath, $fileName);
//                    break;
//
//                default:
//                    $fileName = $baseName . '.' . $extension;
//                    $file->storeAs($storagePath, $fileName);
//            }
//        } catch (\Throwable $e) {
//            \Log::error('Image processing failed: ' . $e->getMessage());
//            // Hata durumunda orijinal dosyayı olduğu gibi kaydet (çift uzantı yapmadan)
//            $fileName = $baseName . '.' . $extension;
//            $file->storeAs($storagePath, $fileName);
//        }
//
//        // DISK KONTROLÜ (Local/Docker fix)
//        // storage_path bazen Docker içinde yanıltıcı olabilir.
//        // Storage::get kullanmak her zaman daha güvenlidir.
//        $fullStoragePath = "{$storagePath}/{$fileName}";
//
//        // file_get_contents(storage_path(...)) yerine Storage::disk('local')->get(...)
//        $fileContent = Storage::disk('local')->get($fullStoragePath);
//
//        $cdnPath = ($subDir ? $subDir . '/' : '') . $fileName;
//
//        Storage::disk('bunnycdn')->put($cdnPath, $fileContent);
//        Storage::disk('local')->delete($fullStoragePath);
//
//        $cdnBaseUrl = config('filesystems.disks.bunnycdn.pull_zone');
//        return rtrim($cdnBaseUrl, '/') . '/' . $cdnPath;
//    }
}
