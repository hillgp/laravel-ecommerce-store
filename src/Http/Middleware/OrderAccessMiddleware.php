<?php

namespace LaravelEcommerce\Store\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use LaravelEcommerce\Store\Models\Order;

class OrderAccessMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $orderId = null)
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
                ->with('error', 'Você precisa estar logado para acessar esta página');
        }

        // If order ID is provided in route parameter
        if ($orderId) {
            $order = Order::find($orderId);
        } else {
            // Try to get order ID from route parameter
            $orderId = $request->route('order') ?? $request->route('orderId');
            $order = Order::find($orderId);
        }

        if (!$order) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Pedido não encontrado',
                    'error' => 'Pedido não existe'
                ], 404);
            }

            abort(404, 'Pedido não encontrado');
        }

        // Check if order belongs to the authenticated customer
        if ($order->customer_id !== $customer->id) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Acesso negado',
                    'error' => 'Você não tem permissão para acessar este pedido'
                ], 403);
            }

            abort(403, 'Você não tem permissão para acessar este pedido');
        }

        // Add order to request for easy access in controllers
        $request->merge(['order' => $order]);

        return $next($request);
    }
}