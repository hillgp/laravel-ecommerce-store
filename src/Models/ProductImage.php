<?php

namespace LaravelEcommerce\Store\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class ProductImage extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'imageable_type',
        'imageable_id',
        'path',
        'filename',
        'original_filename',
        'mime_type',
        'size',
        'alt_text',
        'title',
        'is_primary',
        'sort_order',
        'width',
        'height',
        'thumbnail_path',
        'medium_path',
        'large_path',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'sort_order' => 'integer',
        'size' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
    ];

    protected $attributes = [
        'is_primary' => false,
        'sort_order' => 0,
    ];

    /**
     * Get the owning imageable model.
     */
    public function imageable()
    {
        return $this->morphTo();
    }

    /**
     * Get the image URL.
     */
    public function getUrlAttribute(): string
    {
        if ($this->path) {
            return Storage::url($this->path);
        }

        return asset('images/no-image.png');
    }

    /**
     * Get the thumbnail URL.
     */
    public function getThumbnailUrlAttribute(): string
    {
        if ($this->thumbnail_path) {
            return Storage::url($this->thumbnail_path);
        }

        return $this->url;
    }

    /**
     * Get the medium size URL.
     */
    public function getMediumUrlAttribute(): string
    {
        if ($this->medium_path) {
            return Storage::url($this->medium_path);
        }

        return $this->url;
    }

    /**
     * Get the large size URL.
     */
    public function getLargeUrlAttribute(): string
    {
        if ($this->large_path) {
            return Storage::url($this->large_path);
        }

        return $this->url;
    }

    /**
     * Get the image dimensions.
     */
    public function getDimensionsAttribute(): array
    {
        return [
            'width' => $this->width,
            'height' => $this->height,
            'aspect_ratio' => $this->width && $this->height ? $this->width / $this->height : null,
        ];
    }

    /**
     * Get the formatted file size.
     */
    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->size;
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Get the image aspect ratio class.
     */
    public function getAspectRatioClassAttribute(): string
    {
        if (!$this->width || !$this->height) {
            return 'aspect-square';
        }

        $ratio = $this->width / $this->height;

        if ($ratio >= 1.6) {
            return 'aspect-video';
        } elseif ($ratio >= 1.2) {
            return 'aspect-wide';
        } elseif ($ratio <= 0.6) {
            return 'aspect-tall';
        } elseif ($ratio <= 0.8) {
            return 'aspect-portrait';
        } else {
            return 'aspect-square';
        }
    }

    /**
     * Get the image orientation.
     */
    public function getOrientationAttribute(): string
    {
        if (!$this->width || !$this->height) {
            return 'unknown';
        }

        if ($this->width > $this->height) {
            return 'landscape';
        } elseif ($this->height > $this->width) {
            return 'portrait';
        } else {
            return 'square';
        }
    }

    /**
     * Check if image is landscape.
     */
    public function isLandscape(): bool
    {
        return $this->orientation === 'landscape';
    }

    /**
     * Check if image is portrait.
     */
    public function isPortrait(): bool
    {
        return $this->orientation === 'portrait';
    }

    /**
     * Check if image is square.
     */
    public function isSquare(): bool
    {
        return $this->orientation === 'square';
    }

    /**
     * Get image metadata.
     */
    public function getMetadataAttribute(): array
    {
        return [
            'filename' => $this->filename,
            'original_filename' => $this->original_filename,
            'mime_type' => $this->mime_type,
            'size' => $this->size,
            'formatted_size' => $this->formatted_size,
            'width' => $this->width,
            'height' => $this->height,
            'dimensions' => $this->dimensions,
            'aspect_ratio' => $this->dimensions['aspect_ratio'],
            'orientation' => $this->orientation,
            'is_primary' => $this->is_primary,
            'sort_order' => $this->sort_order,
        ];
    }

    /**
     * Scope a query to only include primary images.
     */
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    /**
     * Scope a query to order by sort order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('created_at');
    }

    /**
     * Scope a query to order by primary first.
     */
    public function scopeByPrimaryFirst($query)
    {
        return $query->orderBy('is_primary', 'desc')->orderBy('sort_order')->orderBy('created_at');
    }

    /**
     * Scope a query to only include images by type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('imageable_type', $type);
    }

    /**
     * Scope a query to only include images by model.
     */
    public function scopeByModel($query, string $modelType, int $modelId)
    {
        return $query->where('imageable_type', $modelType)
                    ->where('imageable_id', $modelId);
    }

    /**
     * Get images for a specific model.
     */
    public static function getForModel(string $modelType, int $modelId): \Illuminate\Support\Collection
    {
        return static::where('imageable_type', $modelType)
            ->where('imageable_id', $modelId)
            ->ordered()
            ->get();
    }

    /**
     * Get primary image for a specific model.
     */
    public static function getPrimaryForModel(string $modelType, int $modelId): ?self
    {
        return static::where('imageable_type', $modelType)
            ->where('imageable_id', $modelId)
            ->primary()
            ->first();
    }

    /**
     * Set primary image for a model.
     */
    public static function setPrimary(string $modelType, int $modelId, int $imageId): void
    {
        // Remove primary flag from all images of this model
        static::where('imageable_type', $modelType)
            ->where('imageable_id', $modelId)
            ->update(['is_primary' => false]);

        // Set primary flag for the specified image
        $image = static::find($imageId);
        if ($image) {
            $image->update(['is_primary' => true]);
        }
    }

    /**
     * Delete image and its files.
     */
    public function deleteWithFiles(): bool
    {
        // Delete physical files
        if ($this->path && Storage::exists($this->path)) {
            Storage::delete($this->path);
        }

        if ($this->thumbnail_path && Storage::exists($this->thumbnail_path)) {
            Storage::delete($this->thumbnail_path);
        }

        if ($this->medium_path && Storage::exists($this->medium_path)) {
            Storage::delete($this->medium_path);
        }

        if ($this->large_path && Storage::exists($this->large_path)) {
            Storage::delete($this->large_path);
        }

        // Delete record
        return $this->delete();
    }

    /**
     * Get image statistics.
     */
    public static function getStatistics(): array
    {
        $totalImages = static::count();
        $totalSize = static::sum('size');
        $averageSize = $totalImages > 0 ? $totalSize / $totalImages : 0;

        $typeDistribution = static::selectRaw('imageable_type, COUNT(*) as count')
            ->groupBy('imageable_type')
            ->pluck('count', 'imageable_type')
            ->toArray();

        $mimeDistribution = static::selectRaw('mime_type, COUNT(*) as count')
            ->groupBy('mime_type')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get();

        return [
            'total_images' => $totalImages,
            'total_size' => $totalSize,
            'average_size' => $averageSize,
            'type_distribution' => $typeDistribution,
            'mime_distribution' => $mimeDistribution,
        ];
    }

    /**
     * Get images by size range.
     */
    public static function getBySizeRange(int $minSize, int $maxSize): \Illuminate\Support\Collection
    {
        return static::whereBetween('size', [$minSize, $maxSize])->get();
    }

    /**
     * Get large images.
     */
    public static function getLargeImages(int $minSize = 1048576): \Illuminate\Support\Collection // 1MB
    {
        return static::where('size', '>=', $minSize)->get();
    }

    /**
     * Get images without dimensions.
     */
    public static function getWithoutDimensions(): \Illuminate\Support\Collection
    {
        return static::whereNull('width')->orWhereNull('height')->get();
    }

    /**
     * Get images by orientation.
     */
    public static function getByOrientation(string $orientation): \Illuminate\Support\Collection
    {
        return static::all()->filter(function ($image) use ($orientation) {
            return $image->orientation === $orientation;
        });
    }

    /**
     * Get image gallery for a model.
     */
    public static function getGallery(string $modelType, int $modelId): array
    {
        $images = static::where('imageable_type', $modelType)
            ->where('imageable_id', $modelId)
            ->ordered()
            ->get();

        $primary = $images->where('is_primary', true)->first();
        $gallery = $images->where('is_primary', false);

        return [
            'primary' => $primary,
            'gallery' => $gallery,
            'all' => $images,
            'count' => $images->count(),
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($image) {
            // If this is the first image for the model, make it primary
            $existingCount = static::where('imageable_type', $image->imageable_type)
                ->where('imageable_id', $image->imageable_id)
                ->count();

            if ($existingCount === 0) {
                $image->is_primary = true;
            }
        });

        static::updating(function ($image) {
            // If setting as primary, remove primary flag from other images
            if ($image->is_primary && $image->isDirty('is_primary')) {
                static::where('imageable_type', $image->imageable_type)
                    ->where('imageable_id', $image->imageable_id)
                    ->where('id', '!=', $image->id)
                    ->update(['is_primary' => false]);
            }
        });

        static::deleting(function ($image) {
            // Delete physical files when deleting the record
            if ($image->path && Storage::exists($image->path)) {
                Storage::delete($image->path);
            }

            if ($image->thumbnail_path && Storage::exists($image->thumbnail_path)) {
                Storage::delete($this->thumbnail_path);
            }

            if ($image->medium_path && Storage::exists($image->medium_path)) {
                Storage::delete($image->medium_path);
            }

            if ($image->large_path && Storage::exists($image->large_path)) {
                Storage::delete($image->large_path);
            }
        });
    }
}