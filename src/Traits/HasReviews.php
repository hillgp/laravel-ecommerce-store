<?php

namespace LaravelEcommerce\Store\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;

trait HasReviews
{
    /**
     * Get all reviews for the model.
     */
    public function reviews(): MorphMany
    {
        return $this->morphMany(\LaravelEcommerce\Store\Models\ProductReview::class, 'reviewable');
    }

    /**
     * Get approved reviews for the model.
     */
    public function approvedReviews(): MorphMany
    {
        return $this->reviews()->where('status', 'approved');
    }

    /**
     * Get pending reviews for the model.
     */
    public function pendingReviews(): MorphMany
    {
        return $this->reviews()->where('status', 'pending');
    }

    /**
     * Get average rating for the model.
     */
    public function getAverageRatingAttribute(): ?float
    {
        return $this->approvedReviews()->avg('rating');
    }

    /**
     * Get total review count for the model.
     */
    public function getReviewCountAttribute(): int
    {
        return $this->approvedReviews()->count();
    }

    /**
     * Get rating distribution for the model.
     */
    public function getRatingDistributionAttribute(): array
    {
        $distribution = [];
        for ($i = 1; $i <= 5; $i++) {
            $distribution[$i] = $this->approvedReviews()->where('rating', $i)->count();
        }
        return $distribution;
    }

    /**
     * Get reviews grouped by rating.
     */
    public function getReviewsByRating(int $rating): Collection
    {
        return $this->approvedReviews()->where('rating', $rating)->get();
    }

    /**
     * Get reviews with pagination.
     */
    public function getPaginatedReviews(int $perPage = 10): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return $this->approvedReviews()
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Add a review to the model.
     */
    public function addReview(array $data): \LaravelEcommerce\Store\Models\ProductReview
    {
        $data['status'] = config('store.reviews.auto_approve', false) ? 'approved' : 'pending';

        return $this->reviews()->create($data);
    }

    /**
     * Update a review.
     */
    public function updateReview(int $reviewId, array $data): bool
    {
        $review = $this->reviews()->find($reviewId);

        if (!$review) {
            return false;
        }

        return $review->update($data);
    }

    /**
     * Delete a review.
     */
    public function deleteReview(int $reviewId): bool
    {
        $review = $this->reviews()->find($reviewId);

        if (!$review) {
            return false;
        }

        return $review->delete();
    }

    /**
     * Approve a review.
     */
    public function approveReview(int $reviewId): bool
    {
        $review = $this->reviews()->find($reviewId);

        if (!$review) {
            return false;
        }

        return $review->update(['status' => 'approved']);
    }

    /**
     * Reject a review.
     */
    public function rejectReview(int $reviewId, string $reason = null): bool
    {
        $review = $this->reviews()->find($reviewId);

        if (!$review) {
            return false;
        }

        return $review->update([
            'status' => 'rejected',
            'rejection_reason' => $reason,
        ]);
    }

    /**
     * Get review statistics.
     */
    public function getReviewStats(): array
    {
        $totalReviews = $this->review_count;
        $averageRating = $this->average_rating;

        $distribution = $this->rating_distribution;

        $recommendationRate = $totalReviews > 0
            ? ($distribution[5] + $distribution[4]) / $totalReviews * 100
            : 0;

        return [
            'total_reviews' => $totalReviews,
            'average_rating' => $averageRating ? round($averageRating, 1) : null,
            'rating_distribution' => $distribution,
            'recommendation_rate' => round($recommendationRate, 1),
            'pending_reviews' => $this->pendingReviews()->count(),
        ];
    }

    /**
     * Check if a customer can review the model.
     */
    public function canBeReviewedBy(int $customerId): bool
    {
        // Check if customer has purchased the product
        if ($this instanceof \LaravelEcommerce\Store\Models\Product) {
            $hasPurchased = \LaravelEcommerce\Store\Models\Order::where('user_id', $customerId)
                ->whereHas('items', function ($query) {
                    $query->where('product_id', $this->id);
                })
                ->where('status', '!=', 'cancelled')
                ->exists();

            if (!$hasPurchased) {
                return false;
            }
        }

        // Check if customer has already reviewed this item
        $hasReviewed = $this->reviews()
            ->where('customer_id', $customerId)
            ->exists();

        return !$hasReviewed;
    }

    /**
     * Get review summary for display.
     */
    public function getReviewSummaryAttribute(): array
    {
        $stats = $this->review_stats;

        return [
            'average_rating' => $stats['average_rating'],
            'total_reviews' => $stats['total_reviews'],
            'rating_percentage' => $stats['average_rating'] ? ($stats['average_rating'] / 5) * 100 : 0,
            'recommendation_rate' => $stats['recommendation_rate'],
            'stars' => $this->getStarsArray(),
        ];
    }

    /**
     * Get stars array for display.
     */
    protected function getStarsArray(): array
    {
        $rating = $this->average_rating ?? 0;
        $stars = [];

        for ($i = 1; $i <= 5; $i++) {
            if ($rating >= $i) {
                $stars[] = 'full';
            } elseif ($rating >= $i - 0.5) {
                $stars[] = 'half';
            } else {
                $stars[] = 'empty';
            }
        }

        return $stars;
    }

    /**
     * Get formatted rating for display.
     */
    public function getFormattedRatingAttribute(): string
    {
        $rating = $this->average_rating;

        if (!$rating) {
            return 'Sem avaliações';
        }

        return number_format($rating, 1) . ' de 5 estrelas';
    }

    /**
     * Get formatted review count for display.
     */
    public function getFormattedReviewCountAttribute(): string
    {
        $count = $this->review_count;

        if ($count === 0) {
            return 'Nenhuma avaliação';
        } elseif ($count === 1) {
            return '1 avaliação';
        } else {
            return $count . ' avaliações';
        }
    }

    /**
     * Get recent reviews.
     */
    public function getRecentReviews(int $limit = 5): Collection
    {
        return $this->approvedReviews()
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get top rated reviews.
     */
    public function getTopRatedReviews(int $limit = 5): Collection
    {
        return $this->approvedReviews()
            ->orderBy('rating', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get reviews with images.
     */
    public function getReviewsWithImages(): Collection
    {
        return $this->approvedReviews()
            ->whereNotNull('images')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get verified reviews.
     */
    public function getVerifiedReviews(): Collection
    {
        return $this->approvedReviews()
            ->where('verified_purchase', true)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Calculate and update rating.
     */
    public function updateRating(): void
    {
        $approvedReviews = $this->approvedReviews()->get();
        $averageRating = $approvedReviews->avg('rating');
        $reviewCount = $approvedReviews->count();

        $this->update([
            'rating' => $averageRating ? round($averageRating, 2) : null,
            'review_count' => $reviewCount,
        ]);
    }

    /**
     * Boot the trait.
     */
    protected static function bootHasReviews(): void
    {
        static::saved(function ($model) {
            // Update rating when reviews change
            if ($model->wasChanged('rating') || $model->wasChanged('review_count')) {
                $model->updateRating();
            }
        });

        static::deleting(function ($model) {
            // Delete all reviews when model is deleted
            $model->reviews()->delete();
        });
    }
}