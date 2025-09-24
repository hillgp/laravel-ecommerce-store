<?php

use Illuminate\Support\Facades\Route;
use LaravelEcommerce\Store\Http\Controllers\ReviewController;
use LaravelEcommerce\Store\Http\Controllers\CouponController;
use LaravelEcommerce\Store\Http\Controllers\NotificationController;
use LaravelEcommerce\Store\Http\Controllers\AdminController;
use LaravelEcommerce\Store\Http\Controllers\Admin\ProductAdminController;
use LaravelEcommerce\Store\Http\Controllers\Admin\OrderAdminController;
use LaravelEcommerce\Store\Http\Controllers\Admin\CustomerAdminController;
use LaravelEcommerce\Store\Http\Controllers\Admin\ReportController;

/*
|--------------------------------------------------------------------------
| Store Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group.
|
*/

// Rotas de avaliações
Route::prefix('reviews')->name('reviews.')->group(function () {
    // Rotas públicas
    Route::get('/product/{product}', [ReviewController::class, 'index'])->name('index');
    Route::get('/{review}', [ReviewController::class, 'show'])->name('show');

    // Rotas autenticadas
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::get('/create/{product}', [ReviewController::class, 'create'])->name('create');
        Route::post('/product/{product}', [ReviewController::class, 'store'])->name('store');
        Route::get('/{review}/edit', [ReviewController::class, 'edit'])->name('edit');
        Route::put('/{review}', [ReviewController::class, 'update'])->name('update');
        Route::delete('/{review}', [ReviewController::class, 'destroy'])->name('destroy');

        // Votação em avaliações
        Route::post('/{review}/vote', [ReviewController::class, 'vote'])->name('vote');
        Route::delete('/{review}/vote', [ReviewController::class, 'removeVote'])->name('vote.remove');
    });

    // Rotas administrativas
    Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
        Route::get('/pending', [ReviewController::class, 'pending'])->name('pending');
        Route::post('/{review}/approve', [ReviewController::class, 'approve'])->name('approve');
        Route::post('/{review}/reject', [ReviewController::class, 'reject'])->name('reject');
    });
});

// Rotas de cupons
Route::prefix('coupons')->name('coupons.')->group(function () {
    // Rotas públicas (para validação)
    Route::post('/apply', [CouponController::class, 'apply'])->name('apply');
    Route::post('/remove', [CouponController::class, 'remove'])->name('remove');
    Route::post('/validate', [CouponController::class, 'validateCoupon'])->name('validate');
    Route::get('/applicable', [CouponController::class, 'applicable'])->name('applicable');

    // Rotas autenticadas
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::get('/history', [CouponController::class, 'usageHistory'])->name('history');
    });

    // Rotas administrativas
    Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
        Route::get('/', [CouponController::class, 'index'])->name('index');
        Route::get('/create', [CouponController::class, 'create'])->name('create');
        Route::post('/', [CouponController::class, 'store'])->name('store');
        Route::get('/{coupon}', [CouponController::class, 'show'])->name('show');
        Route::get('/{coupon}/edit', [CouponController::class, 'edit'])->name('edit');
        Route::put('/{coupon}', [CouponController::class, 'update'])->name('update');
        Route::delete('/{coupon}', [CouponController::class, 'destroy'])->name('destroy');

        // Ações específicas
        Route::post('/{coupon}/activate', [CouponController::class, 'activate'])->name('activate');
        Route::post('/{coupon}/deactivate', [CouponController::class, 'deactivate'])->name('deactivate');
        Route::post('/{coupon}/duplicate', [CouponController::class, 'duplicate'])->name('duplicate');

        // Relatórios
        Route::get('/reports/usage', [CouponController::class, 'usageReport'])->name('reports.usage');
    });
});

// Rotas de API para avaliações
Route::prefix('api/reviews')->name('api.reviews.')->middleware('auth:sanctum')->group(function () {
    Route::get('/product/{product}', [ReviewController::class, 'index'])->name('index');
    Route::post('/product/{product}', [ReviewController::class, 'store'])->name('store');
    Route::get('/{review}', [ReviewController::class, 'show'])->name('show');
    Route::put('/{review}', [ReviewController::class, 'update'])->name('update');
    Route::delete('/{review}', [ReviewController::class, 'destroy'])->name('destroy');

    // Votação em avaliações
    Route::post('/{review}/vote', [ReviewController::class, 'vote'])->name('vote');
    Route::delete('/{review}/vote', [ReviewController::class, 'removeVote'])->name('vote.remove');
});

