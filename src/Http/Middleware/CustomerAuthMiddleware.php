<?php

namespace LaravelEcommerce\Store\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use LaravelEcommerce\Store\Models\Customer;

class CustomerAuthMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $guard = 'customer')
    {
        if (!Auth::guard($guard)->check()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Não autenticado',
                    'error' => 'Token de acesso inválido ou expirado'
                ], 401);
            }

            // Redirect to login page
            return redirect()->route('customer.login')
                ->with('error', 'Você precisa estar logado para acessar esta página');
        }

        $customer = Auth::guard($guard)->user();

        // Check if customer is active
        if (!$customer->is_active) {
            Auth::guard($guard)->logout();

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Conta desativada',
                    'error' => 'Sua conta foi desativada. Entre em contato com o suporte.'
                ], 403);
            }

            return redirect()->route('customer.login')
                ->with('error', 'Sua conta foi desativada. Entre em contato com o suporte.');
        }

        // Update last login
        $customer->updateLastLogin();

        return $next($request);
    }
}