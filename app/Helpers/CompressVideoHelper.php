<?php

use App\Support\TenantContext;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Illuminate\Support\Str;

if (!function_exists('compressAndUploadVideo')) {
    /**
     * Compress and store uploaded image in storage/app/public
     *
     * @param UploadedFile $file
     * @param string|null $subDir
     * @param string|null $fileNamePrefix
     * @return string Public URL
     */
//    function compressAndUploadVideo(UploadedFile $file, string $subDir = null, string $fileNamePrefix = null): string {
//
//        $originalExtension = strtolower($file->getClientOriginalExtension());
//        $baseName = ($fileNamePrefix ? $fileNamePrefix.'-' : '') . Str::uuid();
//        $filename = $baseName.'.'.$originalExtension;
//
//        $path = $file->storeAs($subDir, $filename, 'public');
//
//        return Storage::disk('public')->url($path);
//    }

    function compressAndUploadVideo(
        UploadedFile $file,
        string $subDir = null,
        string $fileNamePrefix = null
    ): string {
        $subDir = $subDir ? trim($subDir, '/') : '';
        $subDir = TenantContext::storagePath($subDir);

        $extension = strtolower($file->getClientOriginalExtension());
        $fileName  = ($fileNamePrefix ? $fileNamePrefix . '-' : '') . Str::uuid() . '.' . $extension;

        $path = $file->storeAs($subDir, $fileName, 'public');
        $localPath = storage_path('app/public/' . $path);

        $cdnPath = ($subDir ? $subDir . '/' : '') . $fileName;

        Storage::disk('bunnycdn')->put(
            $cdnPath,
            file_get_contents($localPath)
        );

        Storage::disk('public')->delete($path);

        $cdnBaseUrl = config('filesystems.disks.bunnycdn.pull_zone');

        return rtrim($cdnBaseUrl, '/') . '/' . $cdnPath;
    }

}
