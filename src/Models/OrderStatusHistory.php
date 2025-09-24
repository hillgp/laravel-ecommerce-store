<?php

namespace LaravelEcommerce\Store\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderStatusHistory extends Model
{
    use HasFactory;

    /**
     * Nome da tabela
     */
    protected $table = 'order_status_history';

    /**
     * Campos preenchíveis
     */
    protected $fillable = [
        'order_id',
        'status',
        'previous_status',
        'user_id',
        'notes',
        'meta_data',
    ];

    /**
     * Campos que devem ser convertidos para tipos específicos
     */
    protected $casts = [
        'order_id' => 'integer',
        'user_id' => 'integer',
        'meta_data' => 'array',
    ];

    /**
     * Campos que devem ser ocultos na serialização
     */
    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    /**
     * Status possíveis do pedido
     */
    const STATUSES = [
        'pending' => 'Pendente',
        'confirmed' => 'Confirmado',
        'processing' => 'Processando',
        'shipped' => 'Enviado',
        'delivered' => 'Entregue',
        'cancelled' => 'Cancelado',
        'refunded' => 'Reembolsado',
        'returned' => 'Devolvido',
    ];

    /**
     * Pedido ao qual o histórico pertence
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    /**
     * Usuário que fez a alteração
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    /**
     * Obtém o status formatado
     */
    public function getFormattedStatusAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /**
     * Obtém o status anterior formatado
     */
    public function getFormattedPreviousStatusAttribute(): string
    {
        return $this->previous_status ? (self::STATUSES[$this->previous_status] ?? $this->previous_status) : '';
    }

    /**
     * Verifica se houve mudança de status
     */
    public function hasStatusChanged(): bool
    {
        return !empty($this->previous_status) && $this->previous_status !== $this->status;
    }

    /**
     * Obtém informações sobre a mudança de status
     */
    public function getStatusChangeInfoAttribute(): array
    {
        return [
            'from' => $this->formatted_previous_status,
            'to' => $this->formatted_status,
            'has_changed' => $this->hasStatusChanged(),
            'changed_at' => $this->created_at,
        ];
    }

    /**
     * Obtém informações do usuário que fez a alteração
     */
    public function getUserInfoAttribute(): array
    {
        return [
            'user_id' => $this->user_id,
            'user_name' => $this->user ? $this->user->name : 'Sistema',
            'user_email' => $this->user ? $this->user->email : null,
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
     * Obtém informações de contexto da alteração
     */
    public function getContextInfoAttribute(): array
    {
        return [
            'ip_address' => $this->getMeta('ip_address'),
            'user_agent' => $this->getMeta('user_agent'),
            'notes' => $this->notes,
            'meta_data' => $this->meta_data,
        ];
    }

    /**
     * Verifica se a alteração foi feita por um usuário
     */
    public function isUserAction(): bool
    {
        return !is_null($this->user_id);
    }

    /**
     * Verifica se a alteração foi feita pelo sistema
     */
    public function isSystemAction(): bool
    {
        return is_null($this->user_id);
    }

    /**
     * Obtém informações para exibição
     */
    public function getDisplayInfoAttribute(): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'status' => $this->status,
            'formatted_status' => $this->formatted_status,
            'previous_status' => $this->previous_status,
            'formatted_previous_status' => $this->formatted_previous_status,
            'has_changed' => $this->hasStatusChanged(),
            'status_change_info' => $this->status_change_info,
            'user_info' => $this->user_info,
            'is_user_action' => $this->isUserAction(),
            'is_system_action' => $this->isSystemAction(),
            'notes' => $this->notes,
            'context_info' => $this->context_info,
            'created_at' => $this->created_at,
            'formatted_created_at' => $this->created_at->format('d/m/Y H:i:s'),
        ];
    }

    /**
     * Scope para alterações por status
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope para alterações por usuário
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope para alterações do sistema
     */
    public function scopeSystemActions($query)
    {
        return $query->whereNull('user_id');
    }

    /**
     * Scope para alterações manuais
     */
    public function scopeUserActions($query)
    {
        return $query->whereNotNull('user_id');
    }

    /**
     * Scope para alterações com mudança de status
     */
    public function scopeStatusChanges($query)
    {
        return $query->whereNotNull('previous_status')->whereRaw('previous_status != status');
    }

    /**
     * Scope para alterações sem mudança de status
     */
    public function scopeNoStatusChanges($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('previous_status')
              ->orWhereRaw('previous_status = status');
        });
    }

    /**
     * Scope para alterações recentes
     */
    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }

    /**
     * Obtém estatísticas das alterações
     */
    public function getStatsAttribute(): array
    {
        return [
            'has_status_changed' => $this->hasStatusChanged(),
            'is_user_action' => $this->isUserAction(),
            'is_system_action' => $this->isSystemAction(),
            'has_notes' => !empty($this->notes),
            'has_meta_data' => !empty($this->meta_data),
        ];
    }
}