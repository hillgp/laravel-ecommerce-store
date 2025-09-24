<?php

use Illuminate\Support\Facades\Route;
use LaravelEcommerce\Store\Http\Controllers\Api\ProductApiController;
use LaravelEcommerce\Store\Http\Controllers\Api\CategoryApiController;
use LaravelEcommerce\Store\Http\Controllers\Api\CartApiController;
use LaravelEcommerce\Store\Http\Controllers\Api\OrderApiController;
use LaravelEcommerce\Store\Http\Controllers\Api\ReviewApiController;

/*
|--------------------------------------------------------------------------
| Store API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group.
|
*/

// Rotas públicas (sem autenticação)
Route::prefix('v1')->name('api.')->group(function () {

    // Produtos
    Route::prefix('products')->name('products.')->group(function () {
        Route::get('/', [ProductApiController::class, 'index'])->name('index');
        Route::get('/{product}', [ProductApiController::class, 'show'])->name('show');
        Route::get('/{product}/reviews', [ProductApiController::class, 'reviews'])->name('reviews');
        Route::get('/search', [ProductApiController::class, 'search'])->name('search');
        Route::get('/featured', [ProductApiController::class, 'featured'])->name('featured');
        Route::get('/category/{category}', [ProductApiController::class, 'byCategory'])->name('by-category');
        Route::get('/brand/{brand}', [ProductApiController::class, 'byBrand'])->name('by-brand');
    });

    // Categorias
    Route::prefix('categories')->name('categories.')->group(function () {
        Route::get('/', [CategoryApiController::class, 'index'])->name('index');
        Route::get('/{category}', [CategoryApiController::class, 'show'])->name('show');
        Route::get('/{category}/tree', [CategoryApiController::class, 'tree'])->name('tree');
        Route::get('/{category}/products', [CategoryApiController::class, 'products'])->name('products');
    });

    // Carrinho (usuário convidado)
    Route::prefix('cart')->name('cart.')->group(function () {
        Route::get('/', [CartApiController::class, 'index'])->name('index');
        Route::post('/', [CartApiController::class, 'store'])->name('store');
        Route::put('/{cartItem}', [CartApiController::class, 'update'])->name('update');
        Route::delete('/{cartItem}', [CartApiController::class, 'destroy'])->name('destroy');
        Route::delete('/', [CartApiController::class, 'clear'])->name('clear');
        Route::post('/apply-coupon', [CartApiController::class, 'applyCoupon'])->name('apply-coupon');
        Route::delete('/remove-coupon', [CartApiController::class, 'removeCoupon'])->name('remove-coupon');
    });

    // Autenticação
    Route::prefix('auth')->name('auth.')->group(function () {
        Route::post('/login', function (Request $request) {
            // Implementar lógica de login
            return response()->json(['message' => 'Login endpoint']);
        })->name('login');

        Route::post('/register', function (Request $request) {
            // Implementar lógica de registro
            return response()->json(['message' => 'Register endpoint']);
        })->name('register');

        Route::post('/forgot-password', function (Request $request) {
            // Implementar lógica de recuperação de senha
            return response()->json(['message' => 'Forgot password endpoint']);
        })->name('forgot-password');
    });
});

