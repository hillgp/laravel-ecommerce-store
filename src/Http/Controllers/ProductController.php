<?php

namespace LaravelEcommerce\Store\Http\Controllers;

use LaravelEcommerce\Store\Models\Product;
use LaravelEcommerce\Store\Models\Category;
use LaravelEcommerce\Store\Services\ProductService;
use LaravelEcommerce\Store\Services\CartService;
use SupernovaCorp\LaravelEcommerceStore\Models\ProductComparison;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class ProductController extends Controller
{
    protected ProductService $productService;
    protected CartService $cartService;

    public function __construct(ProductService $productService, CartService $cartService)
    {
        $this->productService = $productService;
        $this->cartService = $cartService;
    }

    /**
     * Display a listing of products.
     */
    public function index(Request $request): View
    {
        $filters = $request->only([
            'category', 'brand', 'price_min', 'price_max',
            'rating', 'featured', 'on_sale', 'new_arrival', 'in_stock', 'sort'
        ]);

        $products = $this->productService->getAllProducts($filters, 12);
        $categories = Category::active()->ordered()->get();

        return view('store::products.index', compact('products', 'categories', 'filters'));
    }

    /**
     * Display products by category.
     */
    public function category(Category $category, Request $request): View
    {
        $filters = $request->only([
            'brand', 'price_min', 'price_max', 'rating',
            'featured', 'on_sale', 'new_arrival', 'in_stock', 'sort'
        ]);

        $products = $this->productService->getProductsByCategory($category, $filters, 12);

        return view('store::products.category', compact('category', 'products', 'filters'));
    }

    /**
     * Display the specified product.
     */
    public function show(Product $product): View
    {
        // Increment view count
        $product->incrementViewCount();

        $relatedProducts = $this->productService->getRelatedProducts($product, 4);
        $crossSellProducts = $this->productService->getCrossSellProducts($product, 4);
        $upSellProducts = $this->productService->getUpSellProducts($product, 4);

        return view('store::products.show', compact(
            'product',
            'relatedProducts',
            'crossSellProducts',
            'upSellProducts'
        ));
    }

    /**
     * Search products.
     */
    public function search(Request $request): View
    {
        $search = $request->get('q');
        $filters = $request->except('q');

        if (empty($search)) {
            return redirect()->route('store.products.index');
        }

        $products = $this->productService->searchProducts($search, $filters, 12);
        $categories = Category::active()->ordered()->get();

        return view('store::products.search', compact('products', 'categories', 'search', 'filters'));
    }

    /**
     * Add product to cart via AJAX.
     */
    public function addToCart(Request $request, Product $product): JsonResponse
    {
        $quantity = $request->get('quantity', 1);
        $options = $request->get('options', []);

        // Validate product can be added to cart
        if (!$this->cartService->canAddToCart($product, $quantity)) {
            return response()->json([
                'success' => false,
                'message' => 'Produto não pode ser adicionado ao carrinho',
            ], 400);
        }

        try {
            $cartItem = $this->cartService->addItem($product, $quantity, $options);
            $cartSummary = $this->cartService->getSummary();

            return response()->json([
                'success' => true,
                'message' => 'Produto adicionado ao carrinho',
                'cart' => $cartSummary,
                'item' => $cartItem,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao adicionar produto ao carrinho',
            ], 500);
        }
    }

    /**
     * Get product quick view.
     */
    public function quickView(Product $product): JsonResponse
    {
        return response()->json([
            'success' => true,
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'price' => $product->price,
                'formatted_price' => $product->formatted_price,
                'compare_price' => $product->compare_price,
                'formatted_compare_price' => $product->formatted_compare_price,
                'discount_percentage' => $product->discount_percentage,
                'main_image' => $product->main_image,
                'thumbnail' => $product->thumbnail,
                'is_in_stock' => $product->is_in_stock,
                'availability_label' => $product->availability_label,
                'short_description' => $product->short_description,
                'url' => $product->url,
            ],
        ]);
    }

    /**
     * Get featured products.
     */
    public function featured(): JsonResponse
    {
        $products = $this->productService->getFeaturedProducts(8);

        return response()->json([
            'success' => true,
            'products' => $products->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'slug' => $product->slug,
                    'price' => $product->price,
                    'formatted_price' => $product->formatted_price,
                    'main_image' => $product->main_image,
                    'thumbnail' => $product->thumbnail,
                    'url' => $product->url,
                ];
            }),
        ]);
    }

    /**
     * Get sale products.
     */
    public function onSale(): JsonResponse
    {
        $products = $this->productService->getSaleProducts(8);

        return response()->json([
            'success' => true,
            'products' => $products->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'slug' => $product->slug,
                    'price' => $product->price,
                    'formatted_price' => $product->formatted_price,
                    'compare_price' => $product->compare_price,
                    'formatted_compare_price' => $product->formatted_compare_price,
                    'discount_percentage' => $product->discount_percentage,
                    'main_image' => $product->main_image,
                    'thumbnail' => $product->thumbnail,
                    'url' => $product->url,
                ];
            }),
        ]);
    }

    /**
     * Get new arrivals.
     */
    public function newArrivals(): JsonResponse
    {
        $products = $this->productService->getNewArrivals(8);

        return response()->json([
            'success' => true,
            'products' => $products->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'slug' => $product->slug,
                    'price' => $product->price,
                    'formatted_price' => $product->formatted_price,
                    'main_image' => $product->main_image,
                    'thumbnail' => $product->thumbnail,
                    'url' => $product->url,
                ];
            }),
        ]);
    }

    /**
     * Compare products.
     */
    public function compare(Request $request): View|RedirectResponse
    {
        $productIds = $request->get('products', []);

        if (empty($productIds)) {
            return redirect()->back()->with('error', 'Nenhum produto selecionado para comparação');
        }

        $products = Product::whereIn('id', $productIds)
            ->active()
            ->with(['category', 'images'])
            ->get();

        if ($products->count() !== count($productIds)) {
            return redirect()->back()->with('error', 'Alguns produtos não foram encontrados');
        }

        return view('store::products.compare', compact('products'));
    }

    /**
     * Get product suggestions.
     */
    public function suggestions(Request $request): JsonResponse
    {
        $query = $request->get('q');

        if (strlen($query) < 2) {
            return response()->json(['suggestions' => []]);
        }

        $products = Product::search($query)
            ->active()
            ->inStock()
            ->limit(5)
            ->get();

        $suggestions = $products->map(function ($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'price' => $product->formatted_price,
                'image' => $product->thumbnail,
                'url' => $product->url,
            ];
        });

        return response()->json(['suggestions' => $suggestions]);
    }

    /**
     * Get product filters.
     */
    public function filters(Request $request): JsonResponse
    {
        $categoryId = $request->get('category_id');

        $query = Product::active()->inStock();

        if ($categoryId) {
            $query->byCategory($categoryId);
        }

        $priceRange = [
            'min' => $query->min('price') ?? 0,
            'max' => $query->max('price') ?? 1000,
        ];

        $brands = $query->distinct('brand_id')->pluck('brand_id');
        $ratings = $query->distinct('rating')->pluck('rating');

        return response()->json([
            'price_range' => $priceRange,
            'brands' => $brands,
            'ratings' => $ratings,
        ]);
    }

    /**
     * Check if product is in comparison.
     */
    public function isInComparison(Product $product): JsonResponse
    {
        $comparison = ProductComparison::getCurrent();
        $isInComparison = $comparison->hasProduct($product->id);

        return response()->json([
            'in_comparison' => $isInComparison,
            'comparison_count' => $comparison->products_count,
            'can_add_more' => $comparison->canAddMoreProducts(),
        ]);
    }

    /**
     * Get comparison count.
     */
    public function comparisonCount(): JsonResponse
    {
        $comparison = ProductComparison::getCurrent();

        return response()->json([
            'count' => $comparison->products_count,
            'can_add_more' => $comparison->canAddMoreProducts(),
        ]);
    }

    /**
     * Toggle product in comparison.
     */
    public function toggleComparison(Product $product): JsonResponse
    {
        $comparison = ProductComparison::getCurrent();

        if ($comparison->hasProduct($product->id)) {
            $comparison->removeProduct($product->id);
            $message = 'Produto removido da comparação';
            $added = false;
        } else {
            if (!$comparison->canAddMoreProducts()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Limite máximo de produtos para comparação atingido',
                ], 400);
            }

            $comparison->addProduct($product->id);
            $message = 'Produto adicionado à comparação';
            $added = true;
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'added' => $added,
            'comparison_count' => $comparison->products_count,
            'can_add_more' => $comparison->canAddMoreProducts(),
        ]);
    }

    /**
     * Get comparison status for multiple products.
     */
    public function comparisonStatus(Request $request): JsonResponse
    {
        $productIds = $request->get('product_ids', []);
        $comparison = ProductComparison::getCurrent();

        $status = [];
        foreach ($productIds as $productId) {
            $status[$productId] = $comparison->hasProduct($productId);
        }

        return response()->json([
            'status' => $status,
            'comparison_count' => $comparison->products_count,
            'can_add_more' => $comparison->canAddMoreProducts(),
        ]);
    }
}