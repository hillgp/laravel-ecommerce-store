<?php

namespace LaravelEcommerce\Store\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use LaravelEcommerce\Store\Models\CustomerAddress;

class AddressAccessMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $addressId = null)
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
                ->with('error', 'Você precisa estar logado para gerenciar endereços');
        }

        // Get address ID from route parameter
        if ($addressId) {
            $address = CustomerAddress::find($addressId);
        } else {
            $addressId = $request->route('address') ?? $request->route('addressId');
            $address = CustomerAddress::find($addressId);
        }

        if (!$address) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Endereço não encontrado',
                    'error' => 'Endereço não existe'
                ], 404);
            }

            abort(404, 'Endereço não encontrado');
        }

        // Check if address belongs to the authenticated customer
        if ($address->customer_id !== $customer->id) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Acesso negado',
                    'error' => 'Este endereço não pertence a você'
                ], 403);
            }

            abort(403, 'Este endereço não pertence a você');
        }

        // Add address to request for easy access in controllers
        $request->merge(['address' => $address]);

        return $next($request);
    }
}