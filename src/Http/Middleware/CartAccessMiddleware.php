<?php

namespace LaravelEcommerce\Store\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use LaravelEcommerce\Store\Models\Cart;

class CartAccessMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $cartId = null)
    {
        $customer = Auth::guard('customer')->user();

        if (!$customer) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Não autenticado',
                    'error' => 'Cliente não autenticado'
                ], 401);
            }

            return redirect()->route('customer.login')
                ->with('error', 'Você precisa estar logado para acessar o carrinho');
        }

        // Get cart ID from route parameter
        if ($cartId) {
            $cart = Cart::find($cartId);
        } else {
            $cartId = $request->route('cart') ?? $request->route('cartId');
            $cart = Cart::find($cartId);
        }

        if (!$cart) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Carrinho não encontrado',
                    'error' => 'Carrinho não existe'
                ], 404);
            }

            abort(404, 'Carrinho não encontrado');
        }

        // Check if cart belongs to the authenticated customer
        if ($cart->customer_id !== $customer->id) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Acesso negado',
                    'error' => 'Este carrinho não pertence a você'
                ], 403);
            }

            abort(403, 'Este carrinho não pertence a você');
        }

        // Check if cart is active
        if (!$cart->is_active) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Carrinho inativo',
                    'error' => 'Este carrinho está inativo'
                ], 403);
            }

            return redirect()->route('products.index')
                ->with('error', 'Este carrinho está inativo');
        }

        // Check if cart is expired
        if ($cart->isExpired()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Carrinho expirado',
                    'error' => 'Este carrinho expirou'
                ], 403);
            }

            return redirect()->route('products.index')
                ->with('error', 'Este carrinho expirou. Os itens foram removidos.');
        }

        // Add cart to request for easy access in controllers
        $request->merge(['cart' => $cart]);

        return $next($request);
    }
}