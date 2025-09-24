<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use LaravelEcommerce\Store\Http\Controllers\ProductController;
use LaravelEcommerce\Store\Http\Controllers\CategoryController;
use LaravelEcommerce\Store\Http\Controllers\CartController;
use LaravelEcommerce\Store\Http\Controllers\CheckoutController;
use LaravelEcommerce\Store\Http\Controllers\CustomerController;

/*
|--------------------------------------------------------------------------
| Store API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "api" middleware group. Now create something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Public API Routes
Route::prefix('loja')->name('api.store.')->group(function () {

    // Products
    Route::prefix('produtos')->name('products.')->group(function () {
        Route::get('/', [ProductController::class, 'index'])->name('index');
        Route::get('/{product:slug}', [ProductController::class, 'show'])->name('show');
        Route::get('/categoria/{category:slug}', [ProductController::class, 'category'])->name('category');
        Route::get('/busca/sugestoes', [ProductController::class, 'suggestions'])->name('suggestions');
        Route::get('/busca/filtros', [ProductController::class, 'filters'])->name('filters');
        Route::get('/destaques', [ProductController::class, 'featured'])->name('featured');
        Route::get('/ofertas', [ProductController::class, 'onSale'])->name('sale');
        Route::get('/novidades', [ProductController::class, 'newArrivals'])->name('new');
        Route::get('/comparar', [ProductController::class, 'compare'])->name('compare');
        Route::get('/{product:slug}/visualizar', [ProductController::class, 'quickView'])->name('quickview');
        Route::get('/{product:slug}/relacionados', [ProductController::class, 'getRelatedProducts'])->name('related');
        Route::get('/{product:slug}/avaliacoes', [ProductController::class, 'getProductReviews'])->name('reviews');
    });

    // Categories
    Route::prefix('categorias')->name('categories.')->group(function () {
        Route::get('/', [CategoryController::class, 'index'])->name('index');
        Route::get('/{category:slug}', [CategoryController::class, 'show'])->name('show');
        Route::get('/arvore/navegacao', [CategoryController::class, 'tree'])->name('tree');
        Route::get('/lista/dropdown', [CategoryController::class, 'dropdown'])->name('dropdown');
        Route::get('/busca/sugestoes', [CategoryController::class, 'suggestions'])->name('suggestions');
        Route::get('/destaques', [CategoryController::class, 'featured'])->name('featured');
        Route::get('/com-produtos', [CategoryController::class, 'withProducts'])->name('withproducts');
        Route::get('/{category}/produtos', [CategoryController::class, 'productCount'])->name('productcount');
        Route::get('/{category}/breadcrumb', [CategoryController::class, 'breadcrumb'])->name('breadcrumb');
        Route::get('/{category}/filtros', [CategoryController::class, 'filters'])->name('filters');
    });

    // Cart
    Route::prefix('carrinho')->name('cart.')->group(function () {
        Route::get('/', [CartController::class, 'index'])->name('index');
        Route::post('/adicionar/{product}', [CartController::class, 'add'])->name('add');
        Route::post('/atualizar', [CartController::class, 'update'])->name('update');
        Route::post('/remover', [CartController::class, 'remove'])->name('remove');
        Route::post('/limpar', [CartController::class, 'clear'])->name('clear');
        Route::post('/aplicar-cupom', [CartController::class, 'applyCoupon'])->name('applycoupon');
        Route::post('/remover-cupom', [CartController::class, 'removeCoupon'])->name('removecoupon');
        Route::get('/resumo', [CartController::class, 'summary'])->name('summary');
        Route::get('/contador', [CartController::class, 'itemCount'])->name('count');
        Route::get('/frete', [CartController::class, 'shippingOptions'])->name('shipping');
        Route::get('/checkout', [CartController::class, 'checkout'])->name('checkout');
    });

    // Checkout
    Route::prefix('checkout')->name('checkout.')->group(function () {
        Route::get('/', [CheckoutController::class, 'index'])->name('index');
        Route::post('/processar', [CheckoutController::class, 'process'])->name('process');
        Route::get('/sucesso/{orderNumber}', [CheckoutController::class, 'success'])->name('success');
        Route::post('/validar', [CheckoutController::class, 'validateCheckout'])->name('validate');
        Route::get('/metodos-pagamento', [CheckoutController::class, 'paymentMethods'])->name('paymentmethods');
        Route::get('/metodos-frete', [CheckoutController::class, 'shippingMethods'])->name('shippingmethods');
        Route::post('/aplicar-cupom', [CheckoutController::class, 'applyCoupon'])->name('applycoupon');
        Route::get('/resumo', [CheckoutController::class, 'summary'])->name('summary');
        Route::post('/convidado', [CheckoutController::class, 'guestCheckout'])->name('guest');
        Route::post('/salvar-dados', [CheckoutController::class, 'saveCheckoutData'])->name('savedata');
        Route::get('/dados-salvos', [CheckoutController::class, 'getCheckoutData'])->name('getdata');
    });

    // Customer Authentication
    Route::prefix('cliente')->name('customer.')->group(function () {
        Route::post('/entrar', [CustomerController::class, 'authenticate'])->name('authenticate');
        Route::post('/cadastrar', [CustomerController::class, 'store'])->name('store');
        Route::post('/sair', [CustomerController::class, 'logout'])->name('logout');

        // Protected Customer Routes
        Route::middleware(['auth:sanctum'])->group(function () {
            Route::get('/perfil', [CustomerController::class, 'profile'])->name('profile');
            Route::post('/perfil', [CustomerController::class, 'updateProfile'])->name('updateprofile');
            Route::post('/alterar-senha', [CustomerController::class, 'changePassword'])->name('changepassword');
            Route::get('/pedidos', [CustomerController::class, 'orders'])->name('orders');
            Route::get('/pedido/{orderNumber}', [CustomerController::class, 'order'])->name('order');
            Route::get('/enderecos', [CustomerController::class, 'addresses'])->name('addresses');
            Route::get('/lista-desejos', [CustomerController::class, 'wishlist'])->name('wishlist');
            Route::post('/lista-desejos/adicionar/{product}', [CustomerController::class, 'addToWishlist'])->name('wishlist.add');
            Route::post('/lista-desejos/remover/{product}', [CustomerController::class, 'removeFromWishlist'])->name('wishlist.remove');
            Route::get('/avaliacoes', [CustomerController::class, 'reviews'])->name('reviews');
            Route::get('/estatisticas', [CustomerController::class, 'stats'])->name('stats');
        });
    });

    // Reviews
    Route::prefix('avaliacoes')->name('reviews.')->group(function () {
        Route::get('/produto/{product}', [ProductController::class, 'getProductReviews'])->name('product');
        Route::post('/produto/{product}', [CustomerController::class, 'createReview'])->name('create')->middleware('auth:sanctum');
        Route::put('/{review}', [CustomerController::class, 'updateReview'])->name('update')->middleware('auth:sanctum');
        Route::delete('/{review}', [CustomerController::class, 'deleteReview'])->name('delete')->middleware('auth:sanctum');
    });

    // Statistics and Analytics
    Route::prefix('estatisticas')->name('stats.')->group(function () {
        Route::get('/produtos', [ProductController::class, 'getProductStats'])->name('products');
        Route::get('/pedidos', [CheckoutController::class, 'getOrderStats'])->name('orders');
        Route::get('/clientes', [CustomerController::class, 'getCustomerStats'])->name('customers');
        Route::get('/cupons', [CartController::class, 'getCouponStats'])->name('coupons');
    });

    // Search
    Route::prefix('busca')->name('search.')->group(function () {
        Route::get('/produtos', [ProductController::class, 'search'])->name('products');
        Route::get('/categorias', [CategoryController::class, 'search'])->name('categories');
        Route::get('/sugestoes', [ProductController::class, 'suggestions'])->name('suggestions');
    });

    // Wishlist
    Route::prefix('lista-desejos')->name('wishlist.')->middleware('auth:sanctum')->group(function () {
        Route::get('/', [CustomerController::class, 'wishlist'])->name('index');
        Route::post('/adicionar/{product}', [CustomerController::class, 'addToWishlist'])->name('add');
        Route::post('/remover/{product}', [CustomerController::class, 'removeFromWishlist'])->name('remove');
    });

    // Notifications
    Route::prefix('notificacoes')->name('notifications.')->middleware('auth:sanctum')->group(function () {
        Route::get('/', [CustomerController::class, 'getNotifications'])->name('index');
        Route::post('/marcar-lida/{notification}', [CustomerController::class, 'markAsRead'])->name('markread');
        Route::post('/marcar-todas-lidas', [CustomerController::class, 'markAllAsRead'])->name('markallread');
    });

    // Payment Methods
    Route::prefix('pagamento')->name('payment.')->group(function () {
        Route::get('/metodos', [CheckoutController::class, 'paymentMethods'])->name('methods');
        Route::get('/metodos-disponiveis', [CheckoutController::class, 'getAvailablePaymentMethods'])->name('available');
        Route::post('/processar', [CheckoutController::class, 'processPayment'])->name('process')->middleware('auth:sanctum');
        Route::post('/reembolsar', [CheckoutController::class, 'refundPayment'])->name('refund')->middleware('auth:sanctum');
    });

    // Shipping
    Route::prefix('frete')->name('shipping.')->group(function () {
        Route::get('/metodos', [CartController::class, 'shippingOptions'])->name('methods');
        Route::post('/calcular', [CheckoutController::class, 'calculateShipping'])->name('calculate');
        Route::get('/rastreio/{order}', [CheckoutController::class, 'getTrackingInfo'])->name('tracking');
    });

    // Coupons
    Route::prefix('cupons')->name('coupons.')->group(function () {
        Route::post('/aplicar', [CartController::class, 'applyCoupon'])->name('apply');
        Route::post('/remover', [CartController::class, 'removeCoupon'])->name('remove');
        Route::get('/validar/{code}', [CartController::class, 'validateCoupon'])->name('validate');
    });

    // Store Information
    Route::prefix('info')->name('info.')->group(function () {
        Route::get('/configuracao', [ProductController::class, 'getStoreConfig'])->name('config');
        Route::get('/moeda', [ProductController::class, 'getCurrency'])->name('currency');
        Route::get('/idioma', [ProductController::class, 'getLocale'])->name('locale');
        Route::get('/manutencao', [ProductController::class, 'isMaintenanceMode'])->name('maintenance');
    });
});

// API Health Check
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toISOString(),
        'version' => '1.0.0',
        'environment' => app()->environment(),
    ]);
});

// API Documentation
Route::get('/docs', function () {
    return response()->json([
        'message' => 'API Documentation',
        'version' => '1.0.0',
        'endpoints' => [
            'products' => '/api/loja/produtos',
            'categories' => '/api/loja/categorias',
            'cart' => '/api/loja/carrinho',
            'checkout' => '/api/loja/checkout',
            'customer' => '/api/loja/cliente',
            'search' => '/api/loja/busca',
            'reviews' => '/api/loja/avaliacoes',
        ],
        'authentication' => 'Bearer Token (Sanctum)',
    ]);
});