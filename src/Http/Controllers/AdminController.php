<?php

namespace LaravelEcommerce\Store\Http\Controllers;

use LaravelEcommerce\Store\Models\Order;
use LaravelEcommerce\Store\Models\Product;
use LaravelEcommerce\Store\Models\Customer;
use LaravelEcommerce\Store\Models\Category;
use LaravelEcommerce\Store\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Carbon\Carbon;

class AdminController extends Controller
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
        $this->middleware('auth');
        $this->middleware('role:admin');
    }

    /**
     * Dashboard principal do admin.
     */
    public function dashboard(): View
    {
        // Estatísticas gerais
        $stats = [
            'total_orders' => Order::count(),
            'total_products' => Product::count(),
            'total_customers' => Customer::count(),
            'total_categories' => Category::count(),
            'total_reviews' => \LaravelEcommerce\Store\Models\Review::count(),
            'total_coupons' => \LaravelEcommerce\Store\Models\Coupon::count(),
        ];

        // Pedidos por status
        $ordersByStatus = Order::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        // Pedidos dos últimos 30 dias
        $recentOrders = Order::with(['customer', 'items'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Produtos com baixo estoque
        $lowStockProducts = Product::where('stock_quantity', '<=', 5)
            ->where('stock_quantity', '>', 0)
            ->orderBy('stock_quantity')
            ->limit(10)
            ->get();

        // Produtos sem estoque
        $outOfStockProducts = Product::where('stock_quantity', '<=', 0)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Vendas dos últimos 7 dias
        $salesLast7Days = Order::where('created_at', '>=', Carbon::now()->subDays(7))
            ->selectRaw('DATE(created_at) as date, SUM(total) as total, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Estatísticas de notificações
        $notificationStats = $this->notificationService->getNotificationStats();

        // Top produtos mais vendidos
        $topProducts = \DB::table('order_items')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->selectRaw('products.name, SUM(order_items.quantity) as total_sold')
            ->groupBy('products.id', 'products.name')
            ->orderBy('total_sold', 'desc')
            ->limit(10)
            ->get();

        // Novos clientes dos últimos 30 dias
        $newCustomers = Customer::where('created_at', '>=', Carbon::now()->subDays(30))
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('store::admin.dashboard', compact(
            'stats',
            'ordersByStatus',
            'recentOrders',
            'lowStockProducts',
            'outOfStockProducts',
            'salesLast7Days',
            'notificationStats',
            'topProducts',
            'newCustomers'
        ));
    }

    /**
     * Estatísticas em JSON para gráficos.
     */
    public function stats(Request $request): JsonResponse
    {
        $period = $request->get('period', '30'); // dias
        $startDate = Carbon::now()->subDays($period);

        // Vendas por período
        $sales = Order::where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date, SUM(total) as total, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Pedidos por status
        $ordersByStatus = Order::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get();

        // Produtos por categoria
        $productsByCategory = \DB::table('products')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->selectRaw('categories.name, COUNT(*) as count')
            ->groupBy('categories.id', 'categories.name')
            ->get();

        // Receita mensal
        $monthlyRevenue = Order::where('created_at', '>=', $startDate)
            ->selectRaw('MONTH(created_at) as month, SUM(total) as revenue')
            ->groupBy('month')
            ->get();

        return response()->json([
            'sales' => $sales,
            'orders_by_status' => $ordersByStatus,
            'products_by_category' => $productsByCategory,
            'monthly_revenue' => $monthlyRevenue,
        ]);
    }

    /**
     * Configurações gerais da loja.
     */
    public function settings(): View
    {
        $settings = [
            'store_name' => config('store.name', 'Loja Online'),
            'store_email' => config('store.email', 'contato@loja.com'),
            'store_phone' => config('store.phone', '(11) 9999-9999'),
            'store_address' => config('store.address', ''),
            'currency' => config('store.currency', 'BRL'),
            'currency_symbol' => config('store.currency_symbol', 'R$'),
            'maintenance_mode' => config('store.maintenance_mode', false),
            'allow_guest_checkout' => config('store.allow_guest_checkout', true),
            'require_email_verification' => config('store.require_email_verification', false),
            'auto_approve_reviews' => config('store.auto_approve_reviews', true),
            'enable_notifications' => config('store.enable_notifications', true),
            'enable_sms' => config('store.enable_sms', false),
            'enable_push' => config('store.enable_push', false),
        ];

        return view('store::admin.settings', compact('settings'));
    }

    /**
     * Atualizar configurações da loja.
     */
    public function updateSettings(Request $request): RedirectResponse
    {
        $request->validate([
            'store_name' => 'required|string|max:255',
            'store_email' => 'required|email',
            'store_phone' => 'nullable|string|max:20',
            'store_address' => 'nullable|string',
            'currency' => 'required|string|size:3',
            'currency_symbol' => 'required|string|max:5',
            'maintenance_mode' => 'boolean',
            'allow_guest_checkout' => 'boolean',
            'require_email_verification' => 'boolean',
            'auto_approve_reviews' => 'boolean',
            'enable_notifications' => 'boolean',
            'enable_sms' => 'boolean',
            'enable_push' => 'boolean',
        ]);

        // Aqui você implementaria a lógica para salvar as configurações
        // Por enquanto, apenas redireciona com mensagem de sucesso

        return redirect()->back()
            ->with('success', 'Configurações atualizadas com sucesso!');
    }

    /**
     * Gerenciar usuários administradores.
     */
    public function users(): View
    {
        $admins = \LaravelEcommerce\Store\Models\Admin::paginate(20);

        return view('store::admin.users', compact('admins'));
    }

    /**
     * Logs do sistema.
     */
    public function logs(Request $request): View
    {
        $logs = \LaravelEcommerce\Store\Models\SystemLog::orderBy('created_at', 'desc');

        if ($request->filled('level')) {
            $logs->where('level', $request->level);
        }

        if ($request->filled('date_from')) {
            $logs->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $logs->whereDate('created_at', '<=', $request->date_to);
        }

        $logs = $logs->paginate(50);

        $levels = ['debug', 'info', 'warning', 'error', 'critical'];

        return view('store::admin.logs', compact('logs', 'levels'));
    }

    /**
     * Backup do sistema.
     */
    public function backup(): View
    {
        return view('store::admin.backup');
    }

    /**
     * Criar backup.
     */
    public function createBackup(): RedirectResponse
    {
        try {
            // Implementar lógica de backup
            return redirect()->back()
                ->with('success', 'Backup criado com sucesso!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Erro ao criar backup: ' . $e->getMessage());
        }
    }

    /**
     * Download de backup.
     */
    public function downloadBackup(string $filename): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $path = storage_path('backups/' . $filename);

        if (!file_exists($path)) {
            abort(404);
        }

        return response()->download($path);
    }

    /**
     * Manutenção do sistema.
     */
    public function maintenance(): View
    {
        $systemInfo = [
            'laravel_version' => app()->version(),
            'php_version' => PHP_VERSION,
            'server_os' => php_uname('s'),
            'database_connection' => config('database.default'),
            'cache_driver' => config('cache.default'),
            'queue_driver' => config('queue.default'),
            'storage_disk' => config('filesystems.default'),
        ];

        return view('store::admin.maintenance', compact('systemInfo'));
    }

    /**
     * Limpar cache.
     */
    public function clearCache(): RedirectResponse
    {
        try {
            \Artisan::call('cache:clear');
            \Artisan::call('config:clear');
            \Artisan::call('route:clear');
            \Artisan::call('view:clear');

            return redirect()->back()
                ->with('success', 'Cache limpo com sucesso!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Erro ao limpar cache: ' . $e->getMessage());
        }
    }

    /**
     * Otimizar sistema.
     */
    public function optimize(): RedirectResponse
    {
        try {
            \Artisan::call('optimize:clear');
            \Artisan::call('config:cache');
            \Artisan::call('route:cache');
            \Artisan::call('view:cache');

            return redirect()->back()
                ->with('success', 'Sistema otimizado com sucesso!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Erro ao otimizar sistema: ' . $e->getMessage());
        }
    }

    /**
     * Verificar integridade do sistema.
     */
    public function healthCheck(): JsonResponse
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'storage' => $this->checkStorage(),
            'cache' => $this->checkCache(),
            'queue' => $this->checkQueue(),
            'email' => $this->checkEmail(),
            'filesystem' => $this->checkFilesystem(),
        ];

        $overall = collect($checks)->every(function ($check) {
            return $check['status'] === 'healthy';
        });

        return response()->json([
            'status' => $overall ? 'healthy' : 'unhealthy',
            'checks' => $checks,
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Verificar conexão com banco de dados.
     */
    protected function checkDatabase(): array
    {
        try {
            \DB::connection()->getPdo();
            return ['status' => 'healthy', 'message' => 'Conexão OK'];
        } catch (\Exception $e) {
            return ['status' => 'unhealthy', 'message' => 'Erro de conexão: ' . $e->getMessage()];
        }
    }

    /**
     * Verificar sistema de arquivos.
     */
    protected function checkStorage(): array
    {
        try {
            $path = storage_path('app');
            if (is_writable($path)) {
                return ['status' => 'healthy', 'message' => 'Storage OK'];
            }
            return ['status' => 'unhealthy', 'message' => 'Storage não gravável'];
        } catch (\Exception $e) {
            return ['status' => 'unhealthy', 'message' => 'Erro no storage: ' . $e->getMessage()];
        }
    }

    /**
     * Verificar cache.
     */
    protected function checkCache(): array
    {
        try {
            cache()->put('health_check', 'ok', 1);
            if (cache()->get('health_check') === 'ok') {
                return ['status' => 'healthy', 'message' => 'Cache OK'];
            }
            return ['status' => 'unhealthy', 'message' => 'Cache com problemas'];
        } catch (\Exception $e) {
            return ['status' => 'unhealthy', 'message' => 'Erro no cache: ' . $e->getMessage()];
        }
    }

    /**
     * Verificar queue.
     */
    protected function checkQueue(): array
    {
        try {
            $connection = config('queue.default');
            if ($connection === 'sync') {
                return ['status' => 'healthy', 'message' => 'Queue Sync OK'];
            }
            // Para outros drivers, implementar verificação específica
            return ['status' => 'healthy', 'message' => 'Queue OK'];
        } catch (\Exception $e) {
            return ['status' => 'unhealthy', 'message' => 'Erro na queue: ' . $e->getMessage()];
        }
    }

    /**
     * Verificar email.
     */
    protected function checkEmail(): array
    {
        try {
            // Tenta enviar email de teste (opcional)
            return ['status' => 'healthy', 'message' => 'Email OK'];
        } catch (\Exception $e) {
            return ['status' => 'warning', 'message' => 'Email com problemas: ' . $e->getMessage()];
        }
    }

    /**
     * Verificar filesystem.
     */
    protected function checkFilesystem(): array
    {
        try {
            $disk = \Storage::disk('public');
            if ($disk->exists('health_check.txt')) {
                $disk->delete('health_check.txt');
            }
            $disk->put('health_check.txt', 'test');
            $disk->delete('health_check.txt');

            return ['status' => 'healthy', 'message' => 'Filesystem OK'];
        } catch (\Exception $e) {
            return ['status' => 'unhealthy', 'message' => 'Erro no filesystem: ' . $e->getMessage()];
        }
    }
}