<?php

namespace SupernovaCorp\LaravelEcommerceStore\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cookie;
use App\Http\Controllers\Controller;
use App\Models\Product;
use SupernovaCorp\LaravelEcommerceStore\Models\ProductComparison;

class ComparisonController extends Controller
{
    /**
     * Exibe a página de comparação
     */
    public function index(Request $request): \Illuminate\View\View
    {
        $sessionId = ProductComparison::getSessionIdFromCookie();
        $comparison = ProductComparison::getCurrent($sessionId);

        // Se não há produtos na comparação, redireciona para a loja
        if ($comparison->products->isEmpty()) {
            return redirect()->route('store.products.index')
                           ->with('info', 'Adicione produtos para comparar');
        }

        return view('comparison.index', compact('comparison'));
    }

    /**
     * Adiciona produto à comparação
     */
    public function addProduct(Request $request, int $productId): JsonResponse
    {
        try {
            $product = Product::findOrFail($productId);

            // Verifica se produto está ativo
            if (!$product->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Produto não disponível para comparação'
                ], 400);
            }

            $comparison = ProductComparison::getCurrent();
            $added = $comparison->addProduct($productId, $request->notes);

            if (!$added) {
                return response()->json([
                    'success' => false,
                    'message' => 'Não foi possível adicionar o produto. Limite máximo atingido ou produto já adicionado.'
                ], 400);
            }

            // Atualiza cookie se necessário
            if (!$request->cookie('product_comparison_session')) {
                Cookie::queue('product_comparison_session', $comparison->session_id, 60 * 24 * 7);
            }

            return response()->json([
                'success' => true,
                'message' => 'Produto adicionado à comparação',
                'data' => [
                    'comparison_count' => $comparison->products_count,
                    'can_add_more' => $comparison->canAddMoreProducts()
                ]
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Produto não encontrado'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Erro ao adicionar produto à comparação: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erro interno do servidor',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Remove produto da comparação
     */
    public function removeProduct(int $productId): JsonResponse
    {
        try {
            $comparison = ProductComparison::getCurrent();
            $removed = $comparison->removeProduct($productId);

            if (!$removed) {
                return response()->json([
                    'success' => false,
                    'message' => 'Produto não encontrado na comparação'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Produto removido da comparação',
                'data' => [
                    'comparison_count' => $comparison->products_count,
                    'can_add_more' => $comparison->canAddMoreProducts()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao remover produto da comparação: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erro interno do servidor',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Limpa toda a comparação
     */
    public function clear(): JsonResponse
    {
        try {
            $comparison = ProductComparison::getCurrent();
            $comparison->clear();

            return response()->json([
                'success' => true,
                'message' => 'Comparação limpa com sucesso',
                'data' => [
                    'comparison_count' => 0,
                    'can_add_more' => true
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao limpar comparação: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erro interno do servidor',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Obtém produtos da comparação
     */
    public function getProducts(): JsonResponse
    {
        try {
            $comparison = ProductComparison::getCurrent();
            $products = $comparison->products;

            return response()->json([
                'success' => true,
                'data' => $products,
                'meta' => [
                    'count' => $products->count(),
                    'max_products' => $comparison->max_products,
                    'can_add_more' => $comparison->canAddMoreProducts()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao obter produtos da comparação: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erro interno do servidor',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Compartilha comparação
     */
    public function share(): JsonResponse
    {
        try {
            $comparison = ProductComparison::getCurrent();

            if ($comparison->products->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Adicione produtos antes de compartilhar'
                ], 400);
            }

            $shareUrl = $comparison->share();

            return response()->json([
                'success' => true,
                'data' => [
                    'share_url' => $shareUrl,
                    'is_active' => $comparison->is_active
                ],
                'message' => 'Link de compartilhamento gerado'
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao compartilhar comparação: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erro ao compartilhar comparação',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Exibe comparação compartilhada
     */
    public function showShared(string $token): \Illuminate\View\View
    {
        try {
            $comparison = ProductComparison::findByShareToken($token);

            if (!$comparison || !$comparison->isActive()) {
                abort(404, 'Link inválido ou expirado');
            }

            $comparison->load('products', 'customer');

            return view('comparison.shared', compact('comparison'));

        } catch (\Exception $e) {
            Log::error('Erro ao exibir comparação compartilhada: ' . $e->getMessage());
            abort(500, 'Erro interno do servidor');
        }
    }

    /**
     * Obtém estatísticas da comparação
     */
    public function getStats(): JsonResponse
    {
        try {
            $comparison = ProductComparison::getCurrent();
            $stats = $comparison->stats;

            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'Estatísticas obtidas com sucesso'
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao obter estatísticas da comparação: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erro interno do servidor',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Atualiza configurações da comparação
     */
    public function updateSettings(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'nullable|string|max:255',
            'max_products' => 'nullable|integer|min:2|max:10'
        ]);

        try {
            $comparison = ProductComparison::getCurrent();

            if ($request->has('name')) {
                $comparison->name = $request->name;
            }

            if ($request->has('max_products')) {
                $comparison->max_products = $request->max_products;
            }

            $comparison->save();

            return response()->json([
                'success' => true,
                'data' => $comparison,
                'message' => 'Configurações atualizadas com sucesso'
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao atualizar configurações da comparação: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar configurações',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Move produto na comparação (reordenar)
     */
    public function reorderProduct(Request $request, int $productId): JsonResponse
    {
        $request->validate([
            'sort_order' => 'required|integer|min:0'
        ]);

        try {
            $comparison = ProductComparison::getCurrent();

            // Atualiza a ordenação do produto
            DB::table('product_comparison_items')
              ->where('product_comparison_id', $comparison->id)
              ->where('product_id', $productId)
              ->update(['sort_order' => $request->sort_order]);

            return response()->json([
                'success' => true,
                'message' => 'Produto reordenado com sucesso'
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao reordenar produto na comparação: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erro ao reordenar produto',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Adiciona nota a um produto na comparação
     */
    public function addNote(Request $request, int $productId): JsonResponse
    {
        $request->validate([
            'notes' => 'required|string|max:500'
        ]);

        try {
            $comparison = ProductComparison::getCurrent();

            // Atualiza a nota do produto
            DB::table('product_comparison_items')
              ->where('product_comparison_id', $comparison->id)
              ->where('product_id', $productId)
              ->update(['notes' => $request->notes]);

            return response()->json([
                'success' => true,
                'message' => 'Nota adicionada com sucesso'
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao adicionar nota na comparação: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erro ao adicionar nota',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Obtém produtos mais comparados
     */
    public function getMostComparedProducts(Request $request): JsonResponse
    {
        try {
            $limit = $request->get('limit', 10);
            $products = ProductComparison::getMostComparedProducts($limit);

            return response()->json([
                'success' => true,
                'data' => $products,
                'message' => 'Produtos mais comparados obtidos com sucesso'
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao obter produtos mais comparados: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erro interno do servidor',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Obtém dados de comparação formatados
     */
    public function getComparisonData(): JsonResponse
    {
        try {
            $comparison = ProductComparison::getCurrent();
            $comparisonData = $comparison->comparison_data;

            return response()->json([
                'success' => true,
                'data' => $comparisonData,
                'message' => 'Dados de comparação obtidos com sucesso'
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao obter dados de comparação: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erro interno do servidor',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}