// Rotas autenticadas
Route::prefix('v1')->name('api.')->middleware('auth:sanctum')->group(function () {

    // Produtos (admin)
    Route::prefix('admin/products')->name('admin.products.')->group(function () {
        Route::post('/', [ProductApiController::class, 'store'])->name('store');
        Route::put('/{product}', [ProductApiController::class, 'update'])->name('update');
        Route::delete('/{product}', [ProductApiController::class, 'destroy'])->name('destroy');
    });

    // Categorias (admin)
    Route::prefix('admin/categories')->name('admin.categories.')->group(function () {
        Route::post('/', [CategoryApiController::class, 'store'])->name('store');
        Route::put('/{category}', [CategoryApiController::class, 'update'])->name('update');
        Route::delete('/{category}', [CategoryApiController::class, 'destroy'])->name('destroy');
    });

    // Carrinho (usuário autenticado)
    Route::prefix('cart')->name('cart.')->group(function () {
        Route::get('/', [CartApiController::class, 'index'])->name('index');
        Route::post('/', [CartApiController::class, 'store'])->name('store');
        Route::put('/{cartItem}', [CartApiController::class, 'update'])->name('update');
        Route::delete('/{cartItem}', [CartApiController::class, 'destroy'])->name('destroy');
        Route::delete('/', [CartApiController::class, 'clear'])->name('clear');
        Route::post('/apply-coupon', [CartApiController::class, 'applyCoupon'])->name('apply-coupon');
        Route::delete('/remove-coupon', [CartApiController::class, 'removeCoupon'])->name('remove-coupon');
    });

    // Pedidos
    Route::prefix('orders')->name('orders.')->group(function () {
        Route::get('/', [OrderApiController::class, 'index'])->name('index');
        Route::get('/{order}', [OrderApiController::class, 'show'])->name('show');
        Route::post('/', [OrderApiController::class, 'store'])->name('store');
        Route::post('/{order}/cancel', [OrderApiController::class, 'cancel'])->name('cancel');
        Route::get('/{order}/history', [OrderApiController::class, 'statusHistory'])->name('history');
        Route::get('/{order}/track', [OrderApiController::class, 'track'])->name('track');
    });

    // Avaliações
    Route::prefix('reviews')->name('reviews.')->group(function () {
        Route::get('/product/{product}', [ReviewApiController::class, 'index'])->name('index');
        Route::post('/', [ReviewApiController::class, 'store'])->name('store');
        Route::get('/{review}', [ReviewApiController::class, 'show'])->name('show');
        Route::put('/{review}', [ReviewApiController::class, 'update'])->name('update');
        Route::delete('/{review}', [ReviewApiController::class, 'destroy'])->name('destroy');
        Route::post('/{review}/vote', [ReviewApiController::class, 'vote'])->name('vote');
        Route::post('/{review}/report', [ReviewApiController::class, 'report'])->name('report');
        Route::get('/product/{product}/stats', [ReviewApiController::class, 'stats'])->name('stats');
    });

    // Perfil do usuário
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', function (Request $request) {
            return response()->json([
                'success' => true,
                'data' => $request->user(),
            ]);
        })->name('show');

        Route::put('/', function (Request $request) {
            // Implementar atualização de perfil
            return response()->json(['message' => 'Profile update endpoint']);
        })->name('update');

        Route::put('/password', function (Request $request) {
            // Implementar alteração de senha
            return response()->json(['message' => 'Password update endpoint']);
        })->name('password');

        Route::get('/orders', [OrderApiController::class, 'index'])->name('orders');
        Route::get('/reviews', function (Request $request) {
            // Implementar listagem de avaliações do usuário
            return response()->json(['message' => 'User reviews endpoint']);
        })->name('reviews');
    });

    // Wishlist
    Route::prefix('wishlist')->name('wishlist.')->group(function () {
        Route::get('/', function (Request $request) {
            // Implementar listagem da wishlist
            return response()->json(['message' => 'Wishlist index endpoint']);
        })->name('index');

        Route::post('/{product}', function (Request $request, $product) {
            // Implementar adicionar à wishlist
            return response()->json(['message' => 'Add to wishlist endpoint']);
        })->name('store');

        Route::delete('/{product}', function (Request $request, $product) {
            // Implementar remover da wishlist
            return response()->json(['message' => 'Remove from wishlist endpoint']);
        })->name('destroy');
    });

    // Notificações
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', function (Request $request) {
            // Implementar listagem de notificações
            return response()->json(['message' => 'Notifications index endpoint']);
        })->name('index');

        Route::put('/{notification}/read', function (Request $request, $notification) {
            // Implementar marcar como lida
            return response()->json(['message' => 'Mark as read endpoint']);
        })->name('read');

        Route::delete('/{notification}', function (Request $request, $notification) {
            // Implementar excluir notificação
            return response()->json(['message' => 'Delete notification endpoint']);
        })->name('destroy');
    });
});

// Rotas administrativas
Route::prefix('v1/admin')->name('api.admin.')->middleware(['auth:sanctum', 'role:admin'])->group(function () {

    // Dashboard
    Route::get('/dashboard', function (Request $request) {
        // Implementar dashboard admin
        return response()->json(['message' => 'Admin dashboard endpoint']);
    })->name('dashboard');

    // Relatórios
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/sales', function (Request $request) {
            // Implementar relatório de vendas
            return response()->json(['message' => 'Sales report endpoint']);
        })->name('sales');

        Route::get('/products', function (Request $request) {
            // Implementar relatório de produtos
            return response()->json(['message' => 'Products report endpoint']);
        })->name('products');

        Route::get('/customers', function (Request $request) {
            // Implementar relatório de clientes
            return response()->json(['message' => 'Customers report endpoint']);
        })->name('customers');

        Route::get('/export', function (Request $request) {
            // Implementar exportação de relatórios
            return response()->json(['message' => 'Export reports endpoint']);
        })->name('export');
    });

    // Configurações
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', function (Request $request) {
            // Implementar listagem de configurações
            return response()->json(['message' => 'Settings index endpoint']);
        })->name('index');

        Route::put('/', function (Request $request) {
            // Implementar atualização de configurações
            return response()->json(['message' => 'Update settings endpoint']);
        })->name('update');
    });

    // Usuários administradores
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', function (Request $request) {
            // Implementar listagem de usuários admin
            return response()->json(['message' => 'Admin users index endpoint']);
        })->name('index');

        Route::post('/', function (Request $request) {
            // Implementar criação de usuário admin
            return response()->json(['message' => 'Create admin user endpoint']);
        })->name('store');

        Route::put('/{user}', function (Request $request, $user) {
            // Implementar atualização de usuário admin
            return response()->json(['message' => 'Update admin user endpoint']);
        })->name('update');

        Route::delete('/{user}', function (Request $request, $user) {
            // Implementar exclusão de usuário admin
            return response()->json(['message' => 'Delete admin user endpoint']);
        })->name('destroy');
    });
});

// Health check
Route::get('/health', function () {
    return response()->json([
        'status' => 'healthy',
        'timestamp' => now()->toISOString(),
        'version' => '1.0.0',
    ]);
})->name('health');