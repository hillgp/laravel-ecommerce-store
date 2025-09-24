<?php

namespace LaravelEcommerce\Store\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Verificar se usuário está autenticado
        if (!Auth::check()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Não autenticado'
                ], 401);
            }

            return redirect()->route('login')
                ->with('error', 'Você precisa estar logado para acessar esta área.');
        }

        $user = Auth::user();

        // Verificar se usuário tem permissão de admin
        if (!$this->isAdmin($user)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Acesso negado. Permissão de administrador necessária.'
                ], 403);
            }

            return redirect()->back()
                ->with('error', 'Acesso negado. Você não tem permissão para acessar esta área.');
        }

        return $next($request);
    }

    /**
     * Verificar se usuário é administrador.
     */
    protected function isAdmin($user): bool
    {
        // Verificar se existe tabela de admins
        if (class_exists('\LaravelEcommerce\Store\Models\Admin')) {
            return \LaravelEcommerce\Store\Models\Admin::where('user_id', $user->id)->exists();
        }

        // Verificar se usuário tem role admin
        if (method_exists($user, 'hasRole')) {
            return $user->hasRole('admin');
        }

        // Verificar se usuário tem tipo admin
        if (method_exists($user, 'isAdmin')) {
            return $user->isAdmin();
        }

        // Verificar email do usuário (fallback)
        $adminEmails = config('store.admin_emails', ['admin@loja.com']);
        return in_array($user->email, $adminEmails);
    }
}