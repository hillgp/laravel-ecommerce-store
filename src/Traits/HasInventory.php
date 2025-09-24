<?php

namespace LaravelEcommerce\Store\Traits;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

trait HasInventory
{
    /**
     * Get inventory movements for the product.
     */
    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(\LaravelEcommerce\Store\Models\InventoryMovement::class);
    }

    /**
     * Get current stock level.
     */
    public function getCurrentStockAttribute(): int
    {
        return $this->inventoryMovements()
            ->where('type', '!=', 'adjustment')
            ->sum('quantity');
    }

    /**
     * Get available stock (considering reserved items).
     */
    public function getAvailableStockAttribute(): int
    {
        $reserved = $this->getReservedStock();
        return max(0, $this->current_stock - $reserved);
    }

    /**
     * Get reserved stock.
     */
    public function getReservedStock(): int
    {
        return $this->inventoryMovements()
            ->where('type', 'reservation')
            ->where('status', 'active')
            ->sum('quantity');
    }

    /**
     * Get stock by location.
     */
    public function getStockByLocation(int $locationId = null): Collection
    {
        $query = $this->inventoryMovements()
            ->selectRaw('location_id, SUM(quantity) as total_quantity')
            ->groupBy('location_id');

        if ($locationId) {
            $query->where('location_id', $locationId);
        }

        return $query->get();
    }

    /**
     * Check if product is in stock.
     */
    public function isInStock(int $quantity = 1): bool
    {
        return $this->available_stock >= $quantity;
    }

    /**
     * Check if product is low in stock.
     */
    public function isLowStock(int $threshold = null): bool
    {
        $threshold = $threshold ?? config('store.inventory.low_stock_threshold', 10);
        return $this->available_stock <= $threshold && $this->available_stock > 0;
    }

    /**
     * Check if product is out of stock.
     */
    public function isOutOfStock(): bool
    {
        return $this->available_stock <= 0;
    }

    /**
     * Add stock to inventory.
     */
    public function addStock(int $quantity, array $data = []): \LaravelEcommerce\Store\Models\InventoryMovement
    {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('Quantity must be greater than zero.');
        }

        $data = array_merge([
            'type' => 'stock_in',
            'quantity' => $quantity,
            'reason' => 'manual_addition',
            'status' => 'completed',
        ], $data);

        return $this->inventoryMovements()->create($data);
    }

    /**
     * Remove stock from inventory.
     */
    public function removeStock(int $quantity, array $data = []): \LaravelEcommerce\Store\Models\InventoryMovement
    {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('Quantity must be greater than zero.');
        }

        if (!$this->isInStock($quantity)) {
            throw new \OutOfBoundsException('Insufficient stock available.');
        }

        $data = array_merge([
            'type' => 'stock_out',
            'quantity' => $quantity,
            'reason' => 'manual_removal',
            'status' => 'completed',
        ], $data);

        return $this->inventoryMovements()->create($data);
    }

    /**
     * Reserve stock for an order.
     */
    public function reserveStock(int $quantity, int $orderId = null): \LaravelEcommerce\Store\Models\InventoryMovement
    {
        if ($quantity <= 0) {
            throw new \InvalidArgumentException('Quantity must be greater than zero.');
        }

        if (!$this->isInStock($quantity)) {
            throw new \OutOfBoundsException('Insufficient stock available for reservation.');
        }

        $data = [
            'type' => 'reservation',
            'quantity' => $quantity,
            'reason' => 'order_reservation',
            'status' => 'active',
            'reference_id' => $orderId,
        ];

        return $this->inventoryMovements()->create($data);
    }

    /**
     * Release reserved stock.
     */
    public function releaseStock(int $quantity = null, int $orderId = null): bool
    {
        $query = $this->inventoryMovements()
            ->where('type', 'reservation')
            ->where('status', 'active');

        if ($orderId) {
            $query->where('reference_id', $orderId);
        }

        if ($quantity) {
            // Partial release
            $movement = $query->first();
            if ($movement && $movement->quantity > $quantity) {
                $movement->update(['quantity' => $movement->quantity - $quantity]);
                return true;
            }
        }

        // Full release
        return $query->update(['status' => 'cancelled']) > 0;
    }

    /**
     * Adjust stock (manual correction).
     */
    public function adjustStock(int $newQuantity, string $reason = 'manual_adjustment'): \LaravelEcommerce\Store\Models\InventoryMovement
    {
        $currentStock = $this->current_stock;
        $adjustment = $newQuantity - $currentStock;

        if ($adjustment === 0) {
            throw new \InvalidArgumentException('No adjustment needed.');
        }

        $data = [
            'type' => 'adjustment',
            'quantity' => abs($adjustment),
            'reason' => $reason,
            'status' => 'completed',
            'notes' => "Adjusted from {$currentStock} to {$newQuantity}",
        ];

        return $this->inventoryMovements()->create($data);
    }

    /**
     * Get inventory history.
     */
    public function getInventoryHistory(array $filters = []): Collection
    {
        $query = $this->inventoryMovements();

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Get inventory summary.
     */
    public function getInventorySummary(): array
    {
        $movements = $this->inventoryMovements;

        $summary = [
            'current_stock' => $this->current_stock,
            'available_stock' => $this->available_stock,
            'reserved_stock' => $this->getReservedStock(),
            'total_movements' => $movements->count(),
            'stock_value' => $this->current_stock * ($this->cost_price ?? 0),
        ];

        // Stock by type
        $summary['stock_by_type'] = $movements->groupBy('type')->map->sum('quantity');

        // Stock by status
        $summary['stock_by_status'] = $movements->groupBy('status')->map->sum('quantity');

        return $summary;
    }

    /**
     * Get low stock products.
     */
    public static function getLowStockProducts(int $threshold = null): Collection
    {
        $threshold = $threshold ?? config('store.inventory.low_stock_threshold', 10);

        return static::whereRaw('(
            SELECT COALESCE(SUM(quantity), 0)
            FROM inventory_movements im
            WHERE im.product_id = products.id
            AND im.type != "adjustment"
        ) <= ?', [$threshold])
        ->whereRaw('(
            SELECT COALESCE(SUM(quantity), 0)
            FROM inventory_movements im
            WHERE im.product_id = products.id
            AND im.type != "adjustment"
        ) > 0')
        ->get();
    }

    /**
     * Get out of stock products.
     */
    public static function getOutOfStockProducts(): Collection
    {
        return static::whereRaw('(
            SELECT COALESCE(SUM(quantity), 0)
            FROM inventory_movements im
            WHERE im.product_id = products.id
            AND im.type != "adjustment"
        ) <= 0')->get();
    }

    /**
     * Get inventory alerts.
     */
    public function getInventoryAlerts(): array
    {
        $alerts = [];

        if ($this->isOutOfStock()) {
            $alerts[] = [
                'type' => 'danger',
                'message' => 'Produto sem estoque',
                'icon' => 'exclamation-triangle',
            ];
        } elseif ($this->isLowStock()) {
            $alerts[] = [
                'type' => 'warning',
                'message' => 'Estoque baixo',
                'icon' => 'exclamation-circle',
            ];
        }

        // Check for negative stock
        if ($this->current_stock < 0) {
            $alerts[] = [
                'type' => 'danger',
                'message' => 'Estoque negativo detectado',
                'icon' => 'times-circle',
            ];
        }

        return $alerts;
    }

    /**
     * Get stock status for display.
     */
    public function getStockStatusAttribute(): string
    {
        if ($this->isOutOfStock()) {
            return 'Sem estoque';
        } elseif ($this->isLowStock()) {
            return 'Estoque baixo';
        } else {
            return 'Em estoque';
        }
    }

    /**
     * Get stock status class for CSS.
     */
    public function getStockStatusClassAttribute(): string
    {
        if ($this->isOutOfStock()) {
            return 'danger';
        } elseif ($this->isLowStock()) {
            return 'warning';
        } else {
            return 'success';
        }
    }

    /**
     * Get formatted stock information.
     */
    public function getFormattedStockAttribute(): string
    {
        $available = $this->available_stock;
        $reserved = $this->getReservedStock();

        if ($available <= 0) {
            return 'Sem estoque';
        }

        $message = "{$available} disponível(s)";

        if ($reserved > 0) {
            $message .= " ({$reserved} reservado(s))";
        }

        return $message;
    }

    /**
     * Check if product can be purchased.
     */
    public function canBePurchased(int $quantity = 1): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->track_inventory && !$this->isInStock($quantity)) {
            return false;
        }

        return true;
    }

    /**
     * Get inventory valuation.
     */
    public function getInventoryValuation(): float
    {
        $costPrice = $this->cost_price ?? 0;
        return $this->current_stock * $costPrice;
    }

    /**
     * Get inventory turnover rate.
     */
    public function getInventoryTurnoverRate(string $period = '30 days'): float
    {
        $days = is_numeric($period) ? (int) $period : 30;
        $startDate = now()->subDays($days);

        $soldQuantity = $this->inventoryMovements()
            ->where('type', 'stock_out')
            ->where('created_at', '>=', $startDate)
            ->sum('quantity');

        if ($soldQuantity <= 0) {
            return 0;
        }

        $averageStock = $this->inventoryMovements()
            ->where('created_at', '>=', $startDate)
            ->selectRaw('AVG(quantity) as avg_stock')
            ->first()
            ->avg_stock ?? 0;

        return $averageStock > 0 ? $soldQuantity / $averageStock : 0;
    }

    /**
     * Boot the trait.
     */
    protected static function bootHasInventory(): void
    {
        static::saving(function ($model) {
            // Ensure stock is not negative for non-trackable items
            if (!$model->track_inventory && $model->current_stock < 0) {
                $model->current_stock = 0;
            }
        });

        static::deleting(function ($model) {
            // Delete all inventory movements when product is deleted
            $model->inventoryMovements()->delete();
        });
    }
}