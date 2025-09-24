<?php

namespace LaravelEcommerce\Store\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class ShippingTracking extends Model
{
    use HasFactory;

    /**
     * Nome da tabela
     */
    protected $table = 'shipping_tracking';

    /**
     * Campos preenchíveis
     */
    protected $fillable = [
        'order_id',
        'tracking_number',
        'carrier_name',
        'carrier_code',
        'service_type',
        'shipped_at',
        'delivered_at',
        'estimated_delivery',
        'status',
        'notes',
        'tracking_history',
        'origin_location',
        'destination_location',
        'weight',
        'length',
        'width',
        'height',
        'additional_info',
    ];

    /**
     * Campos que devem ser convertidos para tipos específicos
     */
    protected $casts = [
        'order_id' => 'integer',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
        'estimated_delivery' => 'datetime',
        'status' => 'string',
        'tracking_history' => 'array',
        'weight' => 'decimal:3',
        'length' => 'decimal:2',
        'width' => 'decimal:2',
        'height' => 'decimal:2',
        'additional_info' => 'array',
    ];

    /**
     * Campos que devem ser ocultos na serialização
     */
    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    /**
     * Status de rastreamento disponíveis
     */
    const STATUSES = [
        'pending' => 'Pendente',
        'preparing' => 'Preparando',
        'shipped' => 'Enviado',
        'in_transit' => 'Em Trânsito',
        'out_for_delivery' => 'Saiu para Entrega',
        'delivered' => 'Entregue',
        'failed_delivery' => 'Tentativa de Entrega',
        'returned' => 'Devolvido',
        'cancelled' => 'Cancelado',
    ];

    /**
     * Pedido relacionado
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    /**
     * Scope para rastreamentos por status
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope para rastreamentos enviados
     */
    public function scopeShipped($query)
    {
        return $query->whereNotNull('shipped_at');
    }

    /**
     * Scope para rastreamentos entregues
     */
    public function scopeDelivered($query)
    {
        return $query->whereNotNull('delivered_at');
    }

    /**
     * Scope para rastreamentos por transportadora
     */
    public function scopeByCarrier($query, string $carrier)
    {
        return $query->where('carrier_code', $carrier);
    }

    /**
     * Verifica se o rastreamento foi enviado
     */
    public function isShipped(): bool
    {
        return !is_null($this->shipped_at);
    }

    /**
     * Verifica se o rastreamento foi entregue
     */
    public function isDelivered(): bool
    {
        return !is_null($this->delivered_at);
    }

    /**
     * Verifica se está em trânsito
     */
    public function isInTransit(): bool
    {
        return $this->isShipped() && !$this->isDelivered();
    }

    /**
     * Marca como enviado
     */
    public function markAsShipped(string $trackingNumber = null, string $carrierName = null): void
    {
        $data = [
            'status' => 'shipped',
            'shipped_at' => now(),
        ];

        if ($trackingNumber) {
            $data['tracking_number'] = $trackingNumber;
        }

        if ($carrierName) {
            $data['carrier_name'] = $carrierName;
        }

        $this->update($data);
        $this->addTrackingEvent('shipped', 'Pedido enviado pela transportadora');
    }

    /**
     * Marca como entregue
     */
    public function markAsDelivered(): void
    {
        $this->update([
            'status' => 'delivered',
            'delivered_at' => now(),
        ]);

        $this->addTrackingEvent('delivered', 'Pedido entregue com sucesso');
    }

    /**
     * Atualiza o status
     */
    public function updateStatus(string $status, string $message = null): void
    {
        $this->update(['status' => $status]);

        if ($message) {
            $this->addTrackingEvent($status, $message);
        }
    }

    /**
     * Adiciona evento ao histórico de rastreamento
     */
    public function addTrackingEvent(string $status, string $message, array $data = []): void
    {
        $history = $this->tracking_history ?? [];

        $event = [
            'status' => $status,
            'message' => $message,
            'timestamp' => now()->toISOString(),
            'location' => $data['location'] ?? null,
            'data' => $data,
        ];

        array_push($history, $event);

        $this->update(['tracking_history' => $history]);
    }

