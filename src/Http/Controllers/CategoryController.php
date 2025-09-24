<?php

namespace LaravelEcommerce\Store\Http\Controllers;

use LaravelEcommerce\Store\Models\Category;
use LaravelEcommerce\Store\Services\ProductService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class CategoryController extends Controller
{
    protected ProductService $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    /**
     * Display a listing of categories.
     */
    public function index(): View
    {
        $categories = Category::roots()
            ->active()
            ->ordered()
            ->with(['children', 'activeProducts'])
            ->get();

        return view('store::categories.index', compact('categories'));
    }

    /**
     * Display the specified category.
     */
    public function show(Category $category, Request $request): View
    {
        if (!$category->is_active) {
            abort(404, 'Categoria não encontrada');
        }

        $filters = $request->only([
            'brand', 'price_min', 'price_max', 'rating',
            'featured', 'on_sale', 'new_arrival', 'in_stock', 'sort'
        ]);

        $products = $this->productService->getProductsByCategory($category, $filters, 12);
        $subcategories = $category->children()->active()->ordered()->get();

        return view('store::categories.show', compact('category', 'products', 'subcategories', 'filters'));
    }

    /**
     * Get category tree for navigation.
     */
    public function tree(): JsonResponse
    {
        $categories = Category::getTree();

        return response()->json([
            'success' => true,
            'categories' => $categories,
        ]);
    }

    /**
     * Get category dropdown options.
     */
    public function dropdown(): JsonResponse
    {
        $categories = Category::getTreeForDropdown();

        return response()->json([
            'success' => true,
            'categories' => $categories,
        ]);
    }

    /**
     * Get featured categories.
     */
    public function featured(): JsonResponse
    {
        $categories = Category::featured()
            ->active()
            ->ordered()
            ->with(['activeProducts'])
            ->get();

        return response()->json([
            'success' => true,
            'categories' => $categories->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'image' => $category->image_url,
                    'thumbnail' => $category->thumbnail_url,
                    'icon' => $category->icon,
                    'color' => $category->color,
                    'product_count' => $category->total_active_products,
                    'url' => $category->url,
                ];
            }),
        ]);
    }

    /**
     * Get category statistics.
     */
    public function stats(): JsonResponse
    {
        $stats = [
            'total_categories' => Category::count(),
            'active_categories' => Category::active()->count(),
            'featured_categories' => Category::featured()->count(),
            'root_categories' => Category::roots()->count(),
            'categories_with_products' => Category::has('products')->count(),
        ];

        return response()->json([
            'success' => true,
            'stats' => $stats,
        ]);
    }

    /**
     * Get category products count.
     */
    public function productCount(Category $category): JsonResponse
    {
        return response()->json([
            'success' => true,
            'count' => $category->total_active_products,
        ]);
    }

    /**
     * Get category breadcrumb.
     */
    public function breadcrumb(Category $category): JsonResponse
    {
        $breadcrumb = $category->breadcrumb;

        return response()->json([
            'success' => true,
            'breadcrumb' => $breadcrumb->map(function ($cat) {
                return [
                    'id' => $cat->id,
                    'name' => $cat->name,
                    'slug' => $cat->slug,
                    'url' => $cat->url,
                ];
            }),
        ]);
    }

    /**
     * Get category filters.
     */
    public function filters(Category $category): JsonResponse
    {
        $products = $category->activeProducts();

        $priceRange = [
            'min' => $products->min('price') ?? 0,
            'max' => $products->max('price') ?? 1000,
        ];

        $brands = $products->distinct('brand_id')->pluck('brand_id');
        $ratings = $products->distinct('rating')->pluck('rating');

        return response()->json([
            'success' => true,
            'filters' => [
                'price_range' => $priceRange,
                'brands' => $brands,
                'ratings' => $ratings,
            ],
        ]);
    }

    /**
     * Search categories.
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->get('q', '');

        $categories = Category::where('name', 'like', "%{$query}%")
            ->orWhere('description', 'like', "%{$query}%")
            ->active()
            ->ordered()
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'categories' => $categories->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'description' => $category->description,
                    'image' => $category->image_url,
                    'product_count' => $category->total_active_products,
                    'url' => $category->url,
                ];
            }),
        ]);
    }

    /**
     * Get category suggestions for autocomplete.
     */
    public function suggestions(Request $request): JsonResponse
    {
        $query = $request->get('q', '');

        if (strlen($query) < 2) {
            return response()->json(['suggestions' => []]);
        }

        $categories = Category::where('name', 'like', "%{$query}%")
            ->active()
            ->ordered()
            ->limit(5)
            ->get();

        $suggestions = $categories->map(function ($category) {
            return [
                'id' => $category->id,
                'name' => $category->name,
                'url' => $category->url,
                'product_count' => $category->total_active_products,
            ];
        });

        return response()->json(['suggestions' => $suggestions]);
    }

    /**
     * Get categories with products.
     */
    public function withProducts(): JsonResponse
    {
        $categories = Category::has('activeProducts')
            ->with(['activeProducts' => function ($query) {
                $query->limit(4);
            }])
            ->active()
            ->ordered()
            ->get();

        return response()->json([
            'success' => true,
            'categories' => $categories->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'image' => $category->image_url,
                    'url' => $category->url,
                    'product_count' => $category->total_active_products,
                    'products' => $category->activeProducts->map(function ($product) {
                        return [
                            'id' => $product->id,
                            'name' => $product->name,
                            'slug' => $product->slug,
                            'price' => $product->price,
                            'formatted_price' => $product->formatted_price,
                            'image' => $product->thumbnail,
                            'url' => $product->url,
                        ];
                    }),
                ];
            }),
        ]);
    }
}