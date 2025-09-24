<?php

namespace SupernovaCorp\LaravelEcommerceStore\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Wishlist;
use App\Models\WishlistItem;
use App\Services\CacheService;

class WishlistController extends Controller
{
    protected CacheService $cacheService;

    public function __construct(CacheService $cacheService)
    {
        $this->cacheService = $cacheService;

        $this->middleware('auth:sanctum')->except([
            'showShared',
            'getPublicWishlists'
        ]);
    }

    /**
     * Lista todas as wishlists do usuário
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $customerId = Auth::id();
            $wishlists = Wishlist::getForCustomer($customerId)
                                ->with('items.product')
                                ->get();

            return response()->json([
                'success' => true,
                'data' => $wishlists,
                'message' => 'Wishlists obtidas com sucesso'
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao listar wishlists: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erro interno do servidor',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Cria uma nova wishlist
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'is_public' => 'boolean',
            'is_default' => 'boolean'
        ]);

        try {
            DB::beginTransaction();

            $wishlist = Wishlist::create([
                'customer_id' => Auth::id(),
                'name' => $request->name,
                'description' => $request->description,
                'is_public' => $request->boolean('is_public', false),
                'is_default' => $request->boolean('is_default', false)
            ]);

            // Se é padrão, remove padrão das outras
            if ($wishlist->is_default) {
                Wishlist::where('customer_id', Auth::id())
                       ->where('id', '!=', $wishlist->id)
                       ->where('is_default', true)
                       ->update(['is_default' => false]);
            }

            DB::commit();

            // Invalida cache
            $this->cacheService->invalidateWishlistCache(Auth::id());

            return response()->json([
                'success' => true,
                'data' => $wishlist,
                'message' => 'Wishlist criada com sucesso'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao criar wishlist: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erro ao criar wishlist',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Exibe uma wishlist específica
     */
    public function show(int $wishlistId): JsonResponse
    {
        try {
            $wishlist = Wishlist::with('items.product')
                              ->findOrFail($wishlistId);

            // Verifica se pertence ao usuário ou é pública
            if ($wishlist->customer_id !== Auth::id() && !$wishlist->is_public) {
                return response()->json([
                    'success' => false,
                    'message' => 'Acesso negado'
                ], 403);
            }

            return response()->json([
                'success' => true,
                'data' => $wishlist,
                'message' => 'Wishlist obtida com sucesso'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Wishlist não encontrada'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Erro ao obter wishlist: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erro interno do servidor',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Atualiza uma wishlist
     */
    public function update(Request $request, int $wishlistId): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'is_public' => 'boolean',
            'is_default' => 'boolean'
        ]);

        try {
            DB::beginTransaction();

            $wishlist = Wishlist::findOrFail($wishlistId);

            // Verifica se pertence ao usuário
            if ($wishlist->customer_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Acesso negado'
                ], 403);
            }

            $wishlist->update([
                'name' => $request->name,
                'description' => $request->description,
                'is_public' => $request->boolean('is_public', $wishlist->is_public)
            ]);

            // Se está sendo definida como padrão
            if ($request->boolean('is_default', false)) {
                Wishlist::where('customer_id', Auth::id())
                       ->where('id', '!=', $wishlist->id)
                       ->where('is_default', true)
                       ->update(['is_default' => false]);

                $wishlist->update(['is_default' => true]);
            }

            DB::commit();

            // Invalida cache
            $this->cacheService->invalidateWishlistCache(Auth::id());

            return response()->json([
                'success' => true,
                'data' => $wishlist->fresh(),
                'message' => 'Wishlist atualizada com sucesso'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Wishlist não encontrada'
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao atualizar wishlist: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar wishlist',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Remove uma wishlist
     */
    public function destroy(int $wishlistId): JsonResponse
    {
        try {
            DB::beginTransaction();

            $wishlist = Wishlist::findOrFail($wishlistId);

            // Verifica se pertence ao usuário
            if ($wishlist->customer_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Acesso negado'
                ], 403);
            }

            // Não permite excluir wishlist padrão se for a única
            if ($wishlist->is_default) {
                $otherWishlists = Wishlist::where('customer_id', Auth::id())
                                       ->where('id', '!=', $wishlist->id)
                                       ->count();

                if ($otherWishlists === 0) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Não é possível excluir a única wishlist'
                    ], 400);
                }
            }

            $wishlist->delete();

            DB::commit();

            // Invalida cache
            $this->cacheService->invalidateWishlistCache(Auth::id());

            return response()->json([
                'success' => true,
                'message' => 'Wishlist excluída com sucesso'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Wishlist não encontrada'
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao excluir wishlist: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erro ao excluir wishlist',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Adiciona produto à wishlist
     */
    public function addProduct(Request $request, int $wishlistId): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'quantity' => 'integer|min:1|max:999',
            'notes' => 'nullable|string|max:500'
        ]);

        try {
            $wishlist = Wishlist::findOrFail($wishlistId);

            // Verifica se pertence ao usuário
            if ($wishlist->customer_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Acesso negado'
                ], 403);
            }

            $product = Product::findOrFail($request->product_id);

            // Verifica se produto está ativo
            if (!$product->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Produto não disponível'
                ], 400);
            }

            $wishlist->addProduct(
                $request->product_id,
                $request->integer('quantity', 1),
                $request->notes
            );

            // Invalida cache
            $this->cacheService->invalidateWishlistCache(Auth::id());

            return response()->json([
                'success' => true,
                'message' => 'Produto adicionado à wishlist'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Wishlist ou produto não encontrado'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Erro ao adicionar produto à wishlist: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erro ao adicionar produto',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Remove produto da wishlist
     */
    public function removeProduct(int $wishlistId, int $productId): JsonResponse
    {
        try {
            $wishlist = Wishlist::findOrFail($wishlistId);

            // Verifica se pertence ao usuário
            if ($wishlist->customer_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Acesso negado'
                ], 403);
            }

            $removed = $wishlist->removeProduct($productId);

            if (!$removed) {
                return response()->json([
                    'success' => false,
                    'message' => 'Produto não encontrado na wishlist'
                ], 404);
            }

            // Invalida cache
            $this->cacheService->invalidateWishlistCache(Auth::id());

            return response()->json([
                'success' => true,
                'message' => 'Produto removido da wishlist'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Wishlist não encontrada'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Erro ao remover produto da wishlist: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erro ao remover produto',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Move produto entre wishlists
     */
    public function moveProduct(Request $request, int $wishlistId, int $productId): JsonResponse
    {
        $request->validate([
            'target_wishlist_id' => 'required|integer|exists:wishlists,id'
        ]);

        try {
            $wishlist = Wishlist::findOrFail($wishlistId);
            $targetWishlist = Wishlist::findOrFail($request->target_wishlist_id);

            // Verifica se ambas pertencem ao usuário
            if ($wishlist->customer_id !== Auth::id() || $targetWishlist->customer_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Acesso negado'
                ], 403);
            }

            $moved = $wishlist->moveItemTo($productId, $targetWishlist);

            if (!$moved) {
                return response()->json([
                    'success' => false,
                    'message' => 'Produto não encontrado na wishlist'
                ], 404);
            }

            // Invalida cache
            $this->cacheService->invalidateWishlistCache(Auth::id());

            return response()->json([
                'success' => true,
                'message' => 'Produto movido com sucesso'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Wishlist não encontrada'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Erro ao mover produto entre wishlists: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erro ao mover produto',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Atualiza item da wishlist
     */
    public function updateItem(Request $request, int $wishlistId, int $productId): JsonResponse
    {
        $request->validate([
            'quantity' => 'integer|min:1|max:999',
            'notes' => 'nullable|string|max:500'
        ]);

        try {
            $wishlist = Wishlist::findOrFail($wishlistId);

            // Verifica se pertence ao usuário
            if ($wishlist->customer_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Acesso negado'
                ], 403);
            }

            $item = $wishlist->getItem($productId);

            if (!$item) {
                return response()->json([
                    'success' => false,
                    'message' => 'Produto não encontrado na wishlist'
                ], 404);
            }

            if ($request->has('quantity')) {
                $item->updateQuantity($request->quantity);
            }

            if ($request->has('notes')) {
                $item->notes = $request->notes;
                $item->save();
            }

            // Invalida cache
            $this->cacheService->invalidateWishlistCache(Auth::id());

            return response()->json([
                'success' => true,
                'data' => $item->fresh(),
                'message' => 'Item atualizado com sucesso'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Wishlist não encontrada'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Erro ao atualizar item da wishlist: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar item',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Limpa wishlist
     */
    public function clear(int $wishlistId): JsonResponse
    {
        try {
            $wishlist = Wishlist::findOrFail($wishlistId);

            // Verifica se pertence ao usuário
            if ($wishlist->customer_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Acesso negado'
                ], 403);
            }

            $wishlist->clear();

            // Invalida cache
            $this->cacheService->invalidateWishlistCache(Auth::id());

            return response()->json([
                'success' => true,
                'message' => 'Wishlist limpa com sucesso'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Wishlist não encontrada'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Erro ao limpar wishlist: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erro ao limpar wishlist',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Duplica wishlist
     */
    public function duplicate(int $wishlistId): JsonResponse
    {
        try {
            $wishlist = Wishlist::findOrFail($wishlistId);

            // Verifica se pertence ao usuário
            if ($wishlist->customer_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Acesso negado'
                ], 403);
            }

            $newWishlist = $wishlist->duplicate();

            // Invalida cache
            $this->cacheService->invalidateWishlistCache(Auth::id());

            return response()->json([
                'success' => true,
                'data' => $newWishlist,
                'message' => 'Wishlist duplicada com sucesso'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Wishlist não encontrada'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Erro ao duplicar wishlist: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erro ao duplicar wishlist',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Compartilha wishlist
     */
    public function share(int $wishlistId): JsonResponse
    {
        try {
            $wishlist = Wishlist::findOrFail($wishlistId);

            // Verifica se pertence ao usuário
            if ($wishlist->customer_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Acesso negado'
                ], 403);
            }

            $shareUrl = $wishlist->share();

            return response()->json([
                'success' => true,
                'data' => [
                    'share_url' => $shareUrl,
                    'is_public' => $wishlist->is_public
                ],
                'message' => 'Link de compartilhamento gerado'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Wishlist não encontrada'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Erro ao compartilhar wishlist: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erro ao compartilhar wishlist',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Exibe wishlist compartilhada
     */
    public function showShared(string $token): JsonResponse
    {
        try {
            $wishlist = Wishlist::findByShareToken($token);

            if (!$wishlist) {
                return response()->json([
                    'success' => false,
                    'message' => 'Link inválido ou expirado'
                ], 404);
            }

            $wishlist->load('items.product', 'customer');

            return response()->json([
                'success' => true,
                'data' => $wishlist,
                'message' => 'Wishlist compartilhada'
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao exibir wishlist compartilhada: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erro ao carregar wishlist',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Obtém wishlists públicas
     */
    public function getPublicWishlists(Request $request): JsonResponse
    {
        try {
            $limit = $request->get('limit', 20);
            $wishlists = Wishlist::getPublicWishlists($limit)
                                ->with('items.product');

            return response()->json([
                'success' => true,
                'data' => $wishlists,
                'message' => 'Wishlists públicas obtidas com sucesso'
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao obter wishlists públicas: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erro interno do servidor',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Obtém produtos mais desejados
     */
    public function getMostWishedProducts(Request $request): JsonResponse
    {
        try {
            $limit = $request->get('limit', 10);
            $products = WishlistItem::getMostAdded($limit)
                                   ->map(function ($item) {
                                       return $item->product;
                                   });

            return response()->json([
                'success' => true,
                'data' => $products,
                'message' => 'Produtos mais desejados obtidos com sucesso'
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao obter produtos mais desejados: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erro interno do servidor',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Obtém estatísticas de wishlist
     */
    public function getStats(int $wishlistId): JsonResponse
    {
        try {
            $wishlist = Wishlist::findOrFail($wishlistId);

            // Verifica se pertence ao usuário
            if ($wishlist->customer_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Acesso negado'
                ], 403);
            }

            $stats = $wishlist->stats;

            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'Estatísticas obtidas com sucesso'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Wishlist não encontrada'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Erro ao obter estatísticas da wishlist: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erro interno do servidor',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}