<?php

namespace LaravelEcommerce\Store\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use LaravelEcommerce\Store\Models\Product;

class CanReviewMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $productId = null)
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
                ->with('error', 'Você precisa estar logado para avaliar produtos');
        }

        // Get product ID from route parameter
        if ($productId) {
            $product = Product::find($productId);
        } else {
            $productId = $request->route('product') ?? $request->route('productId');
            $product = Product::find($productId);
        }

        if (!$product) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Produto não encontrado',
                    'error' => 'Produto não existe'
                ], 404);
            }

            abort(404, 'Produto não encontrado');
        }

        // Check if customer can review this product
        if (!$product->canBeReviewedBy($customer->id)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Não autorizado',
                    'error' => 'Você não pode avaliar este produto'
                ], 403);
            }

            return redirect()->back()
                ->with('error', 'Você não pode avaliar este produto. Apenas clientes que compraram o produto podem fazer avaliações.');
        }

        // Add product to request for easy access in controllers
        $request->merge(['product' => $product]);

        return $next($request);
    }
}