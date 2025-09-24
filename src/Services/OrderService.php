<?php

namespace LaravelEcommerce\Store\Services;

use LaravelEcommerce\Store\Models\Order;
use LaravelEcommerce\Store\Models\OrderItem;
use LaravelEcommerce\Store\Models\Cart;
use LaravelEcommerce\Store\Models\Customer;
use LaravelEcommerce\Store\Models\Product;
use LaravelEcommerce\Store\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderService
{
    /**
     * Cria um pedido a partir do carrinho
     */
    public function createOrderFromCart(Cart $cart, array $data): Order
    {
        return DB::transaction(function () use ($cart, $data) {
            // Valida o carrinho
            $errors = $this->validateCartForOrder($cart);
            if (!empty($errors)) {
                throw new \Exception('Carrinho inválido: ' . implode(', ', $errors));
            }

            // Cria o pedido
            $order = Order::create([
                'customer_id' => $cart->customer_id,
                'status' => 'pending',
                'payment_status' => 'pending',
                'shipping_status' => 'not_shipped',
                'subtotal' => $cart->subtotal,
                'discount_amount' => $cart->discount_amount,
                'shipping_cost' => $cart->shipping_cost ?? 0,
                'tax_amount' => $cart->tax_amount ?? 0,
                'total' => $cart->total,
                'currency' => 'BRL',
                'coupon_code' => $cart->coupon_code,
                'billing_address_id' => $data['billing_address_id'] ?? null,
                'shipping_address_id' => $data['shipping_address_id'] ?? null,
                'notes' => $data['notes'] ?? null,
                'meta_data' => [
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'created_from' => 'cart',
                    'cart_id' => $cart->id,
                ],
            ]);

            // Cria os itens do pedido
            foreach ($cart->items as $cartItem) {
                $this->createOrderItemFromCartItem($order, $cartItem);
            }

            // Reserva o estoque
            $this->reserveInventory($order);

            // Limpa o carrinho
            $cart->clear();

            // Registra a criação no histórico
            $order->statusHistory()->create([
                'status' => 'pending',
                'notes' => 'Pedido criado a partir do carrinho',
                'meta_data' => [
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ],
            ]);

            Log::info('Pedido criado', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'customer_id' => $order->customer_id,
                'total' => $order->total,
            ]);

            return $order;
        });
    }

    /**
     * Cria um item do pedido a partir de um item do carrinho
     */
    protected function createOrderItemFromCartItem(Order $order, $cartItem): OrderItem
    {
        return $order->items()->create([
            'product_id' => $cartItem->product_id,
            'product_variant_id' => $cartItem->product_variant_id,
            'product_name' => $cartItem->product_name,
            'product_sku' => $cartItem->product_sku,
            'quantity' => $cartItem->quantity,
            'price' => $cartItem->price,
            'total' => $cartItem->total,
            'options' => $cartItem->options,
            'attributes' => $cartItem->attributes,
            'meta_data' => $cartItem->meta_data,
        ]);
    }

    /**
     * Valida o carrinho para criação de pedido
     */
    protected function validateCartForOrder(Cart $cart): array
    {
        $errors = [];

        if ($cart->isEmpty()) {
            $errors[] = 'Carrinho vazio';
        }

        foreach ($cart->items as $item) {
            if (!$item->canBePurchased()) {
                $errors[] = "Produto '{$item->product_name}' não disponível para compra";
            }

            if (!$item->canChangeQuantity($item->quantity)) {
                $errors[] = "Quantidade indisponível para '{$item->product_name}'";
            }
        }

        return $errors;
    }

    /**
     * Reserva o estoque dos produtos do pedido
     */
    protected function reserveInventory(Order $order): void
    {
        foreach ($order->items as $item) {
            if ($item->product->track_stock) {
                // Reduz o estoque do produto
                $item->product->decrement('stock_quantity', $item->quantity);

                // Registra a movimentação de estoque
                $item->product->inventoryMovements()->create([
                    'type' => 'sale',
                    'quantity' => -$item->quantity,
                    'reference' => 'Pedido #' . $order->order_number,
                    'notes' => 'Reserva de estoque para pedido',
                ]);
            }

            if ($item->variant && $item->variant->track_stock) {
                // Reduz o estoque da variação
                $item->variant->decrement('stock_quantity', $item->quantity);

                // Registra a movimentação de estoque da variação
                $item->variant->inventoryMovements()->create([
                    'type' => 'sale',
                    'quantity' => -$item->quantity,
                    'reference' => 'Pedido #' . $order->order_number,
                    'notes' => 'Reserva de estoque para pedido',
                ]);
            }
        }
    }

    /**
     * Confirma um pedido
     */
    public function confirmOrder(Order $order, int $userId = null): void
    {
        if (!$order->canBeShipped()) {
            throw new \Exception('Pedido não pode ser confirmado');
        }

        $order->updateStatus('confirmed', 'Pedido confirmado', $userId);

        Log::info('Pedido confirmado', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'user_id' => $userId,
        ]);
    }

    /**
     * Processa um pedido
     */
    public function processOrder(Order $order, int $userId = null): void
    {
        if ($order->status !== 'confirmed') {
            throw new \Exception('Pedido deve estar confirmado para ser processado');
        }

        $order->updateStatus('processing', 'Pedido em processamento', $userId);

        Log::info('Pedido em processamento', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'user_id' => $userId,
        ]);
    }

    /**
     * Envia um pedido
     */
    public function shipOrder(Order $order, string $trackingNumber = null, string $trackingUrl = null, int $userId = null): void
    {
        if (!$order->canBeShipped()) {
            throw new \Exception('Pedido não pode ser enviado');
        }

        $order->updateStatus('shipped', 'Pedido enviado', $userId);
        $order->updateShippingStatus('shipped', $trackingNumber, $trackingUrl);

        Log::info('Pedido enviado', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'tracking_number' => $trackingNumber,
            'user_id' => $userId,
        ]);
    }

    /**
     * Marca um pedido como entregue
     */
    public function deliverOrder(Order $order, int $userId = null): void
    {
        if (!$order->isShipped()) {
            throw new \Exception('Pedido deve estar enviado para ser entregue');
        }

        $order->updateStatus('delivered', 'Pedido entregue', $userId);
        $order->updateShippingStatus('delivered');

        Log::info('Pedido entregue', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'user_id' => $userId,
        ]);
    }

    /**
     * Cancela um pedido
     */
    public function cancelOrder(Order $order, string $reason = null, int $userId = null): void
    {
        if (!$order->canBeCancelled()) {
            throw new \Exception('Pedido não pode ser cancelado');
        }

        DB::transaction(function () use ($order, $reason, $userId) {
            // Restaura o estoque
            $this->restoreInventory($order);

            // Atualiza o status
            $order->updateStatus('cancelled', $reason, $userId);

            Log::info('Pedido cancelado', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'reason' => $reason,
                'user_id' => $userId,
            ]);
        });
    }

    /**
     * Restaura o estoque dos produtos do pedido
     */
    protected function restoreInventory(Order $order): void
    {
        foreach ($order->items as $item) {
            if ($item->product->track_stock) {
                $item->product->increment('stock_quantity', $item->quantity);

                $item->product->inventoryMovements()->create([
                    'type' => 'cancellation',
                    'quantity' => $item->quantity,
                    'reference' => 'Cancelamento do Pedido #' . $order->order_number,
                    'notes' => 'Restauração de estoque por cancelamento',
                ]);
            }

            if ($item->variant && $item->variant->track_stock) {
                $item->variant->increment('stock_quantity', $item->quantity);

                $item->variant->inventoryMovements()->create([
                    'type' => 'cancellation',
                    'quantity' => $item->quantity,
                    'reference' => 'Cancelamento do Pedido #' . $order->order_number,
                    'notes' => 'Restauração de estoque por cancelamento',
                ]);
            }
        }
    }

    /**
     * Atualiza o status de pagamento do pedido
     */
    public function updatePaymentStatus(Order $order, string $status, string $transactionId = null, array $paymentData = null): void
    {
        $order->updatePaymentStatus($status, $transactionId, $paymentData);

        // Se o pagamento foi aprovado, confirma o pedido automaticamente
        if ($status === 'paid' && $order->status === 'pending') {
            $this->confirmOrder($order);
        }

        Log::info('Status de pagamento atualizado', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'payment_status' => $status,
            'transaction_id' => $transactionId,
        ]);
    }

    /**
     * Atualiza o status de envio do pedido
     */
    public function updateShippingStatus(Order $order, string $status, string $trackingNumber = null, string $trackingUrl = null): void
    {
        $order->updateShippingStatus($status, $trackingNumber, $trackingUrl);

        Log::info('Status de envio atualizado', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'shipping_status' => $status,
            'tracking_number' => $trackingNumber,
        ]);
    }

    /**
     * Obtém pedidos por status
     */
    public function getOrdersByStatus(string $status, int $limit = 50)
    {
        return Order::byStatus($status)
            ->with(['customer', 'items.product'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Obtém pedidos do cliente
     */
    public function getCustomerOrders(Customer $customer, int $limit = 20)
    {
        return $customer->orders()
            ->with(['items.product', 'statusHistory'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Obtém pedidos recentes
     */
    public function getRecentOrders(int $days = 30, int $limit = 50)
    {
        return Order::recent($days)
            ->with(['customer', 'items.product'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Obtém estatísticas de pedidos
     */
    public function getOrderStats(int $days = 30): array
    {
        $query = Order::recent($days);

        return [
            'total_orders' => $query->count(),
            'total_revenue' => $query->sum('total'),
            'average_order_value' => $query->avg('total'),
            'orders_by_status' => $query->selectRaw('status, COUNT(*) as count')->groupBy('status')->pluck('count', 'status'),
            'orders_by_payment_status' => $query->selectRaw('payment_status, COUNT(*) as count')->groupBy('payment_status')->pluck('count', 'payment_status'),
            'orders_by_shipping_status' => $query->selectRaw('shipping_status, COUNT(*) as count')->groupBy('shipping_status')->pluck('count', 'shipping_status'),
            'daily_orders' => $query->selectRaw('DATE(created_at) as date, COUNT(*) as count, SUM(total) as revenue')
                ->groupBy('date')
                ->orderBy('date', 'desc')
                ->limit(30)
                ->get(),
        ];
    }

    /**
     * Busca pedidos
     */
    public function searchOrders(string $search, int $limit = 50)
    {
        return Order::where('order_number', 'like', "%{$search}%")
            ->orWhereHas('customer', function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
            })
            ->with(['customer', 'items.product'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Obtém pedidos pendentes de processamento
     */
    public function getPendingOrders(int $limit = 20)
    {
        return Order::whereIn('status', ['confirmed', 'processing'])
            ->with(['customer', 'items.product'])
            ->orderBy('created_at', 'asc')
            ->limit($limit)
            ->get();
    }

    /**
     * Obtém pedidos com pagamento pendente
     */
    public function getPendingPaymentOrders(int $limit = 20)
    {
        return Order::where('payment_status', 'pending')
            ->with(['customer', 'items.product'])
            ->orderBy('created_at', 'asc')
            ->limit($limit)
            ->get();
    }

    /**
     * Obtém pedidos com envio pendente
     */
    public function getPendingShippingOrders(int $limit = 20)
    {
        return Order::where('shipping_status', 'not_shipped')
            ->where('status', '!=', 'cancelled')
            ->with(['customer', 'items.product'])
            ->orderBy('created_at', 'asc')
            ->limit($limit)
            ->get();
    }

    /**
     * Calcula métricas de performance
     */
    public function getPerformanceMetrics(int $days = 30): array
    {
        $orders = Order::recent($days)->get();

        $processingTimes = $orders->whereNotNull('processing_time')->pluck('processing_time');
        $deliveryTimes = $orders->whereNotNull('delivery_time')->pluck('delivery_time');

        return [
            'average_processing_time' => $processingTimes->avg(),
            'average_delivery_time' => $deliveryTimes->avg(),
            'on_time_delivery_rate' => $orders->where('delivery_time', '<=', 48)->count() / max($orders->count(), 1) * 100,
            'cancellation_rate' => $orders->where('status', 'cancelled')->count() / max($orders->count(), 1) * 100,
            'return_rate' => $orders->where('status', 'returned')->count() / max($orders->count(), 1) * 100,
        ];
    }

    /**
     * Gera relatório de vendas
     */
    public function generateSalesReport(array $filters = []): array
    {
        $query = Order::query();

        if (isset($filters['start_date'])) {
            $query->where('created_at', '>=', $filters['start_date']);
        }

        if (isset($filters['end_date'])) {
            $query->where('created_at', '<=', $filters['end_date']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $orders = $query->with(['items.product', 'customer'])->get();

        return [
            'summary' => [
                'total_orders' => $orders->count(),
                'total_revenue' => $orders->sum('total'),
                'total_items' => $orders->sum(fn($order) => $order->items->sum('quantity')),
                'average_order_value' => $orders->avg('total'),
            ],
            'by_status' => $orders->groupBy('status')->map->count(),
            'by_payment_status' => $orders->groupBy('payment_status')->map->count(),
            'top_products' => $orders->flatMap->items
                ->groupBy('product_id')
                ->sortByDesc(fn($items) => $items->sum('total'))
                ->take(10)
                ->map(function ($items) {
                    $product = $items->first()->product;
                    return [
                        'product' => $product,
                        'quantity_sold' => $items->sum('quantity'),
                        'revenue' => $items->sum('total'),
                    ];
                }),
            'daily_sales' => $orders->groupBy(fn($order) => $order->created_at->format('Y-m-d'))
                ->map(function ($dayOrders) {
                    return [
                        'orders' => $dayOrders->count(),
                        'revenue' => $dayOrders->sum('total'),
                        'items' => $dayOrders->sum(fn($order) => $order->items->sum('quantity')),
                    ];
                })
                ->sortKeysDesc(),
        ];
    }
}