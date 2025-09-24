<?php

namespace LaravelEcommerce\Store\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReviewVote extends Model
{
    use HasFactory;

    /**
     * Nome da tabela
     */
    protected $table = 'review_votes';

    /**
     * Campos preenchíveis
     */
    protected $fillable = [
        'review_id',
        'customer_id',
        'is_helpful',
    ];

    /**
     * Campos que devem ser convertidos para tipos específicos
     */
    protected $casts = [
        'review_id' => 'integer',
        'customer_id' => 'integer',
        'is_helpful' => 'boolean',
    ];

    /**
     * Campos que devem ser ocultos na serialização
     */
    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    /**
     * Avaliação votada
     */
    public function review(): BelongsTo
    {
        return $this->belongsTo(ProductReview::class, 'review_id');
    }

    /**
     * Cliente que votou
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    /**
     * Scope para votos úteis
     */
    public function scopeHelpful($query)
    {
        return $query->where('is_helpful', true);
    }

    /**
     * Scope para votos não úteis
     */
    public function scopeNotHelpful($query)
    {
        return $query->where('is_helpful', false);
    }

    /**
     * Scope para votos por cliente
     */
    public function scopeByCustomer($query, int $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    /**
     * Scope para votos por avaliação
     */
    public function scopeByReview($query, int $reviewId)
    {
        return $query->where('review_id', $reviewId);
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
        ];
    }

    /**
     * Obtém informações da avaliação para exibição
     */
    public function getReviewInfoAttribute(): array
    {
        return [
            'id' => $this->review_id,
            'product_id' => $this->review->product_id,
            'product_name' => $this->review->product->name,
            'rating' => $this->review->rating,
            'status' => $this->review->status,
        ];
    }

    /**
     * Obtém informações para exibição
     */
    public function getDisplayInfoAttribute(): array
    {
        return [
            'id' => $this->id,
            'review_id' => $this->review_id,
            'customer_id' => $this->customer_id,
            'customer_info' => $this->customer_info,
            'review_info' => $this->review_info,
            'is_helpful' => $this->is_helpful,
            'formatted_is_helpful' => $this->is_helpful ? 'Útil' : 'Não útil',
            'created_at' => $this->created_at,
            'formatted_created_at' => $this->created_at->format('d/m/Y H:i:s'),
        ];
    }

    /**
     * Verifica se o voto pode ser alterado
     */
    public function canBeChanged(): bool
    {
        return true; // Por enquanto todos podem ser alterados
    }

    /**
     * Verifica se o voto pode ser removido
     */
    public function canBeRemoved(): bool
    {
        return true; // Por enquanto todos podem ser removidos
    }

    /**
     * Inverte o voto
     */
    public function toggle(): void
    {
        $this->update(['is_helpful' => !$this->is_helpful]);
    }

    /**
     * Marca como útil
     */
    public function markAsHelpful(): void
    {
        $this->update(['is_helpful' => true]);
    }

    /**
     * Marca como não útil
     */
    public function markAsNotHelpful(): void
    {
        $this->update(['is_helpful' => false]);
    }

    /**
     * Obtém estatísticas do voto
     */
    public function getStatsAttribute(): array
    {
        return [
            'is_helpful' => $this->is_helpful,
            'can_be_changed' => $this->canBeChanged(),
            'can_be_removed' => $this->canBeRemoved(),
        ];
    }
}