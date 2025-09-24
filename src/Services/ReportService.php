<?php

namespace LaravelEcommerce\Store\Services;

use LaravelEcommerce\Store\Models\Order;
use LaravelEcommerce\Store\Models\Product;
use LaravelEcommerce\Store\Models\Customer;
use LaravelEcommerce\Store\Models\Category;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportService
{
    /**
     * Relatório de vendas geral.
     */
    public function salesReport(array $filters = []): Collection
    {
        $query = Order::query();

        // Aplicar filtros
        if (isset($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }

        $groupBy = $filters['group_by'] ?? 'day';

        switch ($groupBy) {
            case 'month':
                $query->selectRaw('
                    YEAR(created_at) as year,
                    MONTH(created_at) as month,
                    COUNT(*) as total_orders,
                    SUM(total) as total_revenue,
                    AVG(total) as avg_order_value,
                    SUM(CASE WHEN payment_status = "paid" THEN 1 ELSE 0 END) as paid_orders,
                    SUM(CASE WHEN payment_status = "pending" THEN 1 ELSE 0 END) as pending_orders
                ')->groupByRaw('YEAR(created_at), MONTH(created_at)');
                break;

            case 'week':
                $query->selectRaw('
                    YEAR(created_at) as year,
                    WEEK(created_at) as week,
                    COUNT(*) as total_orders,
                    SUM(total) as total_revenue,
                    AVG(total) as avg_order_value
                ')->groupByRaw('YEAR(created_at), WEEK(created_at)');
                break;

            default: // day
                $query->selectRaw('
                    DATE(created_at) as date,
                    COUNT(*) as total_orders,
                    SUM(total) as total_revenue,
                    AVG(total) as avg_order_value,
                    SUM(CASE WHEN payment_status = "paid" THEN 1 ELSE 0 END) as paid_orders,
                    SUM(CASE WHEN payment_status = "pending" THEN 1 ELSE 0 END) as pending_orders
                ')->groupByRaw('DATE(created_at)');
        }

        return $query->orderByRaw($groupBy === 'day' ? 'date' : 'year, month')->get();
    }

    /**
     * Relatório de produtos mais vendidos.
     */
    public function topProductsReport(array $filters = []): Collection
    {
        $query = DB::table('order_items')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->selectRaw('
                products.id,
                products.name,
                products.sku,
                SUM(order_items.quantity) as total_quantity,
                SUM(order_items.quantity * order_items.price) as total_revenue,
                AVG(order_items.price) as avg_price,
                COUNT(DISTINCT orders.id) as total_orders
            ');

        // Aplicar filtros
        if (isset($filters['date_from'])) {
            $query->whereDate('orders.created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('orders.created_at', '<=', $filters['date_to']);
        }

        if (isset($filters['category_id'])) {
            $query->where('products.category_id', $filters['category_id']);
        }

        return $query->groupBy('products.id', 'products.name', 'products.sku')
            ->orderBy('total_revenue', 'desc')
            ->limit($filters['limit'] ?? 20)
            ->get();
    }

    /**
     * Relatório de categorias.
     */
    public function categoryReport(array $filters = []): Collection
    {
        $query = DB::table('products')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->leftJoin('order_items', 'products.id', '=', 'order_items.product_id')
            ->leftJoin('orders', 'order_items.order_id', '=', 'orders.id')
            ->selectRaw('
                categories.id,
                categories.name,
                COUNT(DISTINCT products.id) as total_products,
                SUM(products.stock_quantity) as total_stock,
                COALESCE(SUM(order_items.quantity), 0) as total_sold,
                COALESCE(SUM(order_items.quantity * order_items.price), 0) as total_revenue,
                AVG(products.price) as avg_price
            ');

        // Aplicar filtros
        if (isset($filters['date_from'])) {
            $query->whereDate('orders.created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('orders.created_at', '<=', $filters['date_to']);
        }

        return $query->groupBy('categories.id', 'categories.name')
            ->orderBy('total_revenue', 'desc')
            ->get();
    }

    /**
     * Relatório de clientes.
     */
    public function customerReport(array $filters = []): Collection
    {
        $query = DB::table('customers')
            ->leftJoin('orders', 'customers.id', '=', 'orders.customer_id')
            ->selectRaw('
                customers.id,
                customers.name,
                customers.email,
                COUNT(DISTINCT orders.id) as total_orders,
                COALESCE(SUM(orders.total), 0) as total_spent,
                COALESCE(AVG(orders.total), 0) as avg_order_value,
                MAX(orders.created_at) as last_order_date,
                MIN(orders.created_at) as first_order_date,
                DATEDIFF(MAX(orders.created_at), MIN(orders.created_at)) as customer_lifetime_days
            ');

        // Aplicar filtros
        if (isset($filters['date_from'])) {
            $query->whereDate('orders.created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('orders.created_at', '<=', $filters['date_to']);
        }

        if (isset($filters['min_orders'])) {
            $query->having('total_orders', '>=', $filters['min_orders']);
        }

        return $query->groupBy('customers.id', 'customers.name', 'customers.email')
            ->orderBy('total_spent', 'desc')
            ->limit($filters['limit'] ?? 50)
            ->get();
    }

    /**
     * Relatório de estoque.
     */
    public function inventoryReport(array $filters = []): Collection
    {
        $query = Product::with(['category', 'brand']);

        // Filtros
        if (isset($filters['status'])) {
            switch ($filters['status']) {
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

        if (isset($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (isset($filters['brand_id'])) {
            $query->where('brand_id', $filters['brand_id']);
        }

        $products = $query->get();

        return collect($products)->map(function ($product) {
            $soldLast30Days = DB::table('order_items')
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->where('order_items.product_id', $product->id)
                ->where('orders.created_at', '>=', now()->subDays(30))
                ->sum('quantity');

            return [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'category' => $product->category->name ?? 'Sem categoria',
                'brand' => $product->brand->name ?? 'Sem marca',
                'price' => $product->price,
                'cost_price' => $product->cost_price,
                'stock_quantity' => $product->stock_quantity,
                'min_stock_quantity' => $product->min_stock_quantity,
                'stock_value' => $product->price * $product->stock_quantity,
                'sold_last_30_days' => $soldLast30Days,
                'stock_status' => $this->getStockStatus($product),
                'last_updated' => $product->updated_at,
            ];
        })->sortByDesc('stock_value')->values();
    }

    /**
     * Relatório financeiro.
     */
    public function financialReport(array $filters = []): Collection
    {
        $query = Order::query();

        // Aplicar filtros
        if (isset($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (isset($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }

        $groupBy = $filters['group_by'] ?? 'day';

        switch ($groupBy) {
            case 'month':
                $query->selectRaw('
                    YEAR(created_at) as year,
                    MONTH(created_at) as month,
                    SUM(total) as gross_revenue,
                    SUM(discount_amount) as total_discounts,
                    SUM(shipping_amount) as total_shipping,
                    SUM(tax_amount) as total_taxes,
                    SUM(CASE WHEN payment_status = "paid" THEN total ELSE 0 END) as net_revenue,
                    COUNT(*) as total_orders,
                    AVG(total) as avg_order_value
                ')->groupByRaw('YEAR(created_at), MONTH(created_at)');
                break;

            default: // day
                $query->selectRaw('
                    DATE(created_at) as date,
                    SUM(total) as gross_revenue,
                    SUM(discount_amount) as total_discounts,
                    SUM(shipping_amount) as total_shipping,
                    SUM(tax_amount) as total_taxes,
                    SUM(CASE WHEN payment_status = "paid" THEN total ELSE 0 END) as net_revenue,
                    COUNT(*) as total_orders,
                    AVG(total) as avg_order_value
                ')->groupByRaw('DATE(created_at)');
        }

        return $query->orderByRaw($groupBy === 'day' ? 'date' : 'year, month')->get();
    }

    /**
     * Relatório de desempenho.
     */
    public function performanceReport(array $filters = []): array
    {
        $period = $filters['period'] ?? 30;
        $startDate = now()->subDays($period);

        // Métricas principais
        $metrics = [
            'total_revenue' => Order::where('created_at', '>=', $startDate)->sum('total'),
            'total_orders' => Order::where('created_at', '>=', $startDate)->count(),
            'avg_order_value' => Order::where('created_at', '>=', $startDate)->avg('total') ?? 0,
            'total_customers' => Customer::where('created_at', '>=', $startDate)->count(),
            'conversion_rate' => $this->calculateConversionRate($startDate),
            'customer_lifetime_value' => $this->calculateCLV($startDate),
            'return_rate' => $this->calculateReturnRate($startDate),
            'cart_abandonment_rate' => $this->calculateCartAbandonmentRate($startDate),
        ];

        // Top produtos por receita
        $topProducts = $this->topProductsReport([
            'date_from' => $startDate->format('Y-m-d'),
            'date_to' => now()->format('Y-m-d'),
            'limit' => 10
        ]);

        // Top categorias por receita
        $topCategories = $this->categoryReport([
            'date_from' => $startDate->format('Y-m-d'),
            'date_to' => now()->format('Y-m-d')
        ]);

        // Vendas por hora do dia
        $salesByHour = DB::table('orders')
            ->where('created_at', '>=', $startDate)
            ->selectRaw('HOUR(created_at) as hour, COUNT(*) as count, SUM(total) as revenue')
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();

        // Vendas por dia da semana
        $salesByWeekday = DB::table('orders')
            ->where('created_at', '>=', $startDate)
            ->selectRaw('DAYOFWEEK(created_at) as weekday, COUNT(*) as count, SUM(total) as revenue')
            ->groupBy('weekday')
            ->orderBy('weekday')
            ->get();

        return [
            'metrics' => $metrics,
            'top_products' => $topProducts,
            'top_categories' => $topCategories,
            'sales_by_hour' => $salesByHour,
            'sales_by_weekday' => $salesByWeekday,
            'period' => $period,
        ];
    }

    /**
     * Dashboard analytics.
     */
    public function dashboardAnalytics(): array
    {
        $today = today();
        $thisMonth = now()->startOfMonth();
        $lastMonth = now()->subMonth()->startOfMonth();

        return [
            'today' => [
                'orders' => Order::whereDate('created_at', $today)->count(),
                'revenue' => Order::whereDate('created_at', $today)->sum('total'),
                'customers' => Customer::whereDate('created_at', $today)->count(),
            ],
            'this_month' => [
                'orders' => Order::where('created_at', '>=', $thisMonth)->count(),
                'revenue' => Order::where('created_at', '>=', $thisMonth)->sum('total'),
                'customers' => Customer::where('created_at', '>=', $thisMonth)->count(),
            ],
            'last_month' => [
                'orders' => Order::whereBetween('created_at', [$lastMonth, $thisMonth])->count(),
                'revenue' => Order::whereBetween('created_at', [$lastMonth, $thisMonth])->sum('total'),
                'customers' => Customer::whereBetween('created_at', [$lastMonth, $thisMonth])->count(),
            ],
            'growth' => $this->calculateGrowth($thisMonth, $lastMonth),
        ];
    }

    /**
     * Calcular taxa de conversão.
     */
    protected function calculateConversionRate(Carbon $startDate): float
    {
        // Para simplificar, vamos assumir uma taxa baseada em visitas vs pedidos
        // Em um sistema real, você teria uma tabela de visitas/páginas vistas
        $totalOrders = Order::where('created_at', '>=', $startDate)->count();
        $estimatedVisits = $totalOrders * 3; // Estimativa: 3 visitas por pedido

        return $estimatedVisits > 0 ? ($totalOrders / $estimatedVisits) * 100 : 0;
    }

    /**
     * Calcular CLV (Customer Lifetime Value).
     */
    protected function calculateCLV(Carbon $startDate): float
    {
        $customers = Customer::where('created_at', '>=', $startDate)->get();

        if ($customers->isEmpty()) {
            return 0;
        }

        $totalRevenue = Order::where('created_at', '>=', $startDate)
            ->whereIn('customer_id', $customers->pluck('id'))
            ->sum('total');

        return $totalRevenue / $customers->count();
    }

    /**
     * Calcular taxa de devolução.
     */
    protected function calculateReturnRate(Carbon $startDate): float
    {
        $totalOrders = Order::where('created_at', '>=', $startDate)->count();
        $returnedOrders = Order::where('created_at', '>=', $startDate)
            ->where('status', 'returned')
            ->count();

        return $totalOrders > 0 ? ($returnedOrders / $totalOrders) * 100 : 0;
    }

    /**
     * Calcular taxa de abandono de carrinho.
     */
    protected function calculateCartAbandonmentRate(Carbon $startDate): float
    {
        // Em um sistema real, você compararia carrinhos criados vs pedidos finalizados
        // Por enquanto, vamos usar uma estimativa
        $cartsCreated = DB::table('carts')
            ->where('created_at', '>=', $startDate)
            ->count();

        $ordersCompleted = Order::where('created_at', '>=', $startDate)->count();

        $abandonedCarts = max(0, $cartsCreated - $ordersCompleted);

        return $cartsCreated > 0 ? ($abandonedCarts / $cartsCreated) * 100 : 0;
    }

    /**
     * Calcular crescimento.
     */
    protected function calculateGrowth(Carbon $thisMonth, Carbon $lastMonth): array
    {
        $thisMonthOrders = Order::where('created_at', '>=', $thisMonth)->count();
        $lastMonthOrders = Order::whereBetween('created_at', [$lastMonth, $thisMonth])->count();

        $thisMonthRevenue = Order::where('created_at', '>=', $thisMonth)->sum('total');
        $lastMonthRevenue = Order::whereBetween('created_at', [$lastMonth, $thisMonth])->sum('total');

        $orderGrowth = $lastMonthOrders > 0 ?
            (($thisMonthOrders - $lastMonthOrders) / $lastMonthOrders) * 100 : 0;

        $revenueGrowth = $lastMonthRevenue > 0 ?
            (($thisMonthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100 : 0;

        return [
            'orders' => $orderGrowth,
            'revenue' => $revenueGrowth,
        ];
    }

    /**
     * Determinar status do estoque.
     */
    protected function getStockStatus(Product $product): string
    {
        if ($product->stock_quantity <= 0) {
            return 'out_of_stock';
        }

        if ($product->stock_quantity <= ($product->min_stock_quantity ?? 5)) {
            return 'low_stock';
        }

        return 'in_stock';
    }
}