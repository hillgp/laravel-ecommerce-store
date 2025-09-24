<?php

namespace LaravelEcommerce\Store\Http\Controllers\Admin;

use LaravelEcommerce\Store\Models\Order;
use LaravelEcommerce\Store\Models\OrderStatus;
use LaravelEcommerce\Store\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Carbon\Carbon;

class OrderAdminController extends \LaravelEcommerce\Store\Http\Controllers\Controller
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
        $this->middleware('auth');
        $this->middleware('role:admin');
    }

    /**
     * Lista de pedidos.
     */
    public function index(Request $request): View
    {
        $query = Order::with(['customer', 'items.product', 'statusHistory']);

        // Filtros
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%")
                  ->orWhere('customer_email', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($customerQuery) use ($search) {
                      $customerQuery->where('name', 'like', "%{$search}%")
                                   ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        if ($request->filled('shipping_status')) {
            $query->where('shipping_status', $request->shipping_status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('total_min')) {
            $query->where('total', '>=', $request->total_min);
        }

        if ($request->filled('total_max')) {
            $query->where('total', '<=', $request->total_max);
        }

        // Ordenação
        $sortBy = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');

        $query->orderBy($sortBy, $sortDirection);

        $orders = $query->paginate(20);

        $statuses = OrderStatus::all();

        return view('store::admin.orders.index', compact('orders', 'statuses'));
    }

    /**
     * Exibir pedido.
     */
    public function show(Order $order): View
    {
        $order->load([
            'customer',
            'items.product.images',
            'statusHistory.user',
            'billingAddress',
            'shippingAddress',
            'payments',
            'shipments'
        ]);

        // Calcular totais
        $totals = [
            'subtotal' => $order->items->sum(function ($item) {
                return $item->price * $item->quantity;
            }),
            'discount' => $order->discount_amount,
            'shipping' => $order->shipping_amount,
            'tax' => $order->tax_amount,
            'total' => $order->total,
        ];

        return view('store::admin.orders.show', compact('order', 'totals'));
    }

    /**
     * Atualizar status do pedido.
     */
    public function updateStatus(Request $request, Order $order): RedirectResponse
    {
        $request->validate([
            'status' => 'required|string',
            'notify_customer' => 'boolean',
            'comment' => 'nullable|string|max:1000',
        ]);

        try {
            $oldStatus = $order->status;
            $newStatus = $request->status;

            // Atualizar status
            $order->update([
                'status' => $newStatus,
                'updated_at' => now(),
            ]);

            // Registrar histórico
            $order->statusHistory()->create([
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'comment' => $request->comment,
                'user_id' => auth()->id(),
                'notified_customer' => $request->boolean('notify_customer'),
            ]);

            // Notificar cliente se solicitado
            if ($request->boolean('notify_customer')) {
                $this->notificationService->sendOrderStatusUpdate($order, $newStatus);
            }

            return redirect()->back()
                ->with('success', 'Status do pedido atualizado com sucesso!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Erro ao atualizar status: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Adicionar comentário ao pedido.
     */
    public function addComment(Request $request, Order $order): RedirectResponse
    {
        $request->validate([
            'comment' => 'required|string|max:1000',
            'is_internal' => 'boolean',
            'notify_customer' => 'boolean',
        ]);

        try {
            $order->comments()->create([
                'comment' => $request->comment,
                'is_internal' => $request->boolean('is_internal', true),
                'user_id' => auth()->id(),
            ]);

            // Notificar cliente se não for comentário interno
            if (!$request->boolean('is_internal') && $request->boolean('notify_customer')) {
                $this->notificationService->sendNotificationWithTemplate(
                    'order_comment',
                    [
                        'customer_name' => $order->customer_name,
                        'order_number' => $order->order_number,
                        'comment' => $request->comment,
                    ],
                    'customer',
                    $order->customer_id ?? 1
                );
            }

            return redirect()->back()
                ->with('success', 'Comentário adicionado com sucesso!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Erro ao adicionar comentário: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Atualizar informações de pagamento.
     */
    public function updatePayment(Request $request, Order $order): RedirectResponse
    {
        $request->validate([
            'payment_status' => 'required|in:pending,paid,failed,refunded,partially_refunded',
            'payment_method' => 'nullable|string|max:50',
            'payment_date' => 'nullable|date',
            'transaction_id' => 'nullable|string|max:255',
            'payment_amount' => 'nullable|numeric|min:0',
            'payment_notes' => 'nullable|string|max:1000',
        ]);

        try {
            $order->update([
                'payment_status' => $request->payment_status,
                'payment_method' => $request->payment_method,
                'payment_date' => $request->payment_date,
                'transaction_id' => $request->transaction_id,
                'payment_notes' => $request->payment_notes,
            ]);

            // Registrar pagamento se valor foi informado
            if ($request->filled('payment_amount')) {
                $order->payments()->create([
                    'amount' => $request->payment_amount,
                    'payment_method' => $request->payment_method,
                    'transaction_id' => $request->transaction_id,
                    'status' => $request->payment_status,
                    'payment_date' => $request->payment_date,
                    'notes' => $request->payment_notes,
                ]);
            }

            return redirect()->back()
                ->with('success', 'Informações de pagamento atualizadas com sucesso!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Erro ao atualizar pagamento: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Atualizar informações de entrega.
     */
    public function updateShipping(Request $request, Order $order): RedirectResponse
    {
        $request->validate([
            'shipping_status' => 'required|in:pending,shipped,delivered,cancelled,returned',
            'shipping_method' => 'nullable|string|max:100',
            'shipping_carrier' => 'nullable|string|max:100',
            'tracking_number' => 'nullable|string|max:255',
            'shipping_date' => 'nullable|date',
            'delivered_date' => 'nullable|date',
            'shipping_cost' => 'nullable|numeric|min:0',
            'shipping_notes' => 'nullable|string|max:1000',
        ]);

        try {
            $order->update([
                'shipping_status' => $request->shipping_status,
                'shipping_method' => $request->shipping_method,
                'shipping_carrier' => $request->shipping_carrier,
                'tracking_number' => $request->tracking_number,
                'shipping_date' => $request->shipping_date,
                'delivered_date' => $request->delivered_date,
                'shipping_notes' => $request->shipping_notes,
            ]);

            // Registrar envio se informações de rastreamento foram fornecidas
            if ($request->filled('tracking_number')) {
                $order->shipments()->create([
                    'shipping_method' => $request->shipping_method,
                    'shipping_carrier' => $request->shipping_carrier,
                    'tracking_number' => $request->tracking_number,
                    'shipping_date' => $request->shipping_date,
                    'delivered_date' => $request->delivered_date,
                    'status' => $request->shipping_status,
                    'notes' => $request->shipping_notes,
                ]);
            }

            return redirect()->back()
                ->with('success', 'Informações de entrega atualizadas com sucesso!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Erro ao atualizar entrega: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Processar reembolso.
     */
    public function processRefund(Request $request, Order $order): RedirectResponse
    {
        $request->validate([
            'refund_amount' => 'required|numeric|min:0|max:' . $order->total,
            'refund_reason' => 'required|string|max:255',
            'refund_method' => 'required|in:original_payment,store_credit,bank_transfer',
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            $refund = $order->refunds()->create([
                'amount' => $request->refund_amount,
                'reason' => $request->refund_reason,
                'method' => $request->refund_method,
                'status' => 'pending',
                'requested_by' => auth()->id(),
                'notes' => $request->notes,
            ]);

            // Atualizar status do pedido se reembolso total
            if ($request->refund_amount >= $order->total) {
                $order->update(['status' => 'refunded']);
            }

            return redirect()->back()
                ->with('success', 'Solicitação de reembolso criada com sucesso!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Erro ao processar reembolso: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Cancelar pedido.
     */
    public function cancel(Request $request, Order $order): RedirectResponse
    {
        $request->validate([
            'cancel_reason' => 'required|string|max:255',
            'notify_customer' => 'boolean',
            'restock_items' => 'boolean',
        ]);

        try {
            // Verificar se pedido pode ser cancelado
            if (!in_array($order->status, ['pending', 'confirmed'])) {
                return redirect()->back()
                    ->with('error', 'Este pedido não pode ser cancelado.');
            }

            $order->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancel_reason' => $request->cancel_reason,
            ]);

            // Registrar cancelamento no histórico
            $order->statusHistory()->create([
                'old_status' => $order->status,
                'new_status' => 'cancelled',
                'comment' => 'Pedido cancelado: ' . $request->cancel_reason,
                'user_id' => auth()->id(),
                'notified_customer' => $request->boolean('notify_customer'),
            ]);

            // Repor estoque se solicitado
            if ($request->boolean('restock_items')) {
                foreach ($order->items as $item) {
                    $item->product->increment('stock_quantity', $item->quantity);
                }
            }

            // Notificar cliente
            if ($request->boolean('notify_customer')) {
                $this->notificationService->sendOrderCancelled($order);
            }

            return redirect()->back()
                ->with('success', 'Pedido cancelado com sucesso!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Erro ao cancelar pedido: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Imprimir pedido.
     */
    public function print(Order $order): View
    {
        $order->load([
            'customer',
            'items.product',
            'billingAddress',
            'shippingAddress'
        ]);

        return view('store::admin.orders.print', compact('order'));
    }

    /**
     * Exportar pedidos.
     */
    public function export(Request $request): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $format = $request->get('format', 'csv');
        $filename = 'pedidos-' . date('Y-m-d-H-i-s') . '.' . $format;

        $query = Order::with(['customer', 'items.product']);

        // Aplicar filtros se existirem
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $orders = $query->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($orders) {
            $file = fopen('php://output', 'w');

            // Cabeçalho
            fputcsv($file, [
                'Número do Pedido',
                'Cliente',
                'Email',
                'Total',
                'Status',
                'Status do Pagamento',
                'Status da Entrega',
                'Data do Pedido',
                'Itens'
            ]);

            // Dados
            foreach ($orders as $order) {
                $items = $order->items->map(function ($item) {
                    return $item->product->name . ' (x' . $item->quantity . ')';
                })->join('; ');

                fputcsv($file, [
                    $order->order_number,
                    $order->customer_name,
                    $order->customer_email,
                    $order->total,
                    $order->status,
                    $order->payment_status,
                    $order->shipping_status,
                    $order->created_at->format('d/m/Y H:i:s'),
                    $items,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Relatórios de pedidos.
     */
    public function reports(Request $request): View
    {
        $reportType = $request->get('type', 'sales');

        switch ($reportType) {
            case 'sales':
                $orders = Order::selectRaw('DATE(created_at) as date, COUNT(*) as count, SUM(total) as total')
                    ->groupBy('date')
                    ->orderBy('date', 'desc')
                    ->limit(30)
                    ->get();
                break;

            case 'status':
                $orders = Order::selectRaw('status, COUNT(*) as count')
                    ->groupBy('status')
                    ->get();
                break;

            case 'payment':
                $orders = Order::selectRaw('payment_status, COUNT(*) as count, SUM(total) as total')
                    ->groupBy('payment_status')
                    ->get();
                break;

            case 'shipping':
                $orders = Order::selectRaw('shipping_status, COUNT(*) as count')
                    ->groupBy('shipping_status')
                    ->get();
                break;

            case 'customers':
                $orders = Order::with('customer')
                    ->selectRaw('customer_id, COUNT(*) as order_count, SUM(total) as total_spent')
                    ->whereNotNull('customer_id')
                    ->groupBy('customer_id')
                    ->orderBy('total_spent', 'desc')
                    ->limit(20)
                    ->get();
                break;

            default:
                $orders = collect();
        }

        return view('store::admin.orders.reports', compact('orders', 'reportType'));
    }

    /**
     * Estatísticas rápidas.
     */
    public function stats(): JsonResponse
    {
        $stats = [
            'total_orders' => Order::count(),
            'pending_orders' => Order::where('status', 'pending')->count(),
            'confirmed_orders' => Order::where('status', 'confirmed')->count(),
            'shipped_orders' => Order::where('status', 'shipped')->count(),
            'delivered_orders' => Order::where('status', 'delivered')->count(),
            'cancelled_orders' => Order::where('status', 'cancelled')->count(),
            'today_orders' => Order::whereDate('created_at', today())->count(),
            'today_revenue' => Order::whereDate('created_at', today())->sum('total'),
            'month_orders' => Order::whereMonth('created_at', now()->month)->count(),
            'month_revenue' => Order::whereMonth('created_at', now()->month)->sum('total'),
        ];

        return response()->json($stats);
    }
}