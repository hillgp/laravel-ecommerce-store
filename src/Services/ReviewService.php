<?php

namespace LaravelEcommerce\Store\Services;

use LaravelEcommerce\Store\Models\Product;
use LaravelEcommerce\Store\Models\ProductReview;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ReviewService
{
    /**
     * Create a new review.
     */
    public function createReview(array $data): ProductReview
    {
        $review = ProductReview::create($data);

        // Update product rating
        $this->updateProductRating($data['product_id']);

        return $review->load('customer');
    }

    /**
     * Update a review.
     */
    public function updateReview(ProductReview $review, array $data): ProductReview
    {
        $review->update($data);

        // Update product rating
        $this->updateProductRating($review->product_id);

        return $review->fresh();
    }

    /**
     * Delete a review.
     */
    public function deleteReview(ProductReview $review): bool
    {
        $productId = $review->product_id;
        $deleted = $review->delete();

        if ($deleted) {
            // Update product rating
            $this->updateProductRating($productId);
        }

        return $deleted;
    }

    /**
     * Get product reviews.
     */
    public function getProductReviews(Product $product, array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = $product->reviews()->with('customer');

        if (isset($filters['rating'])) {
            $query->where('rating', $filters['rating']);
        }

        if (isset($filters['verified'])) {
            $query->where('verified_purchase', $filters['verified']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Get review statistics for product.
     */
    public function getReviewStats(Product $product): array
    {
        $reviews = $product->reviews;

        if ($reviews->isEmpty()) {
            return [
                'average_rating' => 0,
                'total_reviews' => 0,
                'rating_distribution' => [],
                'verified_reviews' => 0,
            ];
        }

        $totalReviews = $reviews->count();
        $averageRating = $reviews->avg('rating');
        $verifiedReviews = $reviews->where('verified_purchase', true)->count();

        $ratingDistribution = [];
        for ($i = 1; $i <= 5; $i++) {
            $ratingDistribution[$i] = $reviews->where('rating', $i)->count();
        }

        return [
            'average_rating' => round($averageRating, 1),
            'total_reviews' => $totalReviews,
            'rating_distribution' => $ratingDistribution,
            'verified_reviews' => $verifiedReviews,
        ];
    }

    /**
     * Approve a review.
     */
    public function approveReview(ProductReview $review): ProductReview
    {
        $review->update(['status' => 'approved']);
        return $review->fresh();
    }

    /**
     * Reject a review.
     */
    public function rejectReview(ProductReview $review, string $reason = null): ProductReview
    {
        $review->update([
            'status' => 'rejected',
            'rejection_reason' => $reason,
        ]);
        return $review->fresh();
    }

    /**
     * Get pending reviews.
     */
    public function getPendingReviews(int $perPage = 20): LengthAwarePaginator
    {
        return ProductReview::where('status', 'pending')
            ->with(['product', 'customer'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get reviews by customer.
     */
    public function getCustomerReviews(int $customerId, int $perPage = 10): LengthAwarePaginator
    {
        return ProductReview::where('customer_id', $customerId)
            ->with('product')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Check if customer can review product.
     */
    public function canReviewProduct(int $customerId, int $productId): bool
    {
        // Check if customer has purchased the product
        $hasPurchased = \LaravelEcommerce\Store\Models\Order::where('user_id', $customerId)
            ->whereHas('items', function ($query) use ($productId) {
                $query->where('product_id', $productId);
            })
            ->where('status', '!=', 'cancelled')
            ->exists();

        if (!$hasPurchased) {
            return false;
        }

        // Check if customer has already reviewed this product
        $hasReviewed = ProductReview::where('customer_id', $customerId)
            ->where('product_id', $productId)
            ->exists();

        return !$hasReviewed;
    }

    /**
     * Update product rating after review change.
     */
    protected function updateProductRating(int $productId): void
    {
        $product = Product::find($productId);

        if (!$product) {
            return;
        }

        $reviews = $product->reviews()->where('status', 'approved')->get();
        $averageRating = $reviews->avg('rating');
        $reviewCount = $reviews->count();

        $product->update([
            'rating' => $averageRating ? round($averageRating, 2) : null,
            'review_count' => $reviewCount,
        ]);
    }

    /**
     * Get review summary.
     */
    public function getReviewSummary(Product $product): array
    {
        $stats = $this->getReviewStats($product);

        return [
            'average_rating' => $stats['average_rating'],
            'total_reviews' => $stats['total_reviews'],
            'rating_percentage' => $stats['average_rating'] ? ($stats['average_rating'] / 5) * 100 : 0,
            'recommendation_rate' => $this->calculateRecommendationRate($product),
        ];
    }

    /**
     * Calculate recommendation rate.
     */
    protected function calculateRecommendationRate(Product $product): float
    {
        $reviews = $product->reviews()->where('status', 'approved')->get();
        $positiveReviews = $reviews->where('rating', '>=', 4)->count();

        return $reviews->count() > 0 ? ($positiveReviews / $reviews->count()) * 100 : 0;
    }

    /**
     * Get top rated products.
     */
    public function getTopRatedProducts(int $limit = 10): Collection
    {
        return Product::where('rating', '>=', 4)
            ->where('review_count', '>=', 5)
            ->orderBy('rating', 'desc')
            ->orderBy('review_count', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get most reviewed products.
     */
    public function getMostReviewedProducts(int $limit = 10): Collection
    {
        return Product::orderBy('review_count', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Export reviews.
     */
    public function exportReviews(array $filters = []): Collection
    {
        $query = ProductReview::with(['product', 'customer']);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['rating'])) {
            $query->where('rating', $filters['rating']);
        }

        if (isset($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->get();
    }
}