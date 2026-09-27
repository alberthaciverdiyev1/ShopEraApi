<?php

namespace Modules\Listing\Http\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * A photo or a video on an ad. A video is squeezed in the background, so it
 * carries a status until the smaller copy is in place.
 */
class ListingMedia extends Model
{
    public const TYPE_IMAGE = 'image';
    public const TYPE_VIDEO = 'video';

    public const STATUS_READY = 'ready';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_FAILED = 'failed';

    protected $table = 'listing_media';

    protected $guarded = [];

    protected $casts = [
        'size_bytes' => 'integer',
        'duration_seconds' => 'integer',
        'sort_order' => 'integer',
    ];

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class, 'listing_id');
    }

    public function url(): ?string
    {
        return $this->publicUrl($this->path);
    }

    public function thumbnailUrl(): ?string
    {
        return $this->publicUrl($this->thumbnail_path);
    }

    /**
     * The path the file has on the CDN, or null when it is not there. The
     * house upload helpers hand back a finished URL, so that is what is
     * stored; deleting the file needs the part after the pull zone.
     */
    public function cdnPath(?string $path = null): ?string
    {
        $path ??= $this->path;
        $base = rtrim((string) config('filesystems.disks.bunnycdn.pull_zone'), '/');

        if (! $path || ! $base || ! Str::startsWith($path, $base . '/')) {
            return null;
        }

        return ltrim(Str::after($path, $base . '/'), '/');
    }

    private function publicUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        // Photos go to the CDN and are stored as a finished address; anything
        // else is a path on our own public disk.
        return Str::startsWith($path, ['http://', 'https://'])
            ? $path
            : Storage::disk('public')->url($path);
    }
}
