<?php

namespace Modules\Listing\Services;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\Listing\Http\Entities\Listing;
use Modules\Listing\Http\Entities\ListingMedia;
use Symfony\Component\HttpFoundation\Response as StatusCode;

/**
 * The photos and the one video on an ad.
 *
 * Photos go through the same helper the shop's product photos use: resized to
 * 900 px, re-encoded, and pushed to the CDN, so an ad costs the server disk
 * nothing. There is no ffmpeg on the host, so a video is stored as it arrives
 * and the size limit is what keeps it sane; the app is what shrinks it.
 */
class ListingMediaService
{
    public function upload(int $listingId, Request $request)
    {
        $listing = Listing::find($listingId);

        if (! $listing || $listing->user_id !== $request->user()->id) {
            return responseHelper('Listing not found.', StatusCode::HTTP_NOT_FOUND);
        }

        $maxImages = (int) config('listing.media.max_images', 15);
        $perRequest = (int) config('listing.media.max_per_request', 5);
        $maxImageKb = (int) round(((int) config('listing.media.max_image_bytes', 8 * 1024 * 1024)) / 1024);
        $maxVideoKb = (int) round(((int) config('listing.media.max_video_bytes', 10 * 1024 * 1024)) / 1024);

        $request->validate([
            'images' => ['nullable', 'array', 'max:' . $perRequest],
            'images.*' => ['file', 'image', 'mimes:jpeg,jpg,png,webp,heic,heif', 'max:' . $maxImageKb],
            'video' => ['nullable', 'file', 'mimetypes:video/mp4,video/quicktime', 'max:' . $maxVideoKb],
            'video_thumbnail' => ['nullable', 'file', 'image', 'max:' . $maxImageKb],
        ]);

        $images = array_values(array_filter((array) $request->file('images', [])));
        $video = $request->file('video');

        if ($images === [] && ! $video) {
            return responseHelper('No file was sent.', StatusCode::HTTP_UNPROCESSABLE_ENTITY);
        }

        $already = $listing->media()->where('type', ListingMedia::TYPE_IMAGE)->count();

        if ($already + count($images) > $maxImages) {
            return responseHelper(
                __('An ad may carry at most :count photos.', ['count' => $maxImages]),
                StatusCode::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $sort = (int) $listing->media()->max('sort_order');

        foreach ($images as $image) {
            $url = $this->storeImage($image, $listing->id);

            if (! $url) {
                continue;
            }

            ListingMedia::create([
                'listing_id' => $listing->id,
                'type' => ListingMedia::TYPE_IMAGE,
                'path' => $url,
                'size_bytes' => $image->getSize(),
                'status' => ListingMedia::STATUS_READY,
                'sort_order' => ++$sort,
            ]);
        }

        if ($video) {
            // One video per ad: a second upload replaces the first.
            $this->forget($listing->media()->where('type', ListingMedia::TYPE_VIDEO)->get());

            $url = compressAndUploadVideo($video, 'listings/videos', 'listing-' . $listing->id);
            $thumbnail = $request->file('video_thumbnail');

            ListingMedia::create([
                'listing_id' => $listing->id,
                'type' => ListingMedia::TYPE_VIDEO,
                'path' => $url,
                // The host has no ffmpeg, so a still cannot be cut from the
                // video here; the app sends one with the upload.
                'thumbnail_path' => $thumbnail ? $this->storeImage($thumbnail, $listing->id) : null,
                'size_bytes' => $video->getSize(),
                'status' => ListingMedia::STATUS_READY,
                'sort_order' => ++$sort,
            ]);
        }

        return responseHelper('Operation successful.', StatusCode::HTTP_OK, $this->list($listing));
    }

    public function destroy(int $mediaId, Request $request)
    {
        $media = ListingMedia::with('listing')->find($mediaId);

        if (! $media || ! $media->listing || $media->listing->user_id !== $request->user()->id) {
            return responseHelper('File not found.', StatusCode::HTTP_NOT_FOUND);
        }

        $listing = $media->listing;
        $this->forget(collect([$media]));

        return responseHelper('Operation successful.', StatusCode::HTTP_OK, $this->list($listing));
    }

    /** The first photo is the cover, so the order is the seller's choice. */
    public function sort(int $listingId, Request $request)
    {
        $listing = Listing::find($listingId);

        if (! $listing || $listing->user_id !== $request->user()->id) {
            return responseHelper('Listing not found.', StatusCode::HTTP_NOT_FOUND);
        }

        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $owned = $listing->media()->pluck('id')->all();
        $order = 0;

        foreach ($validated['ids'] as $id) {
            if (! in_array((int) $id, $owned, true)) {
                continue;
            }

            ListingMedia::whereKey((int) $id)->update(['sort_order' => ++$order]);
        }

        return responseHelper('Operation successful.', StatusCode::HTTP_OK, $this->list($listing->fresh()));
    }

    private function list(Listing $listing): array
    {
        return $listing->media()->get()->map(fn (ListingMedia $media) => [
            'id' => $media->id,
            'type' => $media->type,
            'url' => $media->url(),
            'thumbnail_url' => $media->thumbnailUrl(),
            'status' => $media->status,
            'sort_order' => $media->sort_order,
        ])->all();
    }

    /** Takes the files off the CDN as well as the rows out of the table. */
    private function forget(iterable $items): void
    {
        foreach ($items as $media) {
            foreach ([$media->cdnPath(), $media->cdnPath($media->thumbnail_path)] as $path) {
                $this->deleteFile($path);
            }

            $media->delete();
        }
    }

    private function deleteFile(?string $relative): void
    {
        if (! $relative) {
            return;
        }

        try {
            if (Storage::disk('bunnycdn')->exists($relative)) {
                Storage::disk('bunnycdn')->delete($relative);
            }
        } catch (\Throwable $e) {
            // A file left on the CDN is litter, not a failure the seller
            // should see.
            Log::error('Listing media delete failed: ' . $e->getMessage());
        }
    }

    private function storeImage(UploadedFile $file, int $listingId): ?string
    {
        try {
            return compressAndUploadImage($file, 'listings', 'listing-' . $listingId);
        } catch (\Throwable $e) {
            Log::error('Listing image upload failed: ' . $e->getMessage());

            return null;
        }
    }
}
