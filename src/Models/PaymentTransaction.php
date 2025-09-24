<?php

namespace LaravelEcommerce\Store\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class PaymentTransaction extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Nome da tabela
     */
    protected $table = 'payment_transactions';

    /**
     * Campos preenchíveis
     */
    protected $fillable = [
        'transaction_id',
        'order_id',
        'gateway',
        'status',
        'type',
        'amount',
        'currency',
        'payment_method',
        'external_id',
        'gateway_response',
        'meta_data',
        'notes',
        'processed_at',
    ];

    /**
     * Campos que devem ser convertidos para tipos específicos
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'gateway_response' => 'array',
        'meta_data' => 'array',
        'processed_at' => 'datetime',
    ];

    /**
     * Campos que devem ser ocultos na serialização
     */
    protected $hidden = [
        'gateway_response',
        'meta_data',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * Status possíveis das transações
     */
    const STATUSES = [
        'pending' => 'Pendente',
        'processing' => 'Processando',
        'completed' => 'Concluída',
        'failed' => 'Falhou',
        'cancelled' => 'Cancelada',
        'refunded' => 'Reembolsada',
        'partially_refunded' => 'Parcialmente Reembolsada',
        'chargeback' => 'Chargeback',
    ];

    /**
     * Tipos de transação
     */
    const TYPES = [
        'payment' => 'Pagamento',
        'refund' => 'Reembolso',
        'chargeback' => 'Chargeback',
        'authorization' => 'Autorização',
        'capture' => 'Captura',
        'void' => 'Cancelamento',
    ];

    /**
     * Gateways suportados
     */
    const GATEWAYS = [
        'stripe' => 'Stripe',
        'mercadopago' => 'Mercado Pago',
        'pagseguro' => 'PagSeguro',
        'paypal' => 'PayPal',
        'manual' => 'Manual',
    ];

    /**
     * Boot do modelo
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($transaction) {
            if (empty($transaction->transaction_id)) {
                $transaction->transaction_id = self::generateTransactionId();
            }
        });
    }

    /**
     * Pedido relacionado à transação
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    /**
     * Scope para transações por status
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope para transações por gateway
     */
    public function scopeByGateway($query, string $gateway)
    {
        return $query->where('gateway', $gateway);
    }

    /**
     * Scope para transações por tipo
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope para transações pendentes
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope para transações concluídas
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope para transações falharam
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope para transações recentes
     */
    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }

    /**
     * Gera um ID único para a transação
     */
    public static function generateTransactionId(): string
    {
        do {
            $transactionId = 'TXN-' . date('Ymd') . '-' . strtoupper(Str::random(8));
        } while (self::where('transaction_id', $transactionId)->exists());

        return $transactionId;
    }

    /**
     * Verifica se a transação foi bem-sucedida
     */
    public function isSuccessful(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Verifica se a transação falhou
     */
    public function isFailed(): bool
    {
        return in_array($this->status, ['failed', 'cancelled']);
    }

    /**
     * Verifica se a transação está pendente
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Verifica se a transação é um pagamento
     */
    public function isPayment(): bool
    {
        return $this->type === 'payment';
    }

    /**
     * Verifica se a transação é um reembolso
     */
    public function isRefund(): bool
    {
        return $this->type === 'refund';
    }

    /**
     * Verifica se a transação é um chargeback
     */
    public function isChargeback(): bool
    {
        return $this->type === 'chargeback';
    }

    /**
     * Obtém o status formatado
     */
    public function getFormattedStatusAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /**
     * Obtém o tipo formatado
     */
    public function getFormattedTypeAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    /**
     * Obtém o gateway formatado
     */
    public function getFormattedGatewayAttribute(): string
    {
        return self::GATEWAYS[$this->gateway] ?? $this->gateway;
    }

    /**
     * Obtém o valor formatado
     */
    public function getFormattedAmountAttribute(): string
    {
        return $this->currency . ' ' . number_format($this->amount, 2, ',', '.');
    }

    /**
     * Obtém informações da resposta do gateway
     */
    public function getGatewayInfoAttribute(): array
    {
        return [
            'gateway' => $this->gateway,
            'formatted_gateway' => $this->formatted_gateway,
            'external_id' => $this->external_id,
            'response' => $this->gateway_response,
        ];
    }

    /**
     * Obtém informações de processamento
     */
    public function getProcessingInfoAttribute(): array
    {
        return [
            'processed_at' => $this->processed_at,
            'processing_time' => $this->processed_at ? $this->created_at->diffInSeconds($this->processed_at) : null,
            'is_processed' => !is_null($this->processed_at),
        ];
    }

    /**
     * Obtém estatísticas da transação
     */
    public function getStatsAttribute(): array
    {
        return [
            'status' => $this->status,
            'formatted_status' => $this->formatted_status,
            'type' => $this->type,
            'formatted_type' => $this->formatted_type,
            'gateway' => $this->gateway,
            'formatted_gateway' => $this->formatted_gateway,
            'amount' => $this->amount,
            'formatted_amount' => $this->formatted_amount,
            'is_successful' => $this->isSuccessful(),
            'is_failed' => $this->isFailed(),
            'is_pending' => $this->isPending(),
            'is_payment' => $this->isPayment(),
            'is_refund' => $this->isRefund(),
            'is_chargeback' => $this->isChargeback(),
            'processing_info' => $this->processing_info,
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
     * Atualiza o status da transação
     */
    public function updateStatus(string $status, string $notes = null): void
    {
        $this->update([
            'status' => $status,
            'processed_at' => $status !== 'pending' ? now() : $this->processed_at,
            'notes' => $notes ?? $this->notes,
        ]);
    }

    /**
     * Marca a transação como processada
     */
    public function markAsProcessed(string $notes = null): void
    {
        $this->update([
            'processed_at' => now(),
            'notes' => $notes ?? $this->notes,
        ]);
    }

    /**
     * Obtém informações para exibição
     */
    public function getDisplayInfoAttribute(): array
    {
        return [
            'id' => $this->id,
            'transaction_id' => $this->transaction_id,
            'order_id' => $this->order_id,
            'gateway' => $this->gateway,
            'formatted_gateway' => $this->formatted_gateway,
            'status' => $this->status,
            'formatted_status' => $this->formatted_status,
            'type' => $this->type,
            'formatted_type' => $this->formatted_type,
            'amount' => $this->amount,
            'formatted_amount' => $this->formatted_amount,
            'payment_method' => $this->payment_method,
            'external_id' => $this->external_id,
            'processed_at' => $this->processed_at,
            'formatted_processed_at' => $this->processed_at?->format('d/m/Y H:i:s'),
            'notes' => $this->notes,
            'stats' => $this->stats,
        ];
    }

    /**
     * Verifica se a transação pode ser reembolsada
     */
    public function canBeRefunded(): bool
    {
        return $this->isSuccessful() && $this->isPayment() && $this->amount > 0;
    }

    /**
     * Verifica se a transação pode ser cancelada
     */
    public function canBeCancelled(): bool
    {
        return $this->isPending() && $this->isPayment();
    }

    /**
     * Obtém informações de reembolso
     */
    public function getRefundInfoAttribute(): array
    {
        return [
            'can_be_refunded' => $this->canBeRefunded(),
            'can_be_cancelled' => $this->canBeCancelled(),
            'refundable_amount' => $this->amount,
            'formatted_refundable_amount' => $this->formatted_amount,
        ];
    }

    /**
     * Calcula o tempo de processamento
     */
    public function getProcessingTimeAttribute(): ?int
    {
        if (!$this->processed_at) {
            return null;
        }

        return $this->created_at->diffInSeconds($this->processed_at);
    }

    /**
     * Obtém o tempo de processamento formatado
     */
    public function getFormattedProcessingTimeAttribute(): ?string
    {
        $seconds = $this->processing_time;

        if (!$seconds) {
            return null;
        }

        if ($seconds < 60) {
            return $seconds . 's';
        }

        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;

        if ($minutes < 60) {
            return $minutes . 'm ' . $remainingSeconds . 's';
        }

        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;

        return $hours . 'h ' . $remainingMinutes . 'm';
    }
}