    /**
     * Obtém o último evento de rastreamento
     */
    public function getLastTrackingEventAttribute(): ?array
    {
        $history = $this->tracking_history ?? [];
        return end($history) ?: null;
    }

    /**
     * Obtém o tempo em trânsito
     */
    public function getTransitTimeAttribute(): ?int
    {
        if (!$this->isShipped()) {
            return null;
        }

        $endDate = $this->isDelivered() ? $this->delivered_at : now();
        return $this->shipped_at->diffInDays($endDate);
    }

    /**
     * Verifica se está atrasado
     */
    public function isDelayed(): bool
    {
        if (!$this->estimated_delivery || !$this->isShipped()) {
            return false;
        }

        return now()->isAfter($this->estimated_delivery);
    }

    /**
     * Obtém informações para exibição
     */
    public function getDisplayInfoAttribute(): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'tracking_number' => $this->tracking_number,
            'carrier_name' => $this->carrier_name,
            'carrier_code' => $this->carrier_code,
            'service_type' => $this->service_type,
            'status' => $this->status,
            'status_label' => self::STATUSES[$this->status] ?? $this->status,
            'shipped_at' => $this->shipped_at,
            'delivered_at' => $this->delivered_at,
            'estimated_delivery' => $this->estimated_delivery,
            'notes' => $this->notes,
            'origin_location' => $this->origin_location,
            'destination_location' => $this->destination_location,
            'weight' => $this->weight,
            'dimensions' => [
                'length' => $this->length,
                'width' => $this->width,
                'height' => $this->height,
            ],
            'is_shipped' => $this->isShipped(),
            'is_delivered' => $this->isDelivered(),
            'is_in_transit' => $this->isInTransit(),
            'is_delayed' => $this->isDelayed(),
            'transit_time' => $this->transit_time,
            'last_tracking_event' => $this->last_tracking_event,
            'tracking_history' => $this->tracking_history,
            'formatted_shipped_at' => $this->shipped_at?->format('d/m/Y H:i'),
            'formatted_delivered_at' => $this->delivered_at?->format('d/m/Y H:i'),
            'formatted_estimated_delivery' => $this->estimated_delivery?->format('d/m/Y'),
            'formatted_created_at' => $this->created_at->format('d/m/Y H:i'),
        ];
    }

    /**
     * Obtém URL de rastreamento da transportadora
     */
    public function getTrackingUrlAttribute(): ?string
    {
        if (!$this->tracking_number || !$this->carrier_code) {
            return null;
        }

        $urls = [
            'correios' => 'https://rastreamento.correios.com.br/app/index.php',
            'jadlog' => 'https://www.jadlog.com.br/tracking',
            'loggi' => 'https://www.loggi.com/rastreio/',
            'azul' => 'https://www.azulcargo.com.br/rastreio',
            'tam' => 'https://www.tam.com.br/rastreio',
        ];

        $baseUrl = $urls[$this->carrier_code] ?? null;

        if (!$baseUrl) {
            return null;
        }

        return $baseUrl . '?tracking=' . $this->tracking_number;
    }

    /**
     * Atualiza informações de rastreamento
     */
    public function updateTrackingInfo(array $data): void
    {
        $updateData = [];

        if (isset($data['status'])) {
            $updateData['status'] = $data['status'];
        }

        if (isset($data['location'])) {
            $updateData['destination_location'] = $data['location'];
        }

        if (isset($data['message'])) {
            $this->addTrackingEvent($data['status'] ?? $this->status, $data['message'], $data);
        }

        if (!empty($updateData)) {
            $this->update($updateData);
        }
    }

    /**
     * Obtém estatísticas de rastreamento
     */
    public function getStatsAttribute(): array
    {
        return [
            'is_shipped' => $this->isShipped(),
            'is_delivered' => $this->isDelivered(),
            'is_in_transit' => $this->isInTransit(),
            'is_delayed' => $this->isDelayed(),
            'transit_time' => $this->transit_time,
            'tracking_events_count' => count($this->tracking_history ?? []),
            'has_tracking_number' => !empty($this->tracking_number),
            'has_carrier' => !empty($this->carrier_name),
        ];
    }
}