<?php

namespace LaravelEcommerce\Store\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductReview extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Nome da tabela
     */
    protected $table = 'product_reviews';

    /**
     * Campos preenchíveis
     */
    protected $fillable = [
        'product_id',
        'customer_id',
        'order_id',
        'rating',
        'title',
        'comment',
        'pros',
        'cons',
        'status',
        'is_verified_purchase',
        'helpful_votes',
        'total_votes',
        'images',
        'videos',
        'meta_data',
        'reviewed_at',
    ];

    /**
     * Campos que devem ser convertidos para tipos específicos
     */
    protected $casts = [
        'product_id' => 'integer',
        'customer_id' => 'integer',
        'order_id' => 'integer',
        'rating' => 'integer',
        'is_verified_purchase' => 'boolean',
        'helpful_votes' => 'integer',
        'total_votes' => 'integer',
        'images' => 'array',
        'videos' => 'array',
        'meta_data' => 'array',
        'reviewed_at' => 'datetime',
    ];

    /**
     * Campos que devem ser ocultos na serialização
     */
    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * Status possíveis das avaliações
     */
    const STATUSES = [
        'pending' => 'Pendente',
        'approved' => 'Aprovada',
        'rejected' => 'Rejeitada',
        'spam' => 'Spam',
    ];

    /**
     * Produto avaliado
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Cliente que fez a avaliação
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    /**
     * Pedido relacionado à avaliação
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    /**
     * Votos na avaliação
     */
    public function votes(): HasMany
    {
        return $this->hasMany(ReviewVote::class, 'review_id');
    }

    /**
     * Votos úteis
     */
    public function helpfulVotes(): HasMany
    {
        return $this->votes()->where('is_helpful', true);
    }

    /**
     * Scope para avaliações aprovadas
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope para avaliações pendentes
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope para avaliações de compra verificada
     */
    public function scopeVerified($query)
    {
        return $query->where('is_verified_purchase', true);
    }

    /**
     * Scope para avaliações por produto
     */
    public function scopeByProduct($query, int $productId)
    {
        return $query->where('product_id', $productId);
    }

    /**
     * Scope para avaliações por cliente
     */
    public function scopeByCustomer($query, int $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    /**
     * Scope para avaliações por rating
     */
    public function scopeByRating($query, int $rating)
    {
        return $query->where('rating', $rating);
    }

    /**
     * Scope para avaliações ordenadas por votos úteis
     */
    public function scopeOrderByHelpful($query)
    {
        return $query->orderByDesc('helpful_votes')->orderByDesc('created_at');
    }

    /**
     * Scope para avaliações ordenadas por data
     */
    public function scopeOrderByDate($query)
    {
        return $query->orderByDesc('reviewed_at')->orderByDesc('created_at');
    }

    /**
     * Verifica se a avaliação pode ser editada
     */
    public function canBeEdited(): bool
    {
        return $this->status === 'pending' || $this->status === 'approved';
    }

    /**
     * Verifica se a avaliação pode ser deletada
     */
    public function canBeDeleted(): bool
    {
        return true; // Por enquanto todas podem ser deletadas
    }

    /**
     * Verifica se a avaliação pode receber votos
     */
    public function canBeVoted(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Verifica se um cliente pode votar nesta avaliação
     */
    public function canCustomerVote(int $customerId): bool
    {
        if (!$this->canBeVoted()) {
            return false;
        }

        return !$this->votes()->where('customer_id', $customerId)->exists();
    }

    /**
     * Adiciona um voto à avaliação
     */
    public function addVote(int $customerId, bool $isHelpful): ReviewVote
    {
        if (!$this->canCustomerVote($customerId)) {
            throw new \Exception('Cliente não pode votar nesta avaliação');
        }

        $vote = $this->votes()->create([
            'customer_id' => $customerId,
            'is_helpful' => $isHelpful,
        ]);

        // Atualiza os contadores
        $this->updateVoteCounts();

        return $vote;
    }

    /**
     * Remove um voto da avaliação
     */
    public function removeVote(int $customerId): bool
    {
        $vote = $this->votes()->where('customer_id', $customerId)->first();

        if (!$vote) {
            return false;
        }

        $vote->delete();
        $this->updateVoteCounts();

        return true;
    }

    /**
     * Atualiza os contadores de votos
     */
    protected function updateVoteCounts(): void
    {
        $this->update([
            'helpful_votes' => $this->helpfulVotes()->count(),
            'total_votes' => $this->votes()->count(),
        ]);
    }

    /**
     * Obtém a porcentagem de votos úteis
     */
    public function getHelpfulPercentageAttribute(): float
    {
        if ($this->total_votes === 0) {
            return 0;
        }

        return round(($this->helpful_votes / $this->total_votes) * 100, 1);
    }

    /**
     * Obtém o status formatado
     */
    public function getFormattedStatusAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /**
     * Obtém o rating formatado
     */
    public function getFormattedRatingAttribute(): string
    {
        return str_repeat('★', $this->rating) . str_repeat('☆', 5 - $this->rating);
    }

    /**
     * Obtém as imagens formatadas
     */
    public function getFormattedImagesAttribute(): array
    {
        if (empty($this->images)) {
            return [];
        }

        return array_map(function ($image) {
            return [
                'url' => asset('storage/reviews/' . $image),
                'thumbnail' => asset('storage/reviews/thumbnails/' . $image),
                'filename' => $image,
            ];
        }, $this->images);
    }

    /**
     * Obtém os vídeos formatados
     */
    public function getFormattedVideosAttribute(): array
    {
        if (empty($this->videos)) {
            return [];
        }

        return array_map(function ($video) {
            return [
                'url' => asset('storage/reviews/' . $video),
                'thumbnail' => asset('storage/reviews/thumbnails/' . $video),
                'filename' => $video,
            ];
        }, $this->videos);
    }

    /**
     * Obtém informações do cliente para exibição
     */
    public function getCustomerInfoAttribute(): array
    {
        return [
            'id' => $this->customer_id,
            'name' => $this->customer->name,
            'avatar' => $this->customer->avatar_url,
            'verified_purchase' => $this->is_verified_purchase,
        ];
    }

    /**
     * Obtém estatísticas da avaliação
     */
    public function getStatsAttribute(): array
    {
        return [
            'rating' => $this->rating,
            'formatted_rating' => $this->formatted_rating,
            'helpful_votes' => $this->helpful_votes,
            'total_votes' => $this->total_votes,
            'helpful_percentage' => $this->helpful_percentage,
            'status' => $this->status,
            'formatted_status' => $this->formatted_status,
            'is_verified_purchase' => $this->is_verified_purchase,
            'has_images' => !empty($this->images),
            'has_videos' => !empty($this->videos),
            'has_comment' => !empty($this->comment),
            'has_pros' => !empty($this->pros),
            'has_cons' => !empty($this->cons),
            'images_count' => count($this->images ?? []),
            'videos_count' => count($this->videos ?? []),
        ];
    }

    /**
     * Obtém metadados
     */
    public function getMeta(string $key)
    {
        return $this->meta_data[$key] ?? null;
    }

    /**
     * Define metadados
     */
    public function setMeta(string $key, $value): void
    {
        $metaData = $this->meta_data ?? [];
        $metaData[$key] = $value;
        $this->meta_data = $metaData;
        $this->save();
    }

    /**
     * Remove metadados
     */
    public function removeMeta(string $key): void
    {
        $metaData = $this->meta_data ?? [];
        unset($metaData[$key]);
        $this->meta_data = $metaData;
        $this->save();
    }

    /**
     * Aprova a avaliação
     */
    public function approve(): void
    {
        $this->update([
            'status' => 'approved',
            'reviewed_at' => $this->reviewed_at ?? now(),
        ]);

        // Atualiza a avaliação do produto
        $this->product->updateRating();
    }

    /**
     * Rejeita a avaliação
     */
    public function reject(): void
    {
        $this->update(['status' => 'rejected']);
    }

    /**
     * Marca como spam
     */
    public function markAsSpam(): void
    {
        $this->update(['status' => 'spam']);
    }

    /**
     * Obtém informações para exibição
     */
    public function getDisplayInfoAttribute(): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'customer_info' => $this->customer_info,
            'rating' => $this->rating,
            'formatted_rating' => $this->formatted_rating,
            'title' => $this->title,
            'comment' => $this->comment,
            'pros' => $this->pros,
            'cons' => $this->cons,
            'status' => $this->status,
            'formatted_status' => $this->formatted_status,
            'is_verified_purchase' => $this->is_verified_purchase,
            'helpful_votes' => $this->helpful_votes,
            'total_votes' => $this->total_votes,
            'helpful_percentage' => $this->helpful_percentage,
            'images' => $this->formatted_images,
            'videos' => $this->formatted_videos,
            'stats' => $this->stats,
            'reviewed_at' => $this->reviewed_at,
            'formatted_reviewed_at' => $this->reviewed_at?->format('d/m/Y H:i:s'),
            'created_at' => $this->created_at,
            'formatted_created_at' => $this->created_at->format('d/m/Y H:i:s'),
        ];
    }

    /**
     * Verifica se a avaliação tem mídia
     */
    public function hasMedia(): bool
    {
        return !empty($this->images) || !empty($this->videos);
    }

    /**
     * Verifica se a avaliação tem conteúdo detalhado
     */
    public function hasDetailedContent(): bool
    {
        return !empty($this->pros) || !empty($this->cons) || !empty($this->comment);
    }

    /**
     * Calcula o tempo desde a avaliação
     */
    public function getTimeSinceReviewAttribute(): string
    {
        $date = $this->reviewed_at ?? $this->created_at;

        $diff = $date->diffForHumans();

        return $diff;
    }

    /**
     * Obtém resumo da avaliação
     */
    public function getSummaryAttribute(): string
    {
        if (!empty($this->comment)) {
            return \Illuminate\Support\Str::limit($this->comment, 150);
        }

        if (!empty($this->title)) {
            return $this->title;
        }

        return 'Avaliação de ' . $this->rating . ' estrelas';
    }
}