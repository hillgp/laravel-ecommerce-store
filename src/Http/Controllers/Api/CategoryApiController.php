<?php

namespace LaravelEcommerce\Store\Http\Controllers\Api;

use LaravelEcommerce\Store\Models\Category;
use LaravelEcommerce\Store\Services\CacheService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class CategoryApiController extends \LaravelEcommerce\Store\Http\Controllers\Controller
{
    protected CacheService $cacheService;

    public function __construct(CacheService $cacheService)
    {
        $this->cacheService = $cacheService;
        $this->middleware('auth:sanctum')->except(['index', 'show', 'tree']);
    }

    /**
     * Listar categorias.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'active' => 'boolean',
                'parent' => 'integer|exists:categories,id',
                'with_products' => 'boolean',
                'includes' => 'string',
            ]);

            $query = Category::query();

            if ($request->filled('active')) {
                $query->where('is_active', $request->active);
            }

            if ($request->filled('parent')) {
                $query->where('parent_id', $request->parent);
            }

            // Includes
            if ($request->filled('includes')) {
                $includes = explode(',', $request->includes);
                $query->with($includes);
            }

            if ($request->boolean('with_products')) {
                $query->withCount('products');
            }

            $categories = $query->orderBy('name')->get();

            return response()->json([
                'success' => true,
                'data' => $categories,
                'total' => $categories->count(),
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
     * Exibir categoria específica.
     */
    public function show(Category $category): JsonResponse
    {
        try {
            $category->load(['parent', 'children', 'products' => function ($query) {
                $query->where('is_active', true)->limit(12);
            }]);

            return response()->json([
                'success' => true,
                'data' => $category,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Categoria não encontrada',
            ], 404);
        }
    }

    /**
     * Obter árvore de categorias.
     */
    public function tree(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'active' => 'boolean',
            ]);

            $activeOnly = $request->boolean('active', true);

            $categories = $this->cacheService->getCategories($activeOnly);

            $tree = $this->buildCategoryTree($categories);

            return response()->json([
                'success' => true,
                'data' => $tree,
                'total' => $categories->count(),
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
                'message' => 'Erro ao buscar árvore de categorias',
            ], 500);
        }
    }

    /**
     * Criar categoria (apenas admin).
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'slug' => 'nullable|string|max:255|unique:categories,slug',
                'description' => 'nullable|string',
                'parent_id' => 'nullable|exists:categories,id',
                'image' => 'nullable|string|max:500',
                'icon' => 'nullable|string|max:100',
                'is_active' => 'boolean',
                'sort_order' => 'integer|min:0',
                'meta_title' => 'nullable|string|max:255',
                'meta_description' => 'nullable|string|max:500',
            ]);

            $data = $request->all();

            // Gerar slug se não fornecido
            if (empty($data['slug'])) {
                $data['slug'] = \Illuminate\Support\Str::slug($data['name']);
            }

            $category = Category::create($data);

            // Invalidar cache
            $this->cacheService->invalidateCategoryCache($category->id);

            return response()->json([
                'success' => true,
                'message' => 'Categoria criada com sucesso',
                'data' => $category,
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
                'message' => 'Erro ao criar categoria',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Atualizar categoria (apenas admin).
     */
    public function update(Request $request, Category $category): JsonResponse
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'slug' => 'nullable|string|max:255|unique:categories,slug,' . $category->id,
                'description' => 'nullable|string',
                'parent_id' => 'nullable|exists:categories,id',
                'image' => 'nullable|string|max:500',
                'icon' => 'nullable|string|max:100',
                'is_active' => 'boolean',
                'sort_order' => 'integer|min:0',
                'meta_title' => 'nullable|string|max:255',
                'meta_description' => 'nullable|string|max:500',
            ]);

            $data = $request->all();

            // Gerar slug se não fornecido
            if (empty($data['slug'])) {
                $data['slug'] = \Illuminate\Support\Str::slug($data['name']);
            }

            $category->update($data);

            // Invalidar cache
            $this->cacheService->invalidateCategoryCache($category->id);

            return response()->json([
                'success' => true,
                'message' => 'Categoria atualizada com sucesso',
                'data' => $category->fresh(),
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
                'message' => 'Erro ao atualizar categoria',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Excluir categoria (apenas admin).
     */
    public function destroy(Category $category): JsonResponse
    {
        try {
            // Verificar se tem produtos
            if ($category->products()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Não é possível excluir categoria com produtos',
                ], 422);
            }

            // Verificar se tem subcategorias
            if ($category->children()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Não é possível excluir categoria com subcategorias',
                ], 422);
            }

            $categoryId = $category->id;
            $category->delete();

            // Invalidar cache
            $this->cacheService->invalidateCategoryCache($categoryId);

            return response()->json([
                'success' => true,
                'message' => 'Categoria excluída com sucesso',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao excluir categoria',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obter produtos da categoria.
     */
    public function products(Category $category, Request $request): JsonResponse
    {
        try {
            $request->validate([
                'page' => 'integer|min:1',
                'per_page' => 'integer|min:1|max:100',
                'sort' => 'in:name,price,created_at,rating',
                'direction' => 'in:asc,desc',
                'includes' => 'string',
            ]);

            $query = $category->products()->where('is_active', true);

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
                'category' => $category->only(['id', 'name', 'slug']),
                'pagination' => [
                    'current_page' => $products->currentPage(),
                    'last_page' => $products->lastPage(),
                    'per_page' => $products->perPage(),
                    'total' => $products->total(),
                    'from' => $products->firstItem(),
                    'to' => $products->lastItem(),
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
                'message' => 'Erro ao buscar produtos da categoria',
            ], 500);
        }
    }

    /**
     * Construir árvore de categorias.
     */
    protected function buildCategoryTree($categories, $parentId = null): array
    {
        $tree = [];

        foreach ($categories as $category) {
            if ($category->parent_id == $parentId) {
                $children = $this->buildCategoryTree($categories, $category->id);

                $node = [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'description' => $category->description,
                    'image' => $category->image,
                    'icon' => $category->icon,
                    'is_active' => $category->is_active,
                    'sort_order' => $category->sort_order,
                    'products_count' => $category->products_count ?? 0,
                ];

                if (!empty($children)) {
                    $node['children'] = $children;
                }

                $tree[] = $node;
            }
        }

        return $tree;
    }
}