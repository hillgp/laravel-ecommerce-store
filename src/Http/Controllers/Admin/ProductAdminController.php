<?php

namespace LaravelEcommerce\Store\Http\Controllers\Admin;

use LaravelEcommerce\Store\Models\Product;
use LaravelEcommerce\Store\Models\Category;
use LaravelEcommerce\Store\Models\Brand;
use LaravelEcommerce\Store\Services\ProductService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductAdminController extends \LaravelEcommerce\Store\Http\Controllers\Controller
{
    protected ProductService $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
        $this->middleware('auth');
        $this->middleware('role:admin');
    }

    /**
     * Lista de produtos.
     */
    public function index(Request $request): View
    {
        $query = Product::with(['category', 'brand', 'images', 'reviews']);

        // Filtros
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }

        if ($request->filled('brand')) {
            $query->where('brand_id', $request->brand);
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        if ($request->filled('stock')) {
            switch ($request->stock) {
                case 'in_stock':
                    $query->where('stock_quantity', '>', 0);
                    break;
                case 'low_stock':
                    $query->where('stock_quantity', '<=', 5)
                          ->where('stock_quantity', '>', 0);
                    break;
                case 'out_of_stock':
                    $query->where('stock_quantity', '<=', 0);
                    break;
            }
        }

        if ($request->filled('price_min')) {
            $query->where('price', '>=', $request->price_min);
        }

        if ($request->filled('price_max')) {
            $query->where('price', '<=', $request->price_max);
        }

        // Ordenação
        $sortBy = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');

        $query->orderBy($sortBy, $sortDirection);

        $products = $query->paginate(20);

        $categories = Category::where('is_active', true)->get();
        $brands = Brand::where('is_active', true)->get();

        return view('store::admin.products.index', compact(
            'products',
            'categories',
            'brands'
        ));
    }

    /**
     * Formulário de criação.
     */
    public function create(): View
    {
        $categories = Category::where('is_active', true)->get();
        $brands = Brand::where('is_active', true)->get();

        return view('store::admin.products.create', compact('categories', 'brands'));
    }

    /**
     * Salvar produto.
     */
    public function store(Request $request): RedirectResponse
    {
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
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        try {
            $product = $this->productService->createProduct($request->all());

            return redirect()->route('store.admin.products.show', $product)
                ->with('success', 'Produto criado com sucesso!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Erro ao criar produto: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Exibir produto.
     */
    public function show(Product $product): View
    {
        $product->load([
            'category',
            'brand',
            'images',
            'reviews' => function ($query) {
                $query->with('customer')->latest();
            },
            'variations',
            'relatedProducts'
        ]);

        // Estatísticas do produto
        $stats = [
            'total_reviews' => $product->reviews()->count(),
            'average_rating' => $product->reviews()->avg('rating') ?? 0,
            'total_sold' => $product->orderItems()->sum('quantity'),
            'total_views' => $product->views ?? 0,
        ];

        return view('store::admin.products.show', compact('product', 'stats'));
    }

    /**
     * Formulário de edição.
     */
    public function edit(Product $product): View
    {
        $categories = Category::where('is_active', true)->get();
        $brands = Brand::where('is_active', true)->get();

        return view('store::admin.products.edit', compact('product', 'categories', 'brands'));
    }

    /**
     * Atualizar produto.
     */
    public function update(Request $request, Product $product): RedirectResponse
    {
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

        try {
            $this->productService->updateProduct($product, $request->all());

            return redirect()->route('store.admin.products.show', $product)
                ->with('success', 'Produto atualizado com sucesso!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Erro ao atualizar produto: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Excluir produto.
     */
    public function destroy(Product $product): RedirectResponse
    {
        try {
            $this->productService->deleteProduct($product);

            return redirect()->route('store.admin.products.index')
                ->with('success', 'Produto excluído com sucesso!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Erro ao excluir produto: ' . $e->getMessage());
        }
    }

    /**
     * Upload de imagens.
     */
    public function uploadImages(Request $request, Product $product): JsonResponse
    {
        $request->validate([
            'images' => 'required|array|min:1|max:10',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        try {
            $uploadedImages = [];

            foreach ($request->file('images') as $image) {
                $filename = Str::slug($product->name) . '-' . time() . '-' . uniqid() . '.' . $image->getClientOriginalExtension();

                $path = $image->storeAs('products/' . $product->id, $filename, 'public');

                $productImage = $product->images()->create([
                    'path' => $path,
                    'filename' => $filename,
                    'mime_type' => $image->getMimeType(),
                    'size' => $image->getSize(),
                    'is_primary' => $product->images()->count() === 0, // Primeira imagem é primária
                ]);

                $uploadedImages[] = [
                    'id' => $productImage->id,
                    'path' => Storage::url($path),
                    'filename' => $filename,
                ];
            }

            return response()->json([
                'success' => true,
                'images' => $uploadedImages,
                'message' => count($uploadedImages) . ' imagens enviadas com sucesso!'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao enviar imagens: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Excluir imagem.
     */
    public function deleteImage(Product $product, $imageId): JsonResponse
    {
        try {
            $image = $product->images()->findOrFail($imageId);

            // Excluir arquivo físico
            Storage::disk('public')->delete($image->path);

            // Excluir registro
            $image->delete();

            return response()->json([
                'success' => true,
                'message' => 'Imagem excluída com sucesso!'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao excluir imagem: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Definir imagem primária.
     */
    public function setPrimaryImage(Product $product, $imageId): JsonResponse
    {
        try {
            // Remover primária de outras imagens
            $product->images()->update(['is_primary' => false]);

            // Definir nova imagem primária
            $image = $product->images()->findOrFail($imageId);
            $image->update(['is_primary' => true]);

            return response()->json([
                'success' => true,
                'message' => 'Imagem primária definida com sucesso!'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao definir imagem primária: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Duplicar produto.
     */
    public function duplicate(Product $product): RedirectResponse
    {
        try {
            $newProduct = $this->productService->duplicateProduct($product);

            return redirect()->route('store.admin.products.edit', $newProduct)
                ->with('success', 'Produto duplicado com sucesso!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Erro ao duplicar produto: ' . $e->getMessage());
        }
    }

    /**
     * Exportar produtos.
     */
    public function export(Request $request): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $format = $request->get('format', 'csv');

        // Implementar lógica de exportação
        $filename = 'produtos-' . date('Y-m-d-H-i-s') . '.' . $format;

        // Por enquanto, retorna CSV básico
        $products = Product::with(['category', 'brand'])->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($products) {
            $file = fopen('php://output', 'w');

            // Cabeçalho
            fputcsv($file, [
                'ID',
                'Nome',
                'SKU',
                'Categoria',
                'Marca',
                'Preço',
                'Estoque',
                'Status',
                'Criado em'
            ]);

            // Dados
            foreach ($products as $product) {
                fputcsv($file, [
                    $product->id,
                    $product->name,
                    $product->sku,
                    $product->category->name ?? '',
                    $product->brand->name ?? '',
                    $product->price,
                    $product->stock_quantity,
                    $product->is_active ? 'Ativo' : 'Inativo',
                    $product->created_at->format('d/m/Y H:i:s'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Importar produtos.
     */
    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,xlsx,xls|max:10240',
        ]);

        try {
            $imported = $this->productService->importProducts($request->file('file'));

            return redirect()->back()
                ->with('success', "{$imported} produtos importados com sucesso!");
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Erro ao importar produtos: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Atualizar estoque.
     */
    public function updateStock(Request $request, Product $product): RedirectResponse
    {
        $request->validate([
            'stock_quantity' => 'required|integer|min:0',
            'operation' => 'required|in:set,add,subtract',
            'reason' => 'nullable|string|max:255',
        ]);

        try {
            $oldStock = $product->stock_quantity;
            $newStock = $request->stock_quantity;
            $operation = $request->operation;

            switch ($operation) {
                case 'add':
                    $newStock = $oldStock + $request->stock_quantity;
                    break;
                case 'subtract':
                    $newStock = max(0, $oldStock - $request->stock_quantity);
                    break;
            }

            $product->update([
                'stock_quantity' => $newStock,
                'stock_status' => $newStock > 0 ? 'in_stock' : 'out_of_stock',
            ]);

            // Registrar movimentação de estoque
            $product->stockMovements()->create([
                'old_quantity' => $oldStock,
                'new_quantity' => $newStock,
                'quantity_changed' => $newStock - $oldStock,
                'reason' => $request->reason ?? 'Ajuste manual',
                'type' => $operation,
                'user_id' => auth()->id(),
            ]);

            return redirect()->back()
                ->with('success', 'Estoque atualizado com sucesso!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Erro ao atualizar estoque: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Produtos em promoção.
     */
    public function promotions(): View
    {
        $products = Product::where('compare_price', '>', 0)
            ->where('is_active', true)
            ->orderByRaw('(price / compare_price) ASC')
            ->paginate(20);

        return view('store::admin.products.promotions', compact('products'));
    }

    /**
     * Relatório de produtos.
     */
    public function reports(Request $request): View
    {
        $reportType = $request->get('type', 'inventory');

        switch ($reportType) {
            case 'sales':
                $products = Product::withCount('orderItems')
                    ->having('order_items_count', '>', 0)
                    ->orderBy('order_items_count', 'desc')
                    ->paginate(20);
                break;

            case 'low_stock':
                $products = Product::where('stock_quantity', '<=', 5)
                    ->where('stock_quantity', '>', 0)
                    ->orderBy('stock_quantity')
                    ->paginate(20);
                break;

            case 'no_stock':
                $products = Product::where('stock_quantity', '<=', 0)
                    ->orderBy('created_at', 'desc')
                    ->paginate(20);
                break;

            case 'inactive':
                $products = Product::where('is_active', false)
                    ->orderBy('created_at', 'desc')
                    ->paginate(20);
                break;

            default: // inventory
                $products = Product::orderBy('created_at', 'desc')->paginate(20);
        }

        return view('store::admin.products.reports', compact('products', 'reportType'));
    }
}