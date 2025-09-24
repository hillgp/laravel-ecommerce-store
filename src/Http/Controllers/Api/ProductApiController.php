<?php

namespace LaravelEcommerce\Store\Http\Controllers\Api;

use LaravelEcommerce\Store\Models\Product;
use LaravelEcommerce\Store\Models\Category;
use LaravelEcommerce\Store\Models\Brand;
use LaravelEcommerce\Store\Services\CacheService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class ProductApiController extends \LaravelEcommerce\Store\Http\Controllers\Controller
{
    protected CacheService $cacheService;

    public function __construct(CacheService $cacheService)
    {
        $this->cacheService = $cacheService;
        $this->middleware('auth:sanctum')->except(['index', 'show', 'search', 'featured', 'byCategory', 'byBrand']);
    }

    /**
     * Listar produtos com paginação e filtros.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'page' => 'integer|min:1',
                'per_page' => 'integer|min:1|max:100',
                'category' => 'integer|exists:categories,id',
                'brand' => 'integer|exists:brands,id',
                'featured' => 'boolean',
                'active' => 'boolean',
                'min_price' => 'numeric|min:0',
                'max_price' => 'numeric|min:0',
                'in_stock' => 'boolean',
                'search' => 'string|max:255',
                'sort' => 'in:name,price,created_at,rating',
                'direction' => 'in:asc,desc',
                'includes' => 'string',
            ]);

            $query = Product::with(['category', 'brand', 'images'])
                ->where('is_active', true);

            // Filtros
            if ($request->filled('category')) {
                $query->where('category_id', $request->category);
            }

            if ($request->filled('brand')) {
                $query->where('brand_id', $request->brand);
            }

            if ($request->filled('featured')) {
                $query->where('is_featured', $request->featured);
            }

            if ($request->filled('min_price')) {
                $query->where('price', '>=', $request->min_price);
            }

            if ($request->filled('max_price')) {
                $query->where('price', '<=', $request->max_price);
            }

            if ($request->filled('in_stock')) {
                $query->where('stock_quantity', '>', 0);
            }

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhere('sku', 'like', "%{$search}%");
                });
            }

            // Ordenação
            $sort = $request->get('sort', 'created_at');
            $direction = $request->get('direction', 'desc');
            $query->orderBy($sort, $direction);

            // Includes
            if ($request->filled('includes')) {
                $includes = explode(',', $request->includes);
                $query->with($includes);
            }

            $perPage = $request->get('per_page', 20);
            $products = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $products->items(),
                'pagination' => [
                    'current_page' => $products->currentPage(),
                    'last_page' => $products->lastPage(),
                    'per_page' => $products->perPage(),
                    'total' => $products->total(),
                    'from' => $products->firstItem(),
                    'to' => $products->lastItem(),
                ],
                'filters' => $request->only([
                    'category', 'brand', 'featured', 'active',
                    'min_price', 'max_price', 'in_stock', 'search', 'sort', 'direction'
                ]),
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
                'message' => 'Erro interno do servidor',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Exibir produto específico.
     */
    public function show(Product $product): JsonResponse
    {
        try {
            $product->load([
                'category',
                'brand',
                'images',
                'reviews' => function ($query) {
                    $query->with('customer')->latest();
                },
                'variations',
                'relatedProducts' => function ($query) {
                    $query->limit(8);
                }
            ]);

            // Incrementar contador de visualizações
            $product->increment('views');

            return response()->json([
                'success' => true,
                'data' => $product,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Produto não encontrado',
            ], 404);
        }
    }

    /**
     * Buscar produtos.
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'q' => 'required|string|max:255',
                'limit' => 'integer|min:1|max:50',
                'category' => 'integer|exists:categories,id',
            ]);

            $query = $request->get('q');
            $limit = $request->get('limit', 20);
            $categoryId = $request->get('category');

            $products = $this->cacheService->getSearchResults($query, $limit);

            if ($categoryId) {
                $products = $products->where('category_id', $categoryId);
            }

            return response()->json([
                'success' => true,
                'data' => $products,
                'query' => $query,
                'total' => $products->count(),
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
                'message' => 'Erro na busca',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Produtos em destaque.
     */
    public function featured(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'limit' => 'integer|min:1|max:50',
            ]);

            $limit = $request->get('limit', 12);
            $products = $this->cacheService->getFeaturedProducts($limit);

            return response()->json([
                'success' => true,
                'data' => $products,
                'total' => $products->count(),
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
                'message' => 'Erro ao buscar produtos em destaque',
            ], 500);
        }
    }

    /**
     * Produtos por categoria.
     */
    public function byCategory(Category $category, Request $request): JsonResponse
    {
        try {
            $request->validate([
                'limit' => 'integer|min:1|max:50',
            ]);

            $limit = $request->get('limit', 20);
            $products = $this->cacheService->getProductsByCategory($category->id, $limit);

            return response()->json([
                'success' => true,
                'data' => $products,
                'category' => $category->only(['id', 'name', 'slug']),
                'total' => $products->count(),
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
                'message' => 'Erro ao buscar produtos da categoria',
            ], 500);
        }
    }

    /**
     * Produtos por marca.
     */
    public function byBrand(Brand $brand, Request $request): JsonResponse
    {
        try {
            $request->validate([
                'limit' => 'integer|min:1|max:50',
            ]);

            $limit = $request->get('limit', 20);
            $products = $this->cacheService->getProductsByBrand($brand->id, $limit);

            return response()->json([
                'success' => true,
                'data' => $products,
                'brand' => $brand->only(['id', 'name', 'slug']),
                'total' => $products->count(),
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
                'message' => 'Erro ao buscar produtos da marca',
            ], 500);
        }
    }

    /**
     * Criar produto (apenas admin).
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'sku' => 'required|string|max:100|unique:products,sku',
                'category_id' => 'required|exists:categories,id',
                'brand_id' => 'nullable|exists:brands,id',
                'price' => 'required|numeric|min:0',
                'compare_price' => 'nullable|numeric|min:0',
                'cost_price' => 'nullable|numeric|min:0',
                'stock_quantity' => 'required|integer|min:0',
                'min_stock_quantity' => 'nullable|integer|min:0',
                'description' => 'nullable|string',
                'short_description' => 'nullable|string|max:500',
                'weight' => 'nullable|numeric|min:0',
                'dimensions' => 'nullable|string|max:100',
                'is_active' => 'boolean',
                'is_featured' => 'boolean',
                'is_digital' => 'boolean',
                'is_virtual' => 'boolean',
                'manage_stock' => 'boolean',
                'stock_status' => 'required|in:in_stock,out_of_stock,pre_order',
                'meta_title' => 'nullable|string|max:255',
                'meta_description' => 'nullable|string|max:500',
                'tags' => 'nullable|array',
            ]);

            $product = Product::create($request->all());

            // Invalidar cache
            $this->cacheService->invalidateProductCache($product->id);

            return response()->json([
                'success' => true,
                'message' => 'Produto criado com sucesso',
                'data' => $product,
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
                'message' => 'Erro ao criar produto',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Atualizar produto (apenas admin).
     */
    public function update(Request $request, Product $product): JsonResponse
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'sku' => 'required|string|max:100|unique:products,sku,' . $product->id,
                'category_id' => 'required|exists:categories,id',
                'brand_id' => 'nullable|exists:brands,id',
                'price' => 'required|numeric|min:0',
                'compare_price' => 'nullable|numeric|min:0',
                'cost_price' => 'nullable|numeric|min:0',
                'stock_quantity' => 'required|integer|min:0',
                'min_stock_quantity' => 'nullable|integer|min:0',
                'description' => 'nullable|string',
                'short_description' => 'nullable|string|max:500',
                'weight' => 'nullable|numeric|min:0',
                'dimensions' => 'nullable|string|max:100',
                'is_active' => 'boolean',
                'is_featured' => 'boolean',
                'is_digital' => 'boolean',
                'is_virtual' => 'boolean',
                'manage_stock' => 'boolean',
                'stock_status' => 'required|in:in_stock,out_of_stock,pre_order',
                'meta_title' => 'nullable|string|max:255',
                'meta_description' => 'nullable|string|max:500',
                'tags' => 'nullable|array',
            ]);

            $product->update($request->all());

            // Invalidar cache
            $this->cacheService->invalidateProductCache($product->id);

            return response()->json([
                'success' => true,
                'message' => 'Produto atualizado com sucesso',
                'data' => $product->fresh(),
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
                'message' => 'Erro ao atualizar produto',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Excluir produto (apenas admin).
     */
    public function destroy(Product $product): JsonResponse
    {
        try {
            $productId = $product->id;
            $product->delete();

            // Invalidar cache
            $this->cacheService->invalidateProductCache($productId);

            return response()->json([
                'success' => true,
                'message' => 'Produto excluído com sucesso',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao excluir produto',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obter avaliações do produto.
     */
    public function reviews(Product $product, Request $request): JsonResponse
    {
        try {
            $request->validate([
                'page' => 'integer|min:1',
                'per_page' => 'integer|min:1|max:50',
            ]);

            $perPage = $request->get('per_page', 10);
            $reviews = $this->cacheService->getProductReviews($product->id);

            $paginatedReviews = $reviews->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $paginatedReviews->items(),
                'pagination' => [
                    'current_page' => $paginatedReviews->currentPage(),
                    'last_page' => $paginatedReviews->lastPage(),
                    'per_page' => $paginatedReviews->perPage(),
                    'total' => $paginatedReviews->total(),
                ],
                'summary' => [
                    'total_reviews' => $reviews->count(),
                    'average_rating' => $reviews->avg('rating') ?? 0,
                    'rating_distribution' => $this->getRatingDistribution($reviews),
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
     * Obter distribuição de avaliações.
     */
    protected function getRatingDistribution($reviews): array
    {
        $distribution = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];

        foreach ($reviews as $review) {
            $distribution[$review->rating]++;
        }

        return $distribution;
    }
}