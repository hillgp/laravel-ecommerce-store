<?php

namespace LaravelEcommerce\Store\Http\Controllers;

use LaravelEcommerce\Store\Services\CartService;
use LaravelEcommerce\Store\Services\CouponService;
use LaravelEcommerce\Store\Services\ShippingService;
use LaravelEcommerce\Store\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class CartController extends Controller
{
    protected CartService $cartService;
    protected CouponService $couponService;
    protected ShippingService $shippingService;

    public function __construct(
        CartService $cartService,
        CouponService $couponService,
        ShippingService $shippingService
    ) {
        $this->cartService = $cartService;
        $this->couponService = $couponService;
        $this->shippingService = $shippingService;
    }

    /**
     * Display cart contents.
     */
    public function index(): View
    {
        $cartItems = $this->cartService->getItems();
        $cartSummary = $this->cartService->getSummary();

        return view('store::cart.index', compact('cartItems', 'cartSummary'));
    }

    /**
     * Add item to cart.
     */
    public function add(Request $request, Product $product): JsonResponse|RedirectResponse
    {
        $quantity = $request->get('quantity', 1);
        $options = $request->get('options', []);

        // Validate product can be added to cart
        $errors = $this->cartService->validateItem($product, $quantity);

        if (!empty($errors)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Não foi possível adicionar o produto ao carrinho',
                    'errors' => $errors,
                ], 400);
            }

            return redirect()->back()->withErrors($errors)->withInput();
        }

        try {
            $cartItem = $this->cartService->addItem($product, $quantity, $options);
            $cartSummary = $this->cartService->getSummary();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Produto adicionado ao carrinho',
                    'cart' => $cartSummary,
                    'item' => $cartItem,
                ]);
            }

            return redirect()->route('store.cart.index')
                ->with('success', 'Produto adicionado ao carrinho');
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erro ao adicionar produto ao carrinho',
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Erro ao adicionar produto ao carrinho')
                ->withInput();
        }
    }

    /**
     * Update cart item quantity.
     */
    public function update(Request $request): JsonResponse|RedirectResponse
    {
        $cartItemId = $request->get('item_id');
        $quantity = $request->get('quantity');

        $cartItem = $this->cartService->getCart()->items()->find($cartItemId);

        if (!$cartItem) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Item não encontrado no carrinho',
                ], 404);
            }

            return redirect()->back()->with('error', 'Item não encontrado no carrinho');
        }

        try {
            $updatedItem = $this->cartService->updateItemQuantity($cartItem, $quantity);
            $cartSummary = $this->cartService->getSummary();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Quantidade atualizada',
                    'cart' => $cartSummary,
                    'item' => $updatedItem,
                ]);
            }

            return redirect()->route('store.cart.index')
                ->with('success', 'Carrinho atualizado');
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erro ao atualizar item do carrinho',
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Erro ao atualizar item do carrinho');
        }
    }

    /**
     * Remove item from cart.
     */
    public function remove(Request $request): JsonResponse|RedirectResponse
    {
        $cartItemId = $request->get('item_id');

        $cartItem = $this->cartService->getCart()->items()->find($cartItemId);

        if (!$cartItem) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Item não encontrado no carrinho',
                ], 404);
            }

            return redirect()->back()->with('error', 'Item não encontrado no carrinho');
        }

        try {
            $this->cartService->removeItem($cartItem);
            $cartSummary = $this->cartService->getSummary();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Item removido do carrinho',
                    'cart' => $cartSummary,
                ]);
            }

            return redirect()->route('store.cart.index')
                ->with('success', 'Item removido do carrinho');
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erro ao remover item do carrinho',
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Erro ao remover item do carrinho');
        }
    }

    /**
     * Clear cart.
     */
    public function clear(): JsonResponse|RedirectResponse
    {
        try {
            $this->cartService->clearCart();
            $cartSummary = $this->cartService->getSummary();

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Carrinho esvaziado',
                    'cart' => $cartSummary,
                ]);
            }

            return redirect()->route('store.cart.index')
                ->with('success', 'Carrinho esvaziado');
        } catch (\Exception $e) {
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erro ao esvaziar carrinho',
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'Erro ao esvaziar carrinho');
        }
    }

    /**
     * Apply coupon to cart.
     */
    public function applyCoupon(Request $request): JsonResponse|RedirectResponse
    {
        $couponCode = $request->get('coupon_code');
        $subtotal = $this->cartService->getSubtotal();

        if (empty($couponCode)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Código do cupom é obrigatório',
                ], 400);
            }

            return redirect()->back()->with('error', 'Código do cupom é obrigatório');
        }

        try {
            $result = $this->couponService->applyCoupon($couponCode, $subtotal);

            if ($result['success']) {
                // Store coupon in session
                session(['applied_coupon' => [
                    'code' => $couponCode,
                    'discount' => $result['discount'],
                ]]);

                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => true,
                        'message' => $result['message'],
                        'cart' => $this->cartService->getSummary(),
                    ]);
                }

                return redirect()->back()->with('success', $result['message']);
            } else {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => $result['message'],
                    ], 400);
                }

                return redirect()->back()->with('error', $result['message']);
            }
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erro ao aplicar cupom',
                ], 500);
            }

            return redirect()->back()->with('error', 'Erro ao aplicar cupom');
        }
    }

    /**
     * Remove coupon from cart.
     */
    public function removeCoupon(): JsonResponse|RedirectResponse
    {
        try {
            $result = $this->couponService->removeCoupon(session('applied_coupon.code', ''));

            if ($result['success']) {
                session()->forget('applied_coupon');

                if (request()->expectsJson()) {
                    return response()->json([
                        'success' => true,
                        'message' => $result['message'],
                        'cart' => $this->cartService->getSummary(),
                    ]);
                }

                return redirect()->back()->with('success', $result['message']);
            } else {
                if (request()->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => $result['message'],
                    ], 400);
                }

                return redirect()->back()->with('error', $result['message']);
            }
        } catch (\Exception $e) {
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erro ao remover cupom',
                ], 500);
            }

            return redirect()->back()->with('error', 'Erro ao remover cupom');
        }
    }

    /**
     * Get cart summary.
     */
    public function summary(): JsonResponse
    {
        $cartSummary = $this->cartService->getSummary();
        $appliedCoupon = session('applied_coupon');

        return response()->json([
            'success' => true,
            'cart' => $cartSummary,
            'coupon' => $appliedCoupon,
        ]);
    }

    /**
     * Get shipping options.
     */
    public function shippingOptions(Request $request): JsonResponse
    {
        $address = $request->get('address', []);

        // This would calculate shipping based on address
        $shippingOptions = [
            'standard' => [
                'name' => 'Envio Padrão',
                'cost' => 15.00,
                'days' => '3-5 dias úteis',
            ],
            'express' => [
                'name' => 'Envio Expresso',
                'cost' => 35.00,
                'days' => '1-2 dias úteis',
            ],
        ];

        return response()->json([
            'success' => true,
            'options' => $shippingOptions,
        ]);
    }

    /**
     * Proceed to checkout.
     */
    public function checkout(): View|RedirectResponse
    {
        if ($this->cartService->isEmpty()) {
            return redirect()->route('store.products.index')
                ->with('error', 'Seu carrinho está vazio');
        }

        $cartItems = $this->cartService->getItems();
        $cartSummary = $this->cartService->getSummary();
        $appliedCoupon = session('applied_coupon');
        $shippingMethods = $this->shippingService->getAvailableShippingMethods();

        return view('store::cart.checkout', compact(
            'cartItems',
            'cartSummary',
            'appliedCoupon',
            'shippingMethods'
        ));
    }

    /**
     * Get cart item count for header.
     */
    public function itemCount(): JsonResponse
    {
        return response()->json([
            'count' => $this->cartService->getItemCount(),
        ]);
    }
}