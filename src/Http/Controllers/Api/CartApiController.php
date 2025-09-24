<?php

namespace LaravelEcommerce\Store\Http\Controllers\Api;

use LaravelEcommerce\Store\Models\Cart;
use LaravelEcommerce\Store\Models\CartItem;
use LaravelEcommerce\Store\Models\Product;
use LaravelEcommerce\Store\Services\CacheService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;

class CartApiController extends \LaravelEcommerce\Store\Http\Controllers\Controller
{
    protected CacheService $cacheService;

    public function __construct(CacheService $cacheService)
    {
        $this->cacheService = $cacheService;
        $this->middleware('auth:sanctum')->except(['store', 'show', 'update', 'destroy']);
    }

    /**
     * Obter carrinho do usuário.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if ($user) {
                // Usuário autenticado
                $cart = $user->cart ?? $user->cart()->create();
            } else {
                // Usuário convidado - usar session ID
                $sessionId = $request->cookie('cart_session') ?? Str::uuid()->toString();
                $cart = Cart::firstOrCreate(['session_id' => $sessionId]);

                // Atualizar cookie se não existir
                if (!$request->cookie('cart_session')) {
                    return response()->json([
                        'success' => true,
                        'data' => $cart->load('items.product.images'),
                        'session_id' => $sessionId,
                    ])->cookie('cart_session', $sessionId, 4320); // 3 dias
                }
            }

            $cart->load('items.product.images');

            return response()->json([
                'success' => true,
                'data' => $cart,
                'summary' => $this->calculateCartSummary($cart),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar carrinho',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Adicionar item ao carrinho.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'product_id' => 'required|exists:products,id',
                'quantity' => 'required|integer|min:1',
                'options' => 'nullable|array',
            ]);

            $product = Product::findOrFail($request->product_id);

            // Verificar se produto está ativo e em estoque
            if (!$product->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Produto não disponível',
                ], 422);
            }

            if ($product->stock_quantity <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Produto sem estoque',
                ], 422);
            }

            $user = $request->user();

            if ($user) {
                $cart = $user->cart ?? $user->cart()->create();
            } else {
                $sessionId = $request->cookie('cart_session') ?? Str::uuid()->toString();
                $cart = Cart::firstOrCreate(['session_id' => $sessionId]);
            }

            // Verificar se item já existe no carrinho
            $cartItem = $cart->items()->where('product_id', $request->product_id)->first();

            if ($cartItem) {
                // Atualizar quantidade
                $newQuantity = $cartItem->quantity + $request->quantity;

                if ($newQuantity > $product->stock_quantity) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Quantidade solicitada excede o estoque disponível',
                    ], 422);
                }

                $cartItem->update([
                    'quantity' => $newQuantity,
                    'options' => $request->options ?? $cartItem->options,
                ]);
            } else {
                // Criar novo item
                if ($request->quantity > $product->stock_quantity) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Quantidade solicitada excede o estoque disponível',
                    ], 422);
                }

                $cart->items()->create([
                    'product_id' => $request->product_id,
                    'quantity' => $request->quantity,
                    'price' => $product->price,
                    'options' => $request->options,
                ]);
            }

            // Invalidar cache do carrinho
            $this->cacheService->invalidateCartCache($cart->id);

            $cart->load('items.product.images');

            $response = response()->json([
                'success' => true,
                'message' => 'Item adicionado ao carrinho',
                'data' => $cart,
                'summary' => $this->calculateCartSummary($cart),
            ]);

            // Adicionar cookie se usuário convidado
            if (!$user && !$request->cookie('cart_session')) {
                $response->cookie('cart_session', $cart->session_id, 4320);
            }

            return $response;

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Dados de validação inválidos',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao adicionar item ao carrinho',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Atualizar item do carrinho.
     */
    public function update(Request $request, CartItem $cartItem): JsonResponse
    {
        try {
            $request->validate([
                'quantity' => 'required|integer|min:1',
                'options' => 'nullable|array',
            ]);

            $product = $cartItem->product;

            if ($request->quantity > $product->stock_quantity) {
                return response()->json([
                    'success' => false,
                    'message' => 'Quantidade solicitada excede o estoque disponível',
                ], 422);
            }

            $cartItem->update([
                'quantity' => $request->quantity,
                'options' => $request->options ?? $cartItem->options,
            ]);

            // Invalidar cache
            $this->cacheService->invalidateCartCache($cartItem->cart_id);

            $cart = $cartItem->cart->load('items.product.images');

            return response()->json([
                'success' => true,
                'message' => 'Item atualizado com sucesso',
                'data' => $cart,
                'summary' => $this->calculateCartSummary($cart),
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
                'message' => 'Erro ao atualizar item',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remover item do carrinho.
     */
    public function destroy(CartItem $cartItem): JsonResponse
    {
        try {
            $cartId = $cartItem->cart_id;
            $cartItem->delete();

            // Invalidar cache
            $this->cacheService->invalidateCartCache($cartId);

            $cart = \LaravelEcommerce\Store\Models\Cart::with('items.product.images')->find($cartId);

            return response()->json([
                'success' => true,
                'message' => 'Item removido do carrinho',
                'data' => $cart,
                'summary' => $cart ? $this->calculateCartSummary($cart) : null,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao remover item',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Limpar carrinho.
     */
    public function clear(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if ($user) {
                $cart = $user->cart;
            } else {
                $sessionId = $request->cookie('cart_session');
                $cart = $sessionId ? Cart::where('session_id', $sessionId)->first() : null;
            }

            if ($cart) {
                $cart->items()->delete();
                $this->cacheService->invalidateCartCache($cart->id);
            }

            return response()->json([
                'success' => true,
                'message' => 'Carrinho limpo com sucesso',
                'data' => $cart ? $cart->load('items.product.images') : null,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao limpar carrinho',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Aplicar cupom de desconto.
     */
    public function applyCoupon(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'coupon_code' => 'required|string|max:50',
            ]);

            $user = $request->user();

            if ($user) {
                $cart = $user->cart ?? $user->cart()->create();
            } else {
                $sessionId = $request->cookie('cart_session');
                if (!$sessionId) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Carrinho não encontrado',
                    ], 404);
                }
                $cart = Cart::where('session_id', $sessionId)->first();
            }

            if (!$cart) {
                return response()->json([
                    'success' => false,
                    'message' => 'Carrinho não encontrado',
                ], 404);
            }

            // Buscar cupom
            $coupon = \LaravelEcommerce\Store\Models\Coupon::where('code', $request->coupon_code)
                ->where('is_active', true)
                ->where('valid_from', '<=', now())
                ->where(function ($query) {
                    $query->where('valid_until', '>=', now())
                          ->orWhereNull('valid_until');
                })
                ->first();

            if (!$coupon) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cupom inválido ou expirado',
                ], 422);
            }

            // Verificar se cupom já foi usado
            if ($coupon->usage_limit && $coupon->usage_count >= $coupon->usage_limit) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cupom atingiu o limite de uso',
                ], 422);
            }

            // Verificar se usuário já usou o cupom
            if ($user && $coupon->per_customer_limit) {
                $usageCount = $user->coupons()->where('coupon_id', $coupon->id)->count();
                if ($usageCount >= $coupon->per_customer_limit) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Você já usou este cupom o máximo de vezes permitido',
                    ], 422);
                }
            }

            // Calcular desconto
            $subtotal = $cart->items->sum(function ($item) {
                return $item->price * $item->quantity;
            });

            $discount = $this->calculateCouponDiscount($coupon, $subtotal, $cart->items);

            $cart->update([
                'coupon_id' => $coupon->id,
                'discount_amount' => $discount,
            ]);

            // Registrar uso do cupom
            if ($user) {
                $user->coupons()->attach($coupon->id, ['used_at' => now()]);
            }
            $coupon->increment('usage_count');

            $cart->load('items.product.images', 'coupon');

            return response()->json([
                'success' => true,
                'message' => 'Cupom aplicado com sucesso',
                'data' => $cart,
                'summary' => $this->calculateCartSummary($cart),
                'discount' => $discount,
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
                'message' => 'Erro ao aplicar cupom',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remover cupom do carrinho.
     */
    public function removeCoupon(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if ($user) {
                $cart = $user->cart;
            } else {
                $sessionId = $request->cookie('cart_session');
                $cart = $sessionId ? Cart::where('session_id', $sessionId)->first() : null;
            }

            if (!$cart) {
                return response()->json([
                    'success' => false,
                    'message' => 'Carrinho não encontrado',
                ], 404);
            }

            $cart->update([
                'coupon_id' => null,
                'discount_amount' => 0,
            ]);

            $cart->load('items.product.images');

            return response()->json([
                'success' => true,
                'message' => 'Cupom removido com sucesso',
                'data' => $cart,
                'summary' => $this->calculateCartSummary($cart),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao remover cupom',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Calcular resumo do carrinho.
     */
    protected function calculateCartSummary(Cart $cart): array
    {
        $items = $cart->items;
        $subtotal = $items->sum(function ($item) {
            return $item->price * $item->quantity;
        });

        $discount = $cart->discount_amount ?? 0;
        $tax = ($subtotal - $discount) * 0.1; // 10% de imposto (simplificado)
        $shipping = $this->calculateShipping($subtotal - $discount);
        $total = $subtotal - $discount + $tax + $shipping;

        return [
            'items_count' => $items->count(),
            'total_quantity' => $items->sum('quantity'),
            'subtotal' => $subtotal,
            'discount' => $discount,
            'tax' => $tax,
            'shipping' => $shipping,
            'total' => $total,
        ];
    }

    /**
     * Calcular desconto do cupom.
     */
    protected function calculateCouponDiscount($coupon, float $subtotal, $items): float
    {
        switch ($coupon->type) {
            case 'percentage':
                return ($subtotal * $coupon->value) / 100;
            case 'fixed':
                return min($coupon->value, $subtotal);
            case 'shipping':
                return 0; // Desconto no frete será calculado depois
            default:
                return 0;
        }
    }

    /**
     * Calcular frete.
     */
    protected function calculateShipping(float $subtotal): float
    {
        // Lógica simplificada de frete
        if ($subtotal >= 200) {
            return 0; // Frete grátis
        }

        return 15.00; // Frete padrão
    }
}