// Rotas de API para cupons
Route::prefix('api/coupons')->name('api.coupons.')->group(function () {
    // Rotas públicas
    Route::post('/apply', [CouponController::class, 'apply'])->name('apply');
    Route::post('/remove', [CouponController::class, 'remove'])->name('remove');
    Route::post('/validate', [CouponController::class, 'validateCoupon'])->name('validate');
    Route::get('/applicable', [CouponController::class, 'applicable'])->name('applicable');

    // Rotas autenticadas
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::get('/history', [CouponController::class, 'usageHistory'])->name('history');
    });

    // Rotas administrativas
    Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
        Route::get('/', [CouponController::class, 'index'])->name('index');
        Route::post('/', [CouponController::class, 'store'])->name('store');
        Route::get('/{coupon}', [CouponController::class, 'show'])->name('show');
        Route::put('/{coupon}', [CouponController::class, 'update'])->name('update');
        Route::delete('/{coupon}', [CouponController::class, 'destroy'])->name('destroy');

        // Ações específicas
        Route::post('/{coupon}/activate', [CouponController::class, 'activate'])->name('activate');
        Route::post('/{coupon}/deactivate', [CouponController::class, 'deactivate'])->name('deactivate');
        Route::post('/{coupon}/duplicate', [CouponController::class, 'duplicate'])->name('duplicate');

        // Relatórios
        Route::get('/reports/usage', [CouponController::class, 'usageReport'])->name('reports.usage');
    });
});

// Rotas administrativas
Route::prefix('admin')->name('admin.')->middleware('store.admin')->group(function () {
    // Dashboard
    Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/stats', [AdminController::class, 'stats'])->name('stats');

    // Configurações
    Route::get('/settings', [AdminController::class, 'settings'])->name('settings');
    Route::post('/settings', [AdminController::class, 'updateSettings'])->name('settings.update');

    // Usuários administradores
    Route::get('/users', [AdminController::class, 'users'])->name('users');

    // Logs do sistema
    Route::get('/logs', [AdminController::class, 'logs'])->name('logs');

    // Backup
    Route::get('/backup', [AdminController::class, 'backup'])->name('backup');
    Route::post('/backup', [AdminController::class, 'createBackup'])->name('backup.create');
    Route::get('/backup/{filename}', [AdminController::class, 'downloadBackup'])->name('backup.download');

    // Manutenção
    Route::get('/maintenance', [AdminController::class, 'maintenance'])->name('maintenance');
    Route::post('/maintenance/clear-cache', [AdminController::class, 'clearCache'])->name('maintenance.clear-cache');
    Route::post('/maintenance/optimize', [AdminController::class, 'optimize'])->name('maintenance.optimize');
    Route::get('/maintenance/health-check', [AdminController::class, 'healthCheck'])->name('maintenance.health-check');

    // Produtos
    Route::prefix('products')->name('products.')->group(function () {
        Route::get('/', [ProductAdminController::class, 'index'])->name('index');
        Route::get('/create', [ProductAdminController::class, 'create'])->name('create');
        Route::post('/', [ProductAdminController::class, 'store'])->name('store');
        Route::get('/{product}', [ProductAdminController::class, 'show'])->name('show');
        Route::get('/{product}/edit', [ProductAdminController::class, 'edit'])->name('edit');
        Route::put('/{product}', [ProductAdminController::class, 'update'])->name('update');
        Route::delete('/{product}', [ProductAdminController::class, 'destroy'])->name('destroy');

        // Ações específicas
        Route::post('/{product}/duplicate', [ProductAdminController::class, 'duplicate'])->name('duplicate');
        Route::post('/{product}/update-stock', [ProductAdminController::class, 'updateStock'])->name('update-stock');

        // Upload de imagens
        Route::post('/{product}/images', [ProductAdminController::class, 'uploadImages'])->name('upload-images');
        Route::delete('/{product}/images/{imageId}', [ProductAdminController::class, 'deleteImage'])->name('delete-image');
        Route::post('/{product}/images/{imageId}/primary', [ProductAdminController::class, 'setPrimaryImage'])->name('set-primary-image');

        // Export/Import
        Route::get('/export', [ProductAdminController::class, 'export'])->name('export');
        Route::post('/import', [ProductAdminController::class, 'import'])->name('import');

        // Promoções
        Route::get('/promotions', [ProductAdminController::class, 'promotions'])->name('promotions');

        // Relatórios
        Route::get('/reports', [ProductAdminController::class, 'reports'])->name('reports');
    });

    // Pedidos
    Route::prefix('orders')->name('orders.')->group(function () {
        Route::get('/', [OrderAdminController::class, 'index'])->name('index');
        Route::get('/{order}', [OrderAdminController::class, 'show'])->name('show');
        Route::put('/{order}/status', [OrderAdminController::class, 'updateStatus'])->name('update-status');
        Route::post('/{order}/comment', [OrderAdminController::class, 'addComment'])->name('add-comment');
        Route::put('/{order}/payment', [OrderAdminController::class, 'updatePayment'])->name('update-payment');
        Route::put('/{order}/shipping', [OrderAdminController::class, 'updateShipping'])->name('update-shipping');
        Route::post('/{order}/refund', [OrderAdminController::class, 'processRefund'])->name('process-refund');
        Route::post('/{order}/cancel', [OrderAdminController::class, 'cancel'])->name('cancel');

        // Imprimir
        Route::get('/{order}/print', [OrderAdminController::class, 'print'])->name('print');

        // Export
        Route::get('/export', [OrderAdminController::class, 'export'])->name('export');

        // Relatórios
        Route::get('/reports', [OrderAdminController::class, 'reports'])->name('reports');

        // Estatísticas
        Route::get('/stats', [OrderAdminController::class, 'stats'])->name('stats');
    });

    // Clientes
    Route::prefix('customers')->name('customers.')->group(function () {
        Route::get('/', [CustomerAdminController::class, 'index'])->name('index');
        Route::get('/create', [CustomerAdminController::class, 'create'])->name('create');
        Route::post('/', [CustomerAdminController::class, 'store'])->name('store');
        Route::get('/{customer}', [CustomerAdminController::class, 'show'])->name('show');
        Route::get('/{customer}/edit', [CustomerAdminController::class, 'edit'])->name('edit');
        Route::put('/{customer}', [CustomerAdminController::class, 'update'])->name('update');
        Route::delete('/{customer}', [CustomerAdminController::class, 'destroy'])->name('destroy');

        // Ações específicas
        Route::post('/{customer}/toggle-status', [CustomerAdminController::class, 'toggleStatus'])->name('toggle-status');
        Route::post('/{customer}/verify-email', [CustomerAdminController::class, 'verifyEmail'])->name('verify-email');
        Route::post('/{customer}/password-reset', [CustomerAdminController::class, 'sendPasswordReset'])->name('password-reset');

        // Histórico de pedidos
        Route::get('/{customer}/orders', [CustomerAdminController::class, 'orderHistory'])->name('order-history');

        // Endereços
        Route::post('/{customer}/addresses', [CustomerAdminController::class, 'addAddress'])->name('add-address');
        Route::put('/{customer}/addresses/{addressId}', [CustomerAdminController::class, 'updateAddress'])->name('update-address');
        Route::delete('/{customer}/addresses/{addressId}', [CustomerAdminController::class, 'deleteAddress'])->name('delete-address');

        // Export
        Route::get('/export', [CustomerAdminController::class, 'export'])->name('export');

        // Relatórios
        Route::get('/reports', [CustomerAdminController::class, 'reports'])->name('reports');

        // Estatísticas
        Route::get('/stats', [CustomerAdminController::class, 'stats'])->name('stats');
    });

    // Relatórios e Analytics
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [ReportController::class, 'dashboard'])->name('dashboard');
        Route::get('/sales', [ReportController::class, 'sales'])->name('sales');
        Route::get('/products', [ReportController::class, 'products'])->name('products');
        Route::get('/categories', [ReportController::class, 'categories'])->name('categories');
        Route::get('/customers', [ReportController::class, 'customers'])->name('customers');
        Route::get('/inventory', [ReportController::class, 'inventory'])->name('inventory');
        Route::get('/financial', [ReportController::class, 'financial'])->name('financial');
        Route::get('/performance', [ReportController::class, 'performance'])->name('performance');
        Route::get('/custom', [ReportController::class, 'custom'])->name('custom');
        Route::post('/custom', [ReportController::class, 'custom'])->name('custom.generate');

        // Export
        Route::get('/export', [ReportController::class, 'export'])->name('export');

        // API para dados em tempo real
        Route::get('/realtime/{type}', [ReportController::class, 'realtimeData'])->name('realtime');
        Route::get('/quick-stats', [ReportController::class, 'quickStats'])->name('quick-stats');
    });
});

// Rotas de API administrativa para avaliações
Route::prefix('api/admin/reviews')->name('api.admin.reviews.')->middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::get('/pending', [ReviewController::class, 'pending'])->name('pending');
    Route::post('/{review}/approve', [ReviewController::class, 'approve'])->name('approve');
    Route::post('/{review}/reject', [ReviewController::class, 'reject'])->name('reject');
});

// Rotas de notificações
Route::prefix('notifications')->name('notifications.')->middleware(['auth:sanctum', 'role:admin'])->group(function () {
    // Rotas principais
    Route::get('/', [NotificationController::class, 'index'])->name('index');
    Route::get('/create', [NotificationController::class, 'create'])->name('create');
    Route::post('/', [NotificationController::class, 'store'])->name('store');
    Route::get('/{notification}', [NotificationController::class, 'show'])->name('show');
    Route::delete('/{notification}', [NotificationController::class, 'destroy'])->name('destroy');

    // Ações específicas
    Route::post('/{notification}/retry', [NotificationController::class, 'retry'])->name('retry');
    Route::post('/{notification}/cancel', [NotificationController::class, 'cancel'])->name('cancel');
    Route::post('/process-pending', [NotificationController::class, 'processPending'])->name('process-pending');
    Route::post('/retry-failed', [NotificationController::class, 'retryFailed'])->name('retry-failed');
    Route::post('/send-test', [NotificationController::class, 'sendTest'])->name('send-test');
    Route::post('/cleanup', [NotificationController::class, 'cleanup'])->name('cleanup');

    // Templates
    Route::prefix('templates')->name('templates.')->group(function () {
        Route::get('/', [NotificationController::class, 'templates'])->name('index');
        Route::get('/create', [NotificationController::class, 'createTemplate'])->name('create');
        Route::post('/', [NotificationController::class, 'storeTemplate'])->name('store');
        Route::get('/{template}/edit', [NotificationController::class, 'editTemplate'])->name('edit');
        Route::put('/{template}', [NotificationController::class, 'updateTemplate'])->name('update');
        Route::delete('/{template}', [NotificationController::class, 'destroyTemplate'])->name('destroy');
    });

    // Configurações
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [NotificationController::class, 'settings'])->name('index');
        Route::post('/', [NotificationController::class, 'updateSettings'])->name('update');
    });

    // API
    Route::get('/stats', [NotificationController::class, 'stats'])->name('stats');
});

// Rotas de API para notificações
Route::prefix('api/notifications')->name('api.notifications.')->group(function () {
    // Rotas públicas
    Route::get('/stats', [NotificationController::class, 'stats'])->name('stats');

    // Rotas autenticadas
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::post('/', [NotificationController::class, 'store'])->name('store');
        Route::get('/{notification}', [NotificationController::class, 'show'])->name('show');
        Route::delete('/{notification}', [NotificationController::class, 'destroy'])->name('destroy');

        // Ações específicas
        Route::post('/{notification}/retry', [NotificationController::class, 'retry'])->name('retry');
        Route::post('/{notification}/cancel', [NotificationController::class, 'cancel'])->name('cancel');
        Route::post('/process-pending', [NotificationController::class, 'processPending'])->name('process-pending');
        Route::post('/retry-failed', [NotificationController::class, 'retryFailed'])->name('retry-failed');
        Route::post('/send-test', [NotificationController::class, 'sendTest'])->name('send-test');
    });

    // Rotas administrativas
    Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
        Route::post('/cleanup', [NotificationController::class, 'cleanup'])->name('cleanup');

        // Templates
        Route::prefix('templates')->name('templates.')->group(function () {
            Route::get('/', [NotificationController::class, 'templates'])->name('index');
            Route::post('/', [NotificationController::class, 'storeTemplate'])->name('store');
            Route::put('/{template}', [NotificationController::class, 'updateTemplate'])->name('update');
            Route::delete('/{template}', [NotificationController::class, 'destroyTemplate'])->name('destroy');
        });

        // Configurações
        Route::prefix('settings')->name('settings.')->group(function () {
            Route::get('/', [NotificationController::class, 'settings'])->name('index');
            Route::post('/', [NotificationController::class, 'updateSettings'])->name('update');
        });
    });
});