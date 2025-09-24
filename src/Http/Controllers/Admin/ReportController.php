<?php

namespace LaravelEcommerce\Store\Http\Controllers\Admin;

use LaravelEcommerce\Store\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Carbon\Carbon;

class ReportController extends \LaravelEcommerce\Store\Http\Controllers\Controller
{
    protected ReportService $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
        $this->middleware('auth');
        $this->middleware('role:admin');
    }

    /**
     * Dashboard de relatórios.
     */
    public function dashboard(): View
    {
        $analytics = $this->reportService->dashboardAnalytics();

        return view('store::admin.reports.dashboard', compact('analytics'));
    }

    /**
     * Relatório de vendas.
     */
    public function sales(Request $request): View
    {
        $filters = $request->only([
            'date_from',
            'date_to',
            'status',
            'payment_status',
            'group_by'
        ]);

        $salesData = $this->reportService->salesReport($filters);

        // Resumo do período
        $summary = [
            'total_orders' => $salesData->sum('total_orders'),
            'total_revenue' => $salesData->sum('total_revenue'),
            'avg_order_value' => $salesData->avg('avg_order_value'),
            'paid_orders' => $salesData->sum('paid_orders'),
            'pending_orders' => $salesData->sum('pending_orders'),
        ];

        return view('store::admin.reports.sales', compact('salesData', 'summary', 'filters'));
    }

    /**
     * Relatório de produtos.
     */
    public function products(Request $request): View
    {
        $filters = $request->only([
            'date_from',
            'date_to',
            'category_id',
            'limit'
        ]);

        $products = $this->reportService->topProductsReport($filters);

        // Resumo
        $summary = [
            'total_products' => $products->count(),
            'total_revenue' => $products->sum('total_revenue'),
            'total_quantity' => $products->sum('total_quantity'),
            'avg_price' => $products->avg('avg_price'),
        ];

        return view('store::admin.reports.products', compact('products', 'summary', 'filters'));
    }

    /**
     * Relatório de categorias.
     */
    public function categories(Request $request): View
    {
        $filters = $request->only([
            'date_from',
            'date_to'
        ]);

        $categories = $this->reportService->categoryReport($filters);

        // Resumo
        $summary = [
            'total_categories' => $categories->count(),
            'total_products' => $categories->sum('total_products'),
            'total_revenue' => $categories->sum('total_revenue'),
            'total_sold' => $categories->sum('total_sold'),
        ];

        return view('store::admin.reports.categories', compact('categories', 'summary', 'filters'));
    }

    /**
     * Relatório de clientes.
     */
    public function customers(Request $request): View
    {
        $filters = $request->only([
            'date_from',
            'date_to',
            'min_orders',
            'limit'
        ]);

        $customers = $this->reportService->customerReport($filters);

        // Resumo
        $summary = [
            'total_customers' => $customers->count(),
            'total_orders' => $customers->sum('total_orders'),
            'total_spent' => $customers->sum('total_spent'),
            'avg_order_value' => $customers->avg('avg_order_value'),
        ];

        return view('store::admin.reports.customers', compact('customers', 'summary', 'filters'));
    }

    /**
     * Relatório de estoque.
     */
    public function inventory(Request $request): View
    {
        $filters = $request->only([
            'status',
            'category_id',
            'brand_id'
        ]);

        $inventory = $this->reportService->inventoryReport($filters);

        // Resumo
        $summary = [
            'total_products' => $inventory->count(),
            'total_stock' => $inventory->sum('stock_quantity'),
            'total_value' => $inventory->sum('stock_value'),
            'in_stock' => $inventory->where('stock_status', 'in_stock')->count(),
            'low_stock' => $inventory->where('stock_status', 'low_stock')->count(),
            'out_of_stock' => $inventory->where('stock_status', 'out_of_stock')->count(),
        ];

        return view('store::admin.reports.inventory', compact('inventory', 'summary', 'filters'));
    }

    /**
     * Relatório financeiro.
     */
    public function financial(Request $request): View
    {
        $filters = $request->only([
            'date_from',
            'date_to',
            'payment_status',
            'group_by'
        ]);

        $financialData = $this->reportService->financialReport($filters);

        // Resumo
        $summary = [
            'total_revenue' => $financialData->sum('gross_revenue'),
            'net_revenue' => $financialData->sum('net_revenue'),
            'total_discounts' => $financialData->sum('total_discounts'),
            'total_shipping' => $financialData->sum('total_shipping'),
            'total_taxes' => $financialData->sum('total_taxes'),
            'total_orders' => $financialData->sum('total_orders'),
            'avg_order_value' => $financialData->avg('avg_order_value'),
        ];

        return view('store::admin.reports.financial', compact('financialData', 'summary', 'filters'));
    }

    /**
     * Relatório de desempenho.
     */
    public function performance(Request $request): View
    {
        $filters = $request->only(['period']);

        $performance = $this->reportService->performanceReport($filters);

        return view('store::admin.reports.performance', compact('performance', 'filters'));
    }

    /**
     * Exportar relatório.
     */
    public function export(Request $request): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $type = $request->get('type', 'sales');
        $format = $request->get('format', 'csv');
        $filters = $request->except(['type', 'format']);

        $filename = "relatorio-{$type}-" . date('Y-m-d-H-i-s') . '.' . $format;

        switch ($type) {
            case 'sales':
                $data = $this->reportService->salesReport($filters);
                break;
            case 'products':
                $data = $this->reportService->topProductsReport($filters);
                break;
            case 'categories':
                $data = $this->reportService->categoryReport($filters);
                break;
            case 'customers':
                $data = $this->reportService->customerReport($filters);
                break;
            case 'inventory':
                $data = $this->reportService->inventoryReport($filters);
                break;
            case 'financial':
                $data = $this->reportService->financialReport($filters);
                break;
            default:
                $data = collect();
        }

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($data, $type) {
            $file = fopen('php://output', 'w');

            // Cabeçalho baseado no tipo
            $header = $this->getExportHeader($type);
            fputcsv($file, $header);

            // Dados
            foreach ($data as $row) {
                fputcsv($file, (array) $row);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Obter cabeçalho para exportação.
     */
    protected function getExportHeader(string $type): array
    {
        return match ($type) {
            'sales' => [
                'Data',
                'Total de Pedidos',
                'Receita Total',
                'Valor Médio do Pedido',
                'Pedidos Pagos',
                'Pedidos Pendentes'
            ],
            'products' => [
                'ID',
                'Nome do Produto',
                'SKU',
                'Quantidade Total',
                'Receita Total',
                'Preço Médio',
                'Total de Pedidos'
            ],
            'categories' => [
                'ID',
                'Nome da Categoria',
                'Total de Produtos',
                'Estoque Total',
                'Total Vendido',
                'Receita Total',
                'Preço Médio'
            ],
            'customers' => [
                'ID',
                'Nome',
                'Email',
                'Total de Pedidos',
                'Total Gasto',
                'Valor Médio do Pedido',
                'Última Compra',
                'Primeira Compra'
            ],
            'inventory' => [
                'ID',
                'Nome do Produto',
                'SKU',
                'Categoria',
                'Marca',
                'Preço',
                'Custo',
                'Estoque',
                'Estoque Mínimo',
                'Valor do Estoque',
                'Vendido (30 dias)',
                'Status do Estoque'
            ],
            'financial' => [
                'Data',
                'Receita Bruta',
                'Receita Líquida',
                'Descontos',
                'Frete',
                'Impostos',
                'Total de Pedidos',
                'Valor Médio do Pedido'
            ],
            default => ['Dados']
        };
    }

    /**
     * API para gráficos em tempo real.
     */
    public function realtimeData(Request $request): JsonResponse
    {
        $type = $request->get('type', 'sales');
        $period = $request->get('period', 7); // dias

        $filters = [
            'date_from' => now()->subDays($period)->format('Y-m-d'),
            'date_to' => now()->format('Y-m-d'),
        ];

        switch ($type) {
            case 'sales':
                $data = $this->reportService->salesReport($filters);
                break;
            case 'products':
                $data = $this->reportService->topProductsReport($filters);
                break;
            case 'performance':
                $data = $this->reportService->performanceReport(['period' => $period]);
                break;
            default:
                $data = collect();
        }

        return response()->json([
            'data' => $data,
            'period' => $period,
            'generated_at' => now()->toISOString(),
        ]);
    }

    /**
     * Relatório personalizado.
     */
    public function custom(Request $request): View
    {
        $reportTypes = [
            'sales' => 'Vendas',
            'products' => 'Produtos',
            'categories' => 'Categorias',
            'customers' => 'Clientes',
            'inventory' => 'Estoque',
            'financial' => 'Financeiro',
            'performance' => 'Desempenho',
        ];

        $groupByOptions = [
            'day' => 'Por Dia',
            'week' => 'Por Semana',
            'month' => 'Por Mês',
        ];

        $statusOptions = [
            'pending' => 'Pendente',
            'confirmed' => 'Confirmado',
            'shipped' => 'Enviado',
            'delivered' => 'Entregue',
            'cancelled' => 'Cancelado',
        ];

        if ($request->isMethod('post')) {
            $reportType = $request->get('report_type');
            $filters = $request->except(['report_type']);

            switch ($reportType) {
                case 'sales':
                    $data = $this->reportService->salesReport($filters);
                    break;
                case 'products':
                    $data = $this->reportService->topProductsReport($filters);
                    break;
                case 'categories':
                    $data = $this->reportService->categoryReport($filters);
                    break;
                case 'customers':
                    $data = $this->reportService->customerReport($filters);
                    break;
                case 'inventory':
                    $data = $this->reportService->inventoryReport($filters);
                    break;
                case 'financial':
                    $data = $this->reportService->financialReport($filters);
                    break;
                case 'performance':
                    $data = $this->reportService->performanceReport($filters);
                    break;
                default:
                    $data = collect();
            }

            return view('store::admin.reports.custom-result', compact('data', 'reportType', 'filters'));
        }

        return view('store::admin.reports.custom', compact(
            'reportTypes',
            'groupByOptions',
            'statusOptions'
        ));
    }

    /**
     * Estatísticas rápidas para widgets.
     */
    public function quickStats(): JsonResponse
    {
        $stats = [
            'today_sales' => [
                'orders' => \LaravelEcommerce\Store\Models\Order::whereDate('created_at', today())->count(),
                'revenue' => \LaravelEcommerce\Store\Models\Order::whereDate('created_at', today())->sum('total'),
            ],
            'month_sales' => [
                'orders' => \LaravelEcommerce\Store\Models\Order::whereMonth('created_at', now()->month)->count(),
                'revenue' => \LaravelEcommerce\Store\Models\Order::whereMonth('created_at', now()->month)->sum('total'),
            ],
            'top_products' => $this->reportService->topProductsReport(['limit' => 5]),
            'low_stock' => $this->reportService->inventoryReport(['status' => 'low_stock'])->take(5),
            'recent_orders' => \LaravelEcommerce\Store\Models\Order::with('customer')
                ->latest()
                ->limit(5)
                ->get(),
        ];

        return response()->json($stats);
    }
}