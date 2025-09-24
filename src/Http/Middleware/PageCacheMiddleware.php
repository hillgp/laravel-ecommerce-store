<?php

namespace LaravelEcommerce\Store\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;

class PageCacheMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, int $ttl = 300)
    {
        // Não cachear se usuário estiver logado
        if (Auth::check()) {
            return $next($request);
        }

        // Não cachear métodos que não sejam GET
        if (!$request->isMethod('GET')) {
            return $next($request);
        }

        // Não cachear se houver parâmetros de busca ou filtros
        if ($request->has(['search', 'filter', 'sort', 'page'])) {
            return $next($request);
        }

        // Gerar chave de cache
        $key = $this->generateCacheKey($request);

        // Verificar se existe cache
        if (Cache::has($key)) {
            $cachedResponse = Cache::get($key);

            // Adicionar header indicando que é cache
            return response($cachedResponse['content'])
                ->header('X-Cache-Status', 'HIT')
                ->header('X-Cache-Expires', $cachedResponse['expires_at']);
        }

        // Processar requisição
        $response = $next($request);

        // Cachear apenas respostas bem-sucedidas
        if ($response->getStatusCode() === 200) {
            $content = $response->getContent();

            Cache::put($key, [
                'content' => $content,
                'expires_at' => now()->addSeconds($ttl)->toISOString(),
            ], $ttl);

            // Adicionar header indicando cache
            $response->header('X-Cache-Status', 'MISS');
        }

        return $response;
    }

    /**
     * Generate cache key for the request.
     */
    protected function generateCacheKey(Request $request): string
    {
        $uri = $request->getPathInfo();
        $queryString = $request->getQueryString();

        $key = 'page_cache:' . $uri;

        if ($queryString) {
            $key .= ':' . md5($queryString);
        }

        // Adicionar user agent para diferentes dispositivos
        $userAgent = $request->userAgent();
        if ($userAgent) {
            $key .= ':ua:' . md5($userAgent);
        }

        return $key;
    }
}