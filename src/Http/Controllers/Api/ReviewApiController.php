<?php

namespace LaravelEcommerce\Store\Http\Controllers\Api;

use LaravelEcommerce\Store\Models\Review;
use LaravelEcommerce\Store\Models\Product;
use LaravelEcommerce\Store\Services\CacheService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class ReviewApiController extends \LaravelEcommerce\Store\Http\Controllers\Controller
{
    protected CacheService $cacheService;

    public function __construct(CacheService $cacheService)
    {
        $this->cacheService = $cacheService;
        $this->middleware('auth:sanctum');
    }

    /**
     * Listar avaliações de um produto.
     */
    public function index(Product $product, Request $request): JsonResponse
    {
        try {
            $request->validate([
                'page' => 'integer|min:1',
                'per_page' => 'integer|min:1|max:50',
                'rating' => 'integer|min:1|max:5',
                'verified' => 'boolean',
                'with_images' => 'boolean',
            ]);

            $query = $product->reviews()->with('customer');

            // Filtros
            if ($request->filled('rating')) {
                $query->where('rating', $request->rating);
            }

            if ($request->filled('verified')) {
                $query->where('is_verified', $request->verified);
            }

            if ($request->boolean('with_images')) {
                $query->whereNotNull('images');
            }

            $perPage = $request->get('per_page', 10);
            $reviews = $query->orderBy('created_at', 'desc')->paginate($perPage);

            // Estatísticas
            $stats = [
                'total_reviews' => $product->reviews()->count(),
                'average_rating' => $product->reviews()->avg('rating') ?? 0,
                'rating_distribution' => $this->getRatingDistribution($product),
            ];

            return response()->json([
                'success' => true,
                'data' => $reviews->items(),
                'product' => $product->only(['id', 'name']),
                'stats' => $stats,
                'pagination' => [
                    'current_page' => $reviews->currentPage(),
                    'last_page' => $reviews->lastPage(),
                    'per_page' => $reviews->perPage(),
                    'total' => $reviews->total(),
                    'from' => $reviews->firstItem(),
                    'to' => $reviews->lastItem(),
                ],
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Dados de validação inválidos',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar avaliações',
            ], 500);
        }
    }

    /**
     * Criar avaliação.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'product_id' => 'required|exists:products,id',
                'order_id' => 'nullable|exists:orders,id',
                'rating' => 'required|integer|min:1|max:5',
                'title' => 'nullable|string|max:255',
                'comment' => 'required|string|max:2000',
                'pros' => 'nullable|string|max:1000',
                'cons' => 'nullable|string|max:1000',
                'images' => 'nullable|array|max:5',
                'images.*' => 'string|max:500',
                'is_recommended' => 'boolean',
            ]);

            $user = $request->user();
            $product = Product::findOrFail($request->product_id);

            // Verificar se usuário já avaliou este produto
            $existingReview = Review::where('product_id', $request->product_id)
                ->where('customer_id', $user->id)
                ->first();

            if ($existingReview) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você já avaliou este produto',
                ], 422);
            }

            // Verificar se usuário comprou o produto (se order_id fornecido)
            if ($request->filled('order_id')) {
                $order = $user->orders()->find($request->order_id);
                if (!$order) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Pedido não encontrado',
                    ], 404);
                }

                // Verificar se o produto está no pedido
                $hasProduct = $order->items()->where('product_id', $request->product_id)->exists();
                if (!$hasProduct) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Produto não encontrado no pedido',
                    ], 422);
                }
            }

            $review = Review::create([
                'product_id' => $request->product_id,
                'customer_id' => $user->id,
                'order_id' => $request->order_id,
                'rating' => $request->rating,
                'title' => $request->title,
                'comment' => $request->comment,
                'pros' => $request->pros,
                'cons' => $request->cons,
                'images' => $request->images,
                'is_recommended' => $request->boolean('is_recommended', true),
                'is_approved' => config('store.auto_approve_reviews', true),
                'is_verified' => $request->filled('order_id'),
            ]);

            // Invalidar cache
            $this->cacheService->invalidateProductCache($product->id);

            $review->load('customer');

            return response()->json([
                'success' => true,
                'message' => 'Avaliação criada com sucesso',
                'data' => $review,
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Dados de validação inválidos',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao criar avaliação',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Exibir avaliação específica.
     */
    public function show(Review $review): JsonResponse
    {
        try {
            $review->load('customer', 'product');

            return response()->json([
                'success' => true,
                'data' => $review,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Avaliação não encontrada',
            ], 404);
        }
    }

    /**
     * Atualizar avaliação.
     */
    public function update(Request $request, Review $review): JsonResponse
    {
        try {
            $user = $request->user();

            // Verificar se a avaliação pertence ao usuário
            if ($review->customer_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Avaliação não encontrada',
                ], 404);
            }

            $request->validate([
                'rating' => 'required|integer|min:1|max:5',
                'title' => 'nullable|string|max:255',
                'comment' => 'required|string|max:2000',
                'pros' => 'nullable|string|max:1000',
                'cons' => 'nullable|string|max:1000',
                'images' => 'nullable|array|max:5',
                'images.*' => 'string|max:500',
                'is_recommended' => 'boolean',
            ]);

            $review->update($request->only([
                'rating', 'title', 'comment', 'pros', 'cons', 'images', 'is_recommended'
            ]));

            // Invalidar cache
            $this->cacheService->invalidateProductCache($review->product_id);

            $review->load('customer');

            return response()->json([
                'success' => true,
                'message' => 'Avaliação atualizada com sucesso',
                'data' => $review,
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Dados de validação inválidos',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar avaliação',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Excluir avaliação.
     */
    public function destroy(Review $review): JsonResponse
    {
        try {
            $user = request()->user();

            // Verificar se a avaliação pertence ao usuário
            if ($review->customer_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Avaliação não encontrada',
                ], 404);
            }

            $productId = $review->product_id;
            $review->delete();

            // Invalidar cache
            $this->cacheService->invalidateProductCache($productId);

            return response()->json([
                'success' => true,
                'message' => 'Avaliação excluída com sucesso',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao excluir avaliação',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Votar em avaliação (útil/not useful).
     */
    public function vote(Request $request, Review $review): JsonResponse
    {
        try {
            $request->validate([
                'type' => 'required|in:helpful,not_helpful',
            ]);

            $user = $request->user();
            $type = $request->type;

            // Verificar se usuário já votou
            $existingVote = $review->votes()->where('customer_id', $user->id)->first();

            if ($existingVote) {
                if ($existingVote->type === $type) {
                    // Remover voto
                    $existingVote->delete();
                    $message = 'Voto removido';
                } else {
                    // Alterar voto
                    $existingVote->update(['type' => $type]);
                    $message = 'Voto alterado';
                }
            } else {
                // Criar novo voto
                $review->votes()->create([
                    'customer_id' => $user->id,
                    'type' => $type,
                ]);
                $message = 'Voto registrado';
            }

            $review->load('votes');

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'helpful_votes' => $review->votes()->where('type', 'helpful')->count(),
                    'not_helpful_votes' => $review->votes()->where('type', 'not_helpful')->count(),
                ],
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Dados de validação inválidos',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao votar na avaliação',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Denunciar avaliação.
     */
    public function report(Request $request, Review $review): JsonResponse
    {
        try {
            $request->validate([
                'reason' => 'required|string|max:500',
            ]);

            $user = $request->user();

            // Verificar se já denunciou
            $existingReport = $review->reports()->where('customer_id', $user->id)->first();

            if ($existingReport) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você já denunciou esta avaliação',
                ], 422);
            }

            $review->reports()->create([
                'customer_id' => $user->id,
                'reason' => $request->reason,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Denúncia registrada com sucesso',
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Dados de validação inválidos',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao denunciar avaliação',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obter estatísticas de avaliações de um produto.
     */
    public function stats(Product $product): JsonResponse
    {
        try {
            $reviews = $product->reviews;

            $stats = [
                'total_reviews' => $reviews->count(),
                'average_rating' => $reviews->avg('rating') ?? 0,
                'rating_distribution' => $this->getRatingDistribution($product),
                'verified_reviews' => $reviews->where('is_verified', true)->count(),
                'recommended_percentage' => $reviews->where('is_recommended', true)->count() / max($reviews->count(), 1) * 100,
                'helpful_votes' => $reviews->sum(fn($review) => $review->votes()->where('type', 'helpful')->count()),
                'total_votes' => $reviews->sum(fn($review) => $review->votes()->count()),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar estatísticas',
            ], 500);
        }
    }

    /**
     * Obter distribuição de avaliações.
     */
    protected function getRatingDistribution(Product $product): array
    {
        $distribution = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];

        foreach ($product->reviews as $review) {
            $distribution[$review->rating]++;
        }

        return $distribution;
    }
}