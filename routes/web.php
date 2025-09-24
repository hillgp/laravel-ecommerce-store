<?php

use Illuminate\Support\Facades\Route;
use LaravelEcommerce\Store\Http\Controllers\ProductController;
use LaravelEcommerce\Store\Http\Controllers\CategoryController;
use LaravelEcommerce\Store\Http\Controllers\CartController;
use LaravelEcommerce\Store\Http\Controllers\CheckoutController;
use LaravelEcommerce\Store\Http\Controllers\CustomerController;
use LaravelEcommerce\Store\Http\Controllers\ComparisonController;

/*
|--------------------------------------------------------------------------
| Store Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Public Routes
Route::prefix('loja')->name('store.')->group(function () {

    // Home / Products
    Route::get('/', [ProductController::class, 'index'])->name('products.index');
    Route::get('/produtos', [ProductController::class, 'index'])->name('products.index');
    Route::get('/produto/{product:slug}', [ProductController::class, 'show'])->name('products.show');
    Route::get('/produtos/categoria/{category:slug}', [ProductController::class, 'category'])->name('categories.show');
    Route::get('/produtos/busca', [ProductController::class, 'search'])->name('products.search');
    Route::get('/produtos/destaques', [ProductController::class, 'featured'])->name('products.featured');
    Route::get('/produtos/ofertas', [ProductController::class, 'onSale'])->name('products.sale');
    Route::get('/produtos/novidades', [ProductController::class, 'newArrivals'])->name('products.new');
    Route::get('/produtos/comparar', [ProductController::class, 'compare'])->name('products.compare');
    Route::get('/produto/{product:slug}/visualizar', [ProductController::class, 'quickView'])->name('products.quickview');

    // Categories
    Route::get('/categorias', [CategoryController::class, 'index'])->name('categories.index');
    Route::get('/categorias/destaques', [CategoryController::class, 'featured'])->name('categories.featured');
    Route::get('/categorias/com-produtos', [CategoryController::class, 'withProducts'])->name('categories.withproducts');

    // Product Comparison
    Route::get('/comparacao', [ComparisonController::class, 'index'])->name('comparison.index');
    Route::get('/comparacao/compartilhada/{token}', [ComparisonController::class, 'showShared'])->name('comparison.shared');
    Route::post('/comparacao/adicionar/{product}', [ComparisonController::class, 'addProduct'])->name('comparison.add');
    Route::post('/comparacao/remover/{product}', [ComparisonController::class, 'removeProduct'])->name('comparison.remove');
    Route::post('/comparacao/limpar', [ComparisonController::class, 'clear'])->name('comparison.clear');
    Route::post('/comparacao/compartilhar', [ComparisonController::class, 'share'])->name('comparison.share');
    Route::get('/comparacao/produtos', [ComparisonController::class, 'getProducts'])->name('comparison.products');
    Route::get('/comparacao/estatisticas', [ComparisonController::class, 'getStats'])->name('comparison.stats');
    Route::post('/comparacao/configuracoes', [ComparisonController::class, 'updateSettings'])->name('comparison.settings');
    Route::post('/comparacao/reordenar/{product}', [ComparisonController::class, 'reorderProduct'])->name('comparison.reorder');
    Route::post('/comparacao/nota/{product}', [ComparisonController::class, 'addNote'])->name('comparison.note');
    Route::get('/comparacao/dados', [ComparisonController::class, 'getComparisonData'])->name('comparison.data');

    // Cart
    Route::get('/carrinho', [CartController::class, 'index'])->name('cart.index');
    Route::post('/carrinho/adicionar/{product}', [CartController::class, 'add'])->name('cart.add');
    Route::post('/carrinho/atualizar', [CartController::class, 'update'])->name('cart.update');
    Route::post('/carrinho/remover', [CartController::class, 'remove'])->name('cart.remove');
    Route::post('/carrinho/limpar', [CartController::class, 'clear'])->name('cart.clear');
    Route::post('/carrinho/aplicar-cupom', [CartController::class, 'applyCoupon'])->name('cart.applycoupon');
    Route::post('/carrinho/remover-cupom', [CartController::class, 'removeCoupon'])->name('cart.removecoupon');
    Route::get('/carrinho/resumo', [CartController::class, 'summary'])->name('cart.summary');
    Route::get('/carrinho/frete', [CartController::class, 'shippingOptions'])->name('cart.shipping');
    Route::get('/carrinho/checkout', [CartController::class, 'checkout'])->name('cart.checkout');

    // Checkout
    Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
    Route::post('/checkout/processar', [CheckoutController::class, 'process'])->name('checkout.process');
    Route::get('/checkout/sucesso/{orderNumber}', [CheckoutController::class, 'success'])->name('checkout.success');
    Route::post('/checkout/validar', [CheckoutController::class, 'validateCheckout'])->name('checkout.validate');
    Route::get('/checkout/metodos-pagamento', [CheckoutController::class, 'paymentMethods'])->name('checkout.paymentmethods');
    Route::get('/checkout/metodos-frete', [CheckoutController::class, 'shippingMethods'])->name('checkout.shippingmethods');
    Route::post('/checkout/aplicar-cupom', [CheckoutController::class, 'applyCoupon'])->name('checkout.applycoupon');
    Route::get('/checkout/resumo', [CheckoutController::class, 'summary'])->name('checkout.summary');
    Route::post('/checkout/convidado', [CheckoutController::class, 'guestCheckout'])->name('checkout.guest');
    Route::post('/checkout/salvar-dados', [CheckoutController::class, 'saveCheckoutData'])->name('checkout.savedata');
    Route::get('/checkout/dados-salvos', [CheckoutController::class, 'getCheckoutData'])->name('checkout.getdata');

    // Customer Authentication
    Route::get('/entrar', [CustomerController::class, 'login'])->name('customer.login');
    Route::post('/entrar', [CustomerController::class, 'authenticate'])->name('customer.authenticate');
    Route::post('/sair', [CustomerController::class, 'logout'])->name('customer.logout');
    Route::get('/cadastrar', [CustomerController::class, 'create'])->name('customer.create');
    Route::post('/cadastrar', [CustomerController::class, 'store'])->name('customer.store');

    // Customer Dashboard (Protected Routes)
    Route::middleware(['auth:customer'])->group(function () {
        Route::get('/minha-conta', [CustomerController::class, 'dashboard'])->name('customer.dashboard');
        Route::get('/meu-perfil', [CustomerController::class, 'profile'])->name('customer.profile');
        Route::post('/meu-perfil', [CustomerController::class, 'updateProfile'])->name('customer.updateprofile');
        Route::post('/alterar-senha', [CustomerController::class, 'changePassword'])->name('customer.changepassword');
        Route::get('/meus-pedidos', [CustomerController::class, 'orders'])->name('customer.orders');
        Route::get('/pedido/{orderNumber}', [CustomerController::class, 'order'])->name('customer.order');
        Route::get('/meus-enderecos', [CustomerController::class, 'addresses'])->name('customer.addresses');
        Route::get('/lista-desejos', [CustomerController::class, 'wishlist'])->name('customer.wishlist');
        Route::post('/lista-desejos/adicionar/{product}', [CustomerController::class, 'addToWishlist'])->name('customer.wishlist.add');
        Route::post('/lista-desejos/remover/{product}', [CustomerController::class, 'removeFromWishlist'])->name('customer.wishlist.remove');
        Route::get('/minhas-avaliacoes', [CustomerController::class, 'reviews'])->name('customer.reviews');
        Route::get('/estatisticas', [CustomerController::class, 'stats'])->name('customer.stats');
    });

    // AJAX Routes
    Route::prefix('ajax')->name('ajax.')->group(function () {
        Route::get('/produtos/sugestoes', [ProductController::class, 'suggestions'])->name('products.suggestions');
        Route::get('/produtos/filtros', [ProductController::class, 'filters'])->name('products.filters');
        Route::get('/produtos/comparacao/status/{product}', [ProductController::class, 'isInComparison'])->name('products.comparison.status');
        Route::get('/produtos/comparacao/contador', [ProductController::class, 'comparisonCount'])->name('products.comparison.count');
        Route::post('/produtos/comparacao/toggle/{product}', [ProductController::class, 'toggleComparison'])->name('products.comparison.toggle');
        Route::get('/produtos/comparacao/status-multiplos', [ProductController::class, 'comparisonStatus'])->name('products.comparison.status.multiple');
        Route::get('/categorias/arvore', [CategoryController::class, 'tree'])->name('categories.tree');
        Route::get('/categorias/dropdown', [CategoryController::class, 'dropdown'])->name('categories.dropdown');
        Route::get('/categorias/sugestoes', [CategoryController::class, 'suggestions'])->name('categories.suggestions');
        Route::get('/carrinho/contador', [CartController::class, 'itemCount'])->name('cart.count');
        Route::get('/categorias/{category}/produtos', [CategoryController::class, 'productCount'])->name('categories.productcount');
        Route::get('/categorias/{category}/breadcrumb', [CategoryController::class, 'breadcrumb'])->name('categories.breadcrumb');
        Route::get('/categorias/{category}/filtros', [CategoryController::class, 'filters'])->name('categories.filters');
    });
});

// Legacy Routes (for backward compatibility)
Route::get('/produtos', function () {
    return redirect()->route('store.products.index');
});

Route::get('/carrinho', function () {
    return redirect()->route('store.cart.index');
});

Route::get('/checkout', function () {
    return redirect()->route('store.checkout.index');
});