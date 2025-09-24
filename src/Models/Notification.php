<?php

namespace LaravelEcommerce\Store\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Carbon\Carbon;

class Notification extends Model
{
    use HasFactory;

    /**
     * Nome da tabela
     */
    protected $table = 'notifications';

    /**
     * Campos preenchíveis
     */
    protected $fillable = [
        'type',
        'channel',
        'recipient_type',
        'recipient_id',
        'title',
        'message',
        'data',
        'scheduled_at',
        'sent_at',
        'status',
        'provider',
        'external_id',
        'response',
        'retry_count',
        'last_attempt_at',
        'error_message',
        'metadata',
    ];

    /**
     * Campos que devem ser convertidos para tipos específicos
     */
    protected $casts = [
        'type' => 'string',
        'channel' => 'string',
        'recipient_type' => 'string',
        'recipient_id' => 'integer',
        'title' => 'string',
        'message' => 'string',
        'data' => 'array',
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
        'status' => 'string',
        'provider' => 'string',
        'external_id' => 'string',
        'response' => 'string',
        'retry_count' => 'integer',
        'last_attempt_at' => 'datetime',
        'error_message' => 'string',
        'metadata' => 'array',
    ];

    /**
     * Destinatário da notificação (polimórfico)
     */
    public function recipient(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope para notificações pendentes
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending')
                    ->where(function ($q) {
                        $q->whereNull('scheduled_at')
                          ->orWhere('scheduled_at', '<=', now());
                    });
    }

    /**
     * Scope para notificações agendadas
     */
    public function scopeScheduled($query)
    {
        return $query->where('status', 'pending')
                    ->where('scheduled_at', '>', now());
    }

    /**
     * Scope para notificações enviadas
     */
    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    /**
     * Scope para notificações com falha
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope para notificações por tipo
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope para notificações por canal
     */
    public function scopeByChannel($query, string $channel)
    {
        return $query->where('channel', $channel);
    }

    /**
     * Scope para notificações por destinatário
     */
    public function scopeByRecipient($query, string $type, int $id)
    {
        return $query->where('recipient_type', $type)->where('recipient_id', $id);
    }

    /**
     * Verifica se a notificação pode ser enviada
     */
    public function canBeSent(): bool
    {
        if ($this->status !== 'pending') {
            return false;
        }

        if ($this->scheduled_at && $this->scheduled_at->isFuture()) {
            return false;
        }

        return true;
    }

    /**
     * Marca como enviada
     */
    public function markAsSent(string $provider = null, string $externalId = null, string $response = null): void
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now(),
            'provider' => $provider,
            'external_id' => $externalId,
            'response' => $response,
            'retry_count' => 0,
            'error_message' => null,
        ]);
    }

    /**
     * Marca como falha
     */
    public function markAsFailed(string $errorMessage, int $retryCount = null): void
    {
        $updateData = [
            'status' => 'failed',
            'error_message' => $errorMessage,
            'last_attempt_at' => now(),
        ];

        if ($retryCount !== null) {
            $updateData['retry_count'] = $retryCount;
        } else {
            $updateData['retry_count'] = $this->retry_count + 1;
        }

        $this->update($updateData);
    }

    /**
     * Marca como cancelada
     */
    public function markAsCancelled(): void
    {
        $this->update([
            'status' => 'cancelled',
            'error_message' => null,
        ]);
    }

    /**
     * Incrementa contador de tentativas
     */
    public function incrementRetryCount(): void
    {
        $this->update([
            'retry_count' => $this->retry_count + 1,
            'last_attempt_at' => now(),
        ]);
    }

    /**
     * Verifica se pode ser retentada
     */
    public function canBeRetried(): bool
    {
        if ($this->status !== 'failed') {
            return false;
        }

        $maxRetries = config('notifications.max_retries', 3);

        return $this->retry_count < $maxRetries;
    }

    /**
     * Obtém informações para exibição
     */
    public function getDisplayInfoAttribute(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'channel' => $this->channel,
            'recipient_type' => $this->recipient_type,
            'recipient_id' => $this->recipient_id,
            'title' => $this->title,
            'message' => $this->message,
            'data' => $this->data,
            'status' => $this->status,
            'provider' => $this->provider,
            'external_id' => $this->external_id,
            'scheduled_at' => $this->scheduled_at,
            'sent_at' => $this->sent_at,
            'retry_count' => $this->retry_count,
            'error_message' => $this->error_message,
            'formatted_scheduled_at' => $this->scheduled_at?->format('d/m/Y H:i'),
            'formatted_sent_at' => $this->sent_at?->format('d/m/Y H:i'),
            'formatted_created_at' => $this->created_at->format('d/m/Y H:i'),
            'can_be_sent' => $this->canBeSent(),
            'can_be_retried' => $this->canBeRetried(),
        ];
    }

    /**
     * Obtém estatísticas da notificação
     */
    public function getStatsAttribute(): array
    {
        return [
            'status' => $this->status,
            'retry_count' => $this->retry_count,
            'can_be_sent' => $this->canBeSent(),
            'can_be_retried' => $this->canBeRetried(),
            'is_scheduled' => $this->scheduled_at && $this->scheduled_at->isFuture(),
            'is_sent' => $this->status === 'sent',
            'is_failed' => $this->status === 'failed',
            'has_error' => !empty($this->error_message),
        ];
    }

    /**
     * Obtém configuração do canal
     */
    public function getChannelConfig(string $key, $default = null)
    {
        $config = config("notifications.channels.{$this->channel}", []);

        return $config[$key] ?? $default;
    }

    /**
     * Verifica se é uma notificação de email
     */
    public function isEmail(): bool
    {
        return $this->channel === 'mail';
    }

    /**
     * Verifica se é uma notificação SMS
     */
    public function isSMS(): bool
    {
        return $this->channel === 'sms';
    }

    /**
     * Verifica se é uma notificação push
     */
    public function isPush(): bool
    {
        return $this->channel === 'push';
    }

    /**
     * Verifica se é uma notificação de banco de dados
     */
    public function isDatabase(): bool
    {
        return $this->channel === 'database';
    }

    /**
     * Obtém tempo até o envio
     */
    public function getTimeUntilSendingAttribute(): ?string
    {
        if (!$this->scheduled_at || $this->scheduled_at->isPast()) {
            return null;
        }

        return $this->scheduled_at->diffForHumans();
    }

    /**
     * Obtém tempo desde o envio
     */
    public function getTimeSinceSendingAttribute(): ?string
    {
        if (!$this->sent_at) {
            return null;
        }

        return $this->sent_at->diffForHumans();
    }
}