<?php

namespace LaravelEcommerce\Store\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

trait HasImages
{
    /**
     * Get all images for the model.
     */
    public function images(): MorphMany
    {
        return $this->morphMany(\LaravelEcommerce\Store\Models\ProductImage::class, 'imageable');
    }

    /**
     * Get the main image for the model.
     */
    public function getMainImageAttribute(): ?string
    {
        $image = $this->images()->orderBy('sort_order')->first();
        return $image ? $image->url : null;
    }

    /**
     * Get the thumbnail image for the model.
     */
    public function getThumbnailAttribute(): ?string
    {
        $image = $this->images()->orderBy('sort_order')->first();
        return $image ? $image->thumbnail_url : null;
    }

    /**
     * Get all image URLs for the model.
     */
    public function getImageUrlsAttribute(): array
    {
        return $this->images()->orderBy('sort_order')->pluck('url')->toArray();
    }

    /**
     * Get all thumbnail URLs for the model.
     */
    public function getThumbnailUrlsAttribute(): array
    {
        return $this->images()->orderBy('sort_order')->pluck('thumbnail_url')->toArray();
    }

    /**
     * Add an image to the model.
     */
    public function addImage(string $imagePath, string $alt = null, int $sortOrder = 0): \LaravelEcommerce\Store\Models\ProductImage
    {
        // Generate thumbnail
        $thumbnailPath = $this->generateThumbnail($imagePath);

        return $this->images()->create([
            'url' => $imagePath,
            'thumbnail_url' => $thumbnailPath,
            'alt' => $alt,
            'sort_order' => $sortOrder,
        ]);
    }

    /**
     * Add multiple images to the model.
     */
    public function addImages(array $imagePaths): void
    {
        foreach ($imagePaths as $index => $imagePath) {
            $this->addImage($imagePath, null, $index);
        }
    }

    /**
     * Remove an image from the model.
     */
    public function removeImage(int $imageId): bool
    {
        $image = $this->images()->find($imageId);

        if (!$image) {
            return false;
        }

        // Delete files from storage
        if ($image->url && Storage::exists($image->url)) {
            Storage::delete($image->url);
        }

        if ($image->thumbnail_url && Storage::exists($image->thumbnail_url)) {
            Storage::delete($image->thumbnail_url);
        }

        return $image->delete();
    }

    /**
     * Remove all images from the model.
     */
    public function removeAllImages(): void
    {
        foreach ($this->images as $image) {
            $this->removeImage($image->id);
        }
    }

    /**
     * Reorder images.
     */
    public function reorderImages(array $imageIds): void
    {
        foreach ($imageIds as $sortOrder => $imageId) {
            $this->images()->where('id', $imageId)->update(['sort_order' => $sortOrder]);
        }
    }

    /**
     * Generate thumbnail for an image.
     */
    protected function generateThumbnail(string $imagePath): string
    {
        $thumbnailWidth = config('store.products.thumb_width', 300);
        $thumbnailHeight = config('store.products.thumb_height', 300);

        try {
            $image = Image::make(Storage::path($imagePath));
            $image->fit($thumbnailWidth, $thumbnailHeight);

            $thumbnailPath = 'thumbnails/' . basename($imagePath);
            $fullThumbnailPath = Storage::path($thumbnailPath);

            // Ensure thumbnail directory exists
            Storage::makeDirectory('thumbnails');

            $image->save($fullThumbnailPath);

            return $thumbnailPath;
        } catch (\Exception $e) {
            // If thumbnail generation fails, return original image path
            return $imagePath;
        }
    }

    /**
     * Get image alt text.
     */
    public function getImageAltAttribute(): string
    {
        $image = $this->images()->orderBy('sort_order')->first();
        return $image ? $image->alt : $this->name ?? '';
    }

    /**
     * Check if model has images.
     */
    public function hasImages(): bool
    {
        return $this->images()->exists();
    }

    /**
     * Get image count.
     */
    public function getImageCountAttribute(): int
    {
        return $this->images()->count();
    }

    /**
     * Get images as a collection with metadata.
     */
    public function getImagesWithMetadataAttribute(): \Illuminate\Support\Collection
    {
        return $this->images()->orderBy('sort_order')->get()->map(function ($image) {
            return [
                'id' => $image->id,
                'url' => $image->url,
                'thumbnail_url' => $image->thumbnail_url,
                'alt' => $image->alt,
                'sort_order' => $image->sort_order,
                'created_at' => $image->created_at,
                'updated_at' => $image->updated_at,
            ];
        });
    }

    /**
     * Sync images (replace all images).
     */
    public function syncImages(array $images): void
    {
        // Remove existing images
        $this->removeAllImages();

        // Add new images
        $this->addImages($images);
    }

    /**
     * Get images for display (with fallback).
     */
    public function getDisplayImagesAttribute(): array
    {
        $images = $this->images()->orderBy('sort_order')->get();

        if ($images->isEmpty()) {
            return [
                [
                    'url' => '/images/no-image.png',
                    'thumbnail_url' => '/images/no-image-thumb.png',
                    'alt' => $this->name ?? 'Sem imagem',
                ]
            ];
        }

        return $images->map(function ($image) {
            return [
                'id' => $image->id,
                'url' => $image->url,
                'thumbnail_url' => $image->thumbnail_url,
                'alt' => $image->alt,
            ];
        })->toArray();
    }

    /**
     * Get the first image or fallback.
     */
    public function getFirstImageOrFallbackAttribute(): string
    {
        return $this->main_image ?? '/images/no-image.png';
    }

    /**
     * Get the first thumbnail or fallback.
     */
    public function getFirstThumbnailOrFallbackAttribute(): string
    {
        return $this->thumbnail ?? '/images/no-image-thumb.png';
    }

    /**
     * Boot the trait.
     */
    protected static function bootHasImages(): void
    {
        static::deleting(function ($model) {
            // Remove all images when model is deleted
            $model->removeAllImages();
        });
    }
}