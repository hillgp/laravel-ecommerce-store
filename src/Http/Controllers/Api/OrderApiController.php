<?php

namespace LaravelEcommerce\Store\Http\Controllers\Api;

use LaravelEcommerce\Store\Models\Order;
use LaravelEcommerce\Store\Models\Cart;
use LaravelEcommerce\Store\Services\CacheService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;

class OrderApiController extends \LaravelEcommerce\Store\Http\Controllers\Controller
{
    protected CacheService $cacheService;

    public function __construct(CacheService $cacheService)
    {
        $this->cacheService = $cacheService;
        $this->middleware('auth:sanctum');
    }

    /**
     * Listar pedidos do usuário.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'page' => 'integer|min:1',
                'per_page' => 'integer|min:1|max:100',
                'status' => 'string|in:pending,confirmed,shipped,delivered,cancelled',
                'date_from' => 'date',
                'date_to' => 'date',
                'includes' => 'string',
            ]);

            $user = $request->user();
            $query = $user->orders()->with(['items.product.images']);

            // Filtros
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            // Includes
            if ($request->filled('includes')) {
                $includes = explode(',', $request->includes);
                $query->with($includes);
            }

            $perPage = $request->get('per_page', 20);
            $orders = $query->orderBy('created_at', 'desc')->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $orders->items(),
                'pagination' => [
                    'current_page' => $orders->currentPage(),
                    'last_page' => $orders->lastPage(),
                    'per_page' => $orders->perPage(),
                    'total' => $orders->total(),
                    'from' => $orders->firstItem(),
                    'to' => $orders->lastItem(),
                ],
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Dados de validação inválidos',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar pedidos',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Exibir pedido específico.
     */
    public function show(Order $order): JsonResponse
    {
        try {
            $user = request()->user();

            // Verificar se o pedido pertence ao usuário
            if ($order->customer_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pedido não encontrado',
                ], 404);
            }

            $order->load([
                'items.product.images',
                'statusHistory',
                'billingAddress',
                'shippingAddress',
                'payments',
                'shipments'
            ]);

            return response()->json([
                'success' => true,
                'data' => $order,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Pedido não encontrado',
            ], 404);
        }
    }

    /**
     * Criar pedido a partir do carrinho.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'cart_session' => 'nullable|string',
                'billing_address' => 'required|array',
                'shipping_address' => 'nullable|array',
                'payment_method' => 'required|string',
                'shipping_method' => 'required|string',
                'notes' => 'nullable|string|max:1000',
            ]);

            $user = $request->user();

            // Buscar carrinho
            if ($user) {
                $cart = $user->cart;
            } else {
                $sessionId = $request->cart_session ?? $request->cookie('cart_session');
                if (!$sessionId) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Carrinho não encontrado',
                    ], 404);
                }
                $cart = Cart::where('session_id', $sessionId)->first();
            }

            if (!$cart || $cart->items->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Carrinho vazio',
                ], 422);
            }

            // Verificar estoque
            foreach ($cart->items as $item) {
                if ($item->quantity > $item->product->stock_quantity) {
                    return response()->json([
                        'success' => false,
                        'message' => "Produto {$item->product->name} sem estoque suficiente",
                    ], 422);
                }
            }

            return DB::transaction(function () use ($cart, $request, $user) {
                // Criar pedido
                $orderData = [
                    'customer_id' => $user->id ?? null,
                    'customer_name' => $user->name ?? $request->billing_address['name'],
                    'customer_email' => $user->email ?? $request->billing_address['email'],
                    'customer_phone' => $user->phone ?? $request->billing_address['phone'],
                    'status' => 'pending',
                    'payment_status' => 'pending',
                    'shipping_status' => 'pending',
                    'subtotal' => $cart->items->sum(fn($item) => $item->price * $item->quantity),
                    'discount_amount' => $cart->discount_amount ?? 0,
                    'tax_amount' => ($cart->items->sum(fn($item) => $item->price * $item->quantity) - ($cart->discount_amount ?? 0)) * 0.1,
                    'shipping_amount' => $this->calculateShipping($cart),
                    'total' => $cart->items->sum(fn($item) => $item->price * $item->quantity) - ($cart->discount_amount ?? 0) + $this->calculateShipping($cart),
                    'currency' => 'BRL',
                    'payment_method' => $request->payment_method,
                    'shipping_method' => $request->shipping_method,
                    'notes' => $request->notes,
                    'order_number' => 'ORD-' . date('Ymd') . '-' . str_pad(Order::count() + 1, 6, '0', STR_PAD_LEFT),
                ];

                $order = Order::create($orderData);

                // Criar itens do pedido
                foreach ($cart->items as $item) {
                    $order->items()->create([
                        'product_id' => $item->product_id,
                        'quantity' => $item->quantity,
                        'price' => $item->price,
                        'options' => $item->options,
                    ]);

                    // Reduzir estoque
                    $item->product->decrement('stock_quantity', $item->quantity);
                }

                // Criar endereço de cobrança
                $billingData = $request->billing_address;
                $billingData['type'] = 'billing';
                $billingData['order_id'] = $order->id;
                $order->billingAddress()->create($billingData);

                // Criar endereço de entrega se fornecido
                if ($request->shipping_address) {
                    $shippingData = $request->shipping_address;
                    $shippingData['type'] = 'shipping';
                    $shippingData['order_id'] = $order->id;
                    $order->shippingAddress()->create($shippingData);
                }

                // Registrar status inicial
                $order->statusHistory()->create([
                    'old_status' => null,
                    'new_status' => 'pending',
                    'comment' => 'Pedido criado',
                    'user_id' => $user->id ?? null,
                ]);

                // Limpar carrinho
                $cart->items()->delete();
                $cart->update(['coupon_id' => null, 'discount_amount' => 0]);

                // Invalidar cache
                $this->cacheService->invalidateCartCache($cart->id);
                $this->cacheService->invalidateOrderCache($order->id);

                $order->load(['items.product.images', 'billingAddress', 'shippingAddress']);

                return response()->json([
                    'success' => true,
                    'message' => 'Pedido criado com sucesso',
                    'data' => $order,
                ], 201);
            });

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Dados de validação inválidos',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao criar pedido',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cancelar pedido.
     */
    public function cancel(Order $order): JsonResponse
    {
        try {
            $user = request()->user();

            // Verificar se o pedido pertence ao usuário
            if ($order->customer_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pedido não encontrado',
                ], 404);
            }

            // Verificar se pode ser cancelado
            if (!in_array($order->status, ['pending', 'confirmed'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Este pedido não pode ser cancelado',
                ], 422);
            }

            $order->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
            ]);

            // Registrar no histórico
            $order->statusHistory()->create([
                'old_status' => $order->status,
                'new_status' => 'cancelled',
                'comment' => 'Pedido cancelado pelo cliente',
                'user_id' => $user->id,
            ]);

            // Repor estoque
            foreach ($order->items as $item) {
                $item->product->increment('stock_quantity', $item->quantity);
            }

            // Invalidar cache
            $this->cacheService->invalidateOrderCache($order->id);

            return response()->json([
                'success' => true,
                'message' => 'Pedido cancelado com sucesso',
                'data' => $order->fresh(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao cancelar pedido',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obter histórico de status do pedido.
     */
    public function statusHistory(Order $order): JsonResponse
    {
        try {
            $user = request()->user();

            // Verificar se o pedido pertence ao usuário
            if ($order->customer_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pedido não encontrado',
                ], 404);
            }

            $statusHistory = $order->statusHistory()
                ->with('user')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $statusHistory,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar histórico',
            ], 500);
        }
    }

    /**
     * Rastrear pedido.
     */
    public function track(Order $order): JsonResponse
    {
        try {
            $user = request()->user();

            // Verificar se o pedido pertence ao usuário
            if ($order->customer_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pedido não encontrado',
                ], 404);
            }

            $tracking = [
                'order_number' => $order->order_number,
                'status' => $order->status,
                'payment_status' => $order->payment_status,
                'shipping_status' => $order->shipping_status,
                'created_at' => $order->created_at,
                'estimated_delivery' => $this->calculateEstimatedDelivery($order),
                'shipping_address' => $order->shippingAddress,
                'tracking_number' => $order->tracking_number,
                'shipping_carrier' => $order->shipping_carrier,
                'shipments' => $order->shipments,
            ];

            return response()->json([
                'success' => true,
                'data' => $tracking,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao rastrear pedido',
            ], 500);
        }
    }

    /**
     * Calcular frete.
     */
    protected function calculateShipping(Cart $cart): float
    {
        $subtotal = $cart->items->sum(fn($item) => $item->price * $item->quantity) - ($cart->discount_amount ?? 0);

        if ($subtotal >= 200) {
            return 0; // Frete grátis
        }

        return 15.00; // Frete padrão
    }

    /**
     * Calcular data estimada de entrega.
     */
    protected function calculateEstimatedDelivery(Order $order): ?string
    {
        if ($order->status === 'delivered') {
            return $order->delivered_date?->format('Y-m-d');
        }

        if ($order->status === 'shipped') {
            return now()->addDays(2)->format('Y-m-d');
        }

        if ($order->status === 'confirmed') {
            return now()->addDays(5)->format('Y-m-d');
        }

        return null;
    }
}