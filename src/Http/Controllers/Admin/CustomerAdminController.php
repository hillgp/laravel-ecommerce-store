<?php

namespace LaravelEcommerce\Store\Http\Controllers\Admin;

use LaravelEcommerce\Store\Models\Customer;
use LaravelEcommerce\Store\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class CustomerAdminController extends \LaravelEcommerce\Store\Http\Controllers\Controller
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
        $this->middleware('auth');
        $this->middleware('role:admin');
    }

    /**
     * Lista de clientes.
     */
    public function index(Request $request): View
    {
        $query = Customer::with(['orders', 'addresses', 'reviews']);

        // Filtros
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('document', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        if ($request->filled('verified')) {
            if ($request->verified === 'verified') {
                $query->whereNotNull('email_verified_at');
            } else {
                $query->whereNull('email_verified_at');
            }
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('has_orders')) {
            if ($request->has_orders === 'yes') {
                $query->has('orders');
            } else {
                $query->doesntHave('orders');
            }
        }

        // Ordenação
        $sortBy = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');

        $query->orderBy($sortBy, $sortDirection);

        $customers = $query->paginate(20);

        return view('store::admin.customers.index', compact('customers'));
    }

    /**
     * Exibir cliente.
     */
    public function show(Customer $customer): View
    {
        $customer->load([
            'orders' => function ($query) {
                $query->latest()->limit(10);
            },
            'addresses',
            'reviews' => function ($query) {
                $query->with('product')->latest();
            },
            'wishlist',
            'coupons'
        ]);

        // Estatísticas do cliente
        $stats = [
            'total_orders' => $customer->orders()->count(),
            'total_spent' => $customer->orders()->sum('total'),
            'average_order_value' => $customer->orders()->avg('total') ?? 0,
            'last_order_date' => $customer->orders()->max('created_at'),
            'total_reviews' => $customer->reviews()->count(),
            'average_rating' => $customer->reviews()->avg('rating') ?? 0,
        ];

        return view('store::admin.customers.show', compact('customer', 'stats'));
    }

    /**
     * Formulário de criação.
     */
    public function create(): View
    {
        return view('store::admin.customers.create');
    }

    /**
     * Salvar cliente.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:customers,email',
            'phone' => 'nullable|string|max:20',
            'document' => 'nullable|string|max:20|unique:customers,document',
            'birth_date' => 'nullable|date|before:today',
            'gender' => 'nullable|in:male,female,other',
            'password' => 'nullable|string|min:8',
            'is_active' => 'boolean',
            'send_welcome_email' => 'boolean',
        ]);

        try {
            $data = $request->all();

            // Criptografar senha se fornecida
            if ($request->filled('password')) {
                $data['password'] = Hash::make($request->password);
            }

            $customer = Customer::create($data);

            // Enviar email de boas-vindas se solicitado
            if ($request->boolean('send_welcome_email')) {
                $this->notificationService->sendWelcomeEmail($customer);
            }

            return redirect()->route('store.admin.customers.show', $customer)
                ->with('success', 'Cliente criado com sucesso!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Erro ao criar cliente: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Formulário de edição.
     */
    public function edit(Customer $customer): View
    {
        return view('store::admin.customers.edit', compact('customer'));
    }

    /**
     * Atualizar cliente.
     */
    public function update(Request $request, Customer $customer): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:customers,email,' . $customer->id,
            'phone' => 'nullable|string|max:20',
            'document' => 'nullable|string|max:20|unique:customers,document,' . $customer->id,
            'birth_date' => 'nullable|date|before:today',
            'gender' => 'nullable|in:male,female,other',
            'password' => 'nullable|string|min:8',
            'is_active' => 'boolean',
        ]);

        try {
            $data = $request->all();

            // Criptografar senha se fornecida
            if ($request->filled('password')) {
                $data['password'] = Hash::make($request->password);
            } else {
                unset($data['password']);
            }

            $customer->update($data);

            return redirect()->route('store.admin.customers.show', $customer)
                ->with('success', 'Cliente atualizado com sucesso!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Erro ao atualizar cliente: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Excluir cliente.
     */
    public function destroy(Customer $customer): RedirectResponse
    {
        try {
            // Verificar se cliente tem pedidos
            if ($customer->orders()->count() > 0) {
                return redirect()->back()
                    ->with('error', 'Não é possível excluir cliente com pedidos existentes.');
            }

            $customer->delete();

            return redirect()->route('store.admin.customers.index')
                ->with('success', 'Cliente excluído com sucesso!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Erro ao excluir cliente: ' . $e->getMessage());
        }
    }

    /**
     * Alterar status do cliente.
     */
    public function toggleStatus(Customer $customer): RedirectResponse
    {
        try {
            $customer->update([
                'is_active' => !$customer->is_active
            ]);

            $status = $customer->is_active ? 'ativado' : 'desativado';

            return redirect()->back()
                ->with('success', "Cliente {$status} com sucesso!");
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Erro ao alterar status do cliente: ' . $e->getMessage());
        }
    }

    /**
     * Verificar email do cliente.
     */
    public function verifyEmail(Customer $customer): RedirectResponse
    {
        try {
            $customer->update([
                'email_verified_at' => now()
            ]);

            return redirect()->back()
                ->with('success', 'Email do cliente verificado com sucesso!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Erro ao verificar email: ' . $e->getMessage());
        }
    }

    /**
     * Enviar email de redefinição de senha.
     */
    public function sendPasswordReset(Customer $customer): RedirectResponse
    {
        try {
            $token = \Str::random(60);
            $customer->update([
                'password_reset_token' => $token,
                'password_reset_expires_at' => now()->addHours(24),
            ]);

            $this->notificationService->sendPasswordReset($customer, $token);

            return redirect()->back()
                ->with('success', 'Email de redefinição de senha enviado com sucesso!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Erro ao enviar email de redefinição: ' . $e->getMessage());
        }
    }

    /**
     * Histórico de pedidos do cliente.
     */
    public function orderHistory(Customer $customer): View
    {
        $orders = $customer->orders()
            ->with(['items.product', 'statusHistory'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('store::admin.customers.order-history', compact('customer', 'orders'));
    }

    /**
     * Adicionar endereço ao cliente.
     */
    public function addAddress(Request $request, Customer $customer): RedirectResponse
    {
        $request->validate([
            'type' => 'required|in:billing,shipping',
            'name' => 'required|string|max:255',
            'street' => 'required|string|max:255',
            'number' => 'nullable|string|max:20',
            'complement' => 'nullable|string|max:100',
            'neighborhood' => 'required|string|max:100',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:2',
            'zip_code' => 'required|string|max:10',
            'country' => 'required|string|max:2',
            'is_default' => 'boolean',
        ]);

        try {
            // Se for endereço padrão, remover padrão dos outros
            if ($request->boolean('is_default')) {
                $customer->addresses()
                    ->where('type', $request->type)
                    ->update(['is_default' => false]);
            }

            $customer->addresses()->create($request->all());

            return redirect()->back()
                ->with('success', 'Endereço adicionado com sucesso!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Erro ao adicionar endereço: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Atualizar endereço do cliente.
     */
    public function updateAddress(Request $request, Customer $customer, $addressId): RedirectResponse
    {
        $request->validate([
            'type' => 'required|in:billing,shipping',
            'name' => 'required|string|max:255',
            'street' => 'required|string|max:255',
            'number' => 'nullable|string|max:20',
            'complement' => 'nullable|string|max:100',
            'neighborhood' => 'required|string|max:100',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:2',
            'zip_code' => 'required|string|max:10',
            'country' => 'required|string|max:2',
            'is_default' => 'boolean',
        ]);

        try {
            $address = $customer->addresses()->findOrFail($addressId);

            // Se for endereço padrão, remover padrão dos outros
            if ($request->boolean('is_default')) {
                $customer->addresses()
                    ->where('type', $request->type)
                    ->where('id', '!=', $addressId)
                    ->update(['is_default' => false]);
            }

            $address->update($request->all());

            return redirect()->back()
                ->with('success', 'Endereço atualizado com sucesso!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Erro ao atualizar endereço: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Excluir endereço do cliente.
     */
    public function deleteAddress(Customer $customer, $addressId): RedirectResponse
    {
        try {
            $address = $customer->addresses()->findOrFail($addressId);
            $address->delete();

            return redirect()->back()
                ->with('success', 'Endereço excluído com sucesso!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Erro ao excluir endereço: ' . $e->getMessage());
        }
    }

    /**
     * Exportar clientes.
     */
    public function export(Request $request): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $format = $request->get('format', 'csv');
        $filename = 'clientes-' . date('Y-m-d-H-i-s') . '.' . $format;

        $query = Customer::with(['orders']);

        // Aplicar filtros se existirem
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $customers = $query->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($customers) {
            $file = fopen('php://output', 'w');

            // Cabeçalho
            fputcsv($file, [
                'ID',
                'Nome',
                'Email',
                'Telefone',
                'Documento',
                'Data de Nascimento',
                'Gênero',
                'Status',
                'Email Verificado',
                'Total de Pedidos',
                'Total Gasto',
                'Data de Cadastro'
            ]);

            // Dados
            foreach ($customers as $customer) {
                fputcsv($file, [
                    $customer->id,
                    $customer->name,
                    $customer->email,
                    $customer->phone,
                    $customer->document,
                    $customer->birth_date?->format('d/m/Y'),
                    $customer->gender,
                    $customer->is_active ? 'Ativo' : 'Inativo',
                    $customer->email_verified_at ? 'Sim' : 'Não',
                    $customer->orders()->count(),
                    $customer->orders()->sum('total'),
                    $customer->created_at->format('d/m/Y H:i:s'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Relatórios de clientes.
     */
    public function reports(Request $request): View
    {
        $reportType = $request->get('type', 'overview');

        switch ($reportType) {
            case 'new_customers':
                $customers = Customer::selectRaw('DATE(created_at) as date, COUNT(*) as count')
                    ->groupBy('date')
                    ->orderBy('date', 'desc')
                    ->limit(30)
                    ->get();
                break;

            case 'top_customers':
                $customers = Customer::with('orders')
                    ->selectRaw('customers.*, COUNT(orders.id) as order_count, SUM(orders.total) as total_spent')
                    ->leftJoin('orders', 'customers.id', '=', 'orders.customer_id')
                    ->groupBy('customers.id')
                    ->having('order_count', '>', 0)
                    ->orderBy('total_spent', 'desc')
                    ->limit(20)
                    ->get();
                break;

            case 'inactive_customers':
                $customers = Customer::whereDoesntHave('orders', function ($query) {
                    $query->where('created_at', '>=', now()->subMonths(6));
                })->paginate(20);
                break;

            case 'unverified_emails':
                $customers = Customer::whereNull('email_verified_at')
                    ->orderBy('created_at', 'desc')
                    ->paginate(20);
                break;

            default: // overview
                $customers = Customer::selectRaw('
                    COUNT(*) as total_customers,
                    COUNT(CASE WHEN is_active = 1 THEN 1 END) as active_customers,
                    COUNT(CASE WHEN email_verified_at IS NOT NULL THEN 1 END) as verified_customers,
                    COUNT(CASE WHEN created_at >= ? THEN 1 END) as new_customers_this_month,
                    AVG(CASE WHEN orders.total IS NOT NULL THEN orders.total END) as avg_order_value
                ', [now()->startOfMonth()])
                ->leftJoin('orders', 'customers.id', '=', 'orders.customer_id')
                ->first();
                break;
        }

        return view('store::admin.customers.reports', compact('customers', 'reportType'));
    }

    /**
     * Estatísticas de clientes.
     */
    public function stats(): JsonResponse
    {
        $stats = [
            'total_customers' => Customer::count(),
            'active_customers' => Customer::where('is_active', true)->count(),
            'verified_customers' => Customer::whereNotNull('email_verified_at')->count(),
            'new_customers_today' => Customer::whereDate('created_at', today())->count(),
            'new_customers_this_week' => Customer::where('created_at', '>=', now()->startOfWeek())->count(),
            'new_customers_this_month' => Customer::where('created_at', '>=', now()->startOfMonth())->count(),
            'customers_with_orders' => Customer::has('orders')->count(),
            'customers_without_orders' => Customer::doesntHave('orders')->count(),
        ];

        return response()->json($stats);
    }
}