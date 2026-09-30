<?php

namespace App\Support\Media;

use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\PathGenerator\PathGenerator;

class PresensiPathGenerator implements PathGenerator
{
    /**
     * Get the path for the given media, relative to the root storage path.
     */
    public function getPath(Media $media): string
    {
        return $this->getBasePath($media) . '/';
    }

    /**
     * Get the path for conversions of the given media, relative to the root storage path.
     */
    public function getPathForConversions(Media $media): string
    {
        return $this->getBasePath($media) . '/conversions/';
    }

    /**
     * Get the path for responsive images of the given media, relative to the root storage path.
     */
    public function getPathForResponsiveImages(Media $media): string
    {
        return $this->getBasePath($media) . '/responsive-images/';
    }

    /**
     * Get base path with backward compatibility:
     * 1. If file has been moved to presensi/{id}/, use presensi/{id}.
     * 2. If file still exists at old root {id}/, fallback to {id} so it doesn't break before migration.
     * 3. For new files, always use presensi/{id}.
     */
    protected function getBasePath(Media $media): string
    {
        $disk = $media->disk ?: config('media-library.disk_name', 'public');
        $oldPath = $media->getKey() . '/' . $media->file_name;
        $newPath = 'presensi/' . $media->getKey() . '/' . $media->file_name;

        // Fallback: Jika file fisik masih di lokasi lama dan belum dipindah ke folder presensi/
        if (!Storage::disk($disk)->exists($newPath) && Storage::disk($disk)->exists($oldPath)) {
            return (string) $media->getKey();
        }

        return 'presensi/' . $media->getKey();
    }
}
