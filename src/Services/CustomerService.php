<?php

namespace LaravelEcommerce\Store\Services;

use LaravelEcommerce\Store\Models\Customer;
use LaravelEcommerce\Store\Models\CustomerAddress;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class CustomerService
{
    /**
     * Cria um novo cliente
     */
    public function createCustomer(array $data): Customer
    {
        // Valida os dados
        $this->validateCustomerData($data);

        // Verifica se o email já existe
        if (Customer::where('email', $data['email'])->exists()) {
            throw new \Exception('Email já cadastrado');
        }

        $customer = Customer::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'phone' => $data['phone'] ?? null,
            'document' => $data['document'] ?? null,
            'birth_date' => $data['birth_date'] ?? null,
            'gender' => $data['gender'] ?? null,
            'is_active' => $data['is_active'] ?? true,
            'email_verified' => $data['email_verified'] ?? false,
            'preferences' => $data['preferences'] ?? [],
            'meta_data' => [
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'created_from' => 'registration',
            ],
        ]);

        // Envia email de boas-vindas se o email não estiver verificado
        if (!$customer->email_verified) {
            $this->sendWelcomeEmail($customer);
        }

        Log::info('Cliente criado', [
            'customer_id' => $customer->id,
            'email' => $customer->email,
        ]);

        return $customer;
    }

    /**
     * Atualiza um cliente
     */
    public function updateCustomer(Customer $customer, array $data): Customer
    {
        // Valida os dados
        $this->validateCustomerData($data, $customer->id);

        // Verifica se o email já existe (se foi alterado)
        if (isset($data['email']) && $data['email'] !== $customer->email) {
            if (Customer::where('email', $data['email'])->where('id', '!=', $customer->id)->exists()) {
                throw new \Exception('Email já cadastrado');
            }
        }

        $customer->update([
            'name' => $data['name'] ?? $customer->name,
            'email' => $data['email'] ?? $customer->email,
            'phone' => $data['phone'] ?? $customer->phone,
            'document' => $data['document'] ?? $customer->document,
            'birth_date' => $data['birth_date'] ?? $customer->birth_date,
            'gender' => $data['gender'] ?? $customer->gender,
            'is_active' => $data['is_active'] ?? $customer->is_active,
            'preferences' => array_merge($customer->preferences ?? [], $data['preferences'] ?? []),
        ]);

        Log::info('Cliente atualizado', [
            'customer_id' => $customer->id,
            'email' => $customer->email,
        ]);

        return $customer;
    }

    /**
     * Autentica um cliente
     */
    public function authenticate(array $credentials): ?Customer
    {
        $customer = Customer::where('email', $credentials['email'])->first();

        if (!$customer) {
            return null;
        }

        if (!$customer->canLogin()) {
            return null;
        }

        if (!Hash::check($credentials['password'], $customer->password)) {
            return null;
        }

        // Atualiza o último login
        $customer->updateLastLogin();

        Log::info('Cliente autenticado', [
            'customer_id' => $customer->id,
            'email' => $customer->email,
        ]);

        return $customer;
    }

    /**
     * Verifica o email do cliente
     */
    public function verifyEmail(Customer $customer, string $token): bool
    {
        if ($customer->verifyEmail($token)) {
            Log::info('Email verificado', [
                'customer_id' => $customer->id,
                'email' => $customer->email,
            ]);

            return true;
        }

        return false;
    }

    /**
     * Envia email de verificação
     */
    public function sendVerificationEmail(Customer $customer): void
    {
        $token = $customer->regenerateVerificationToken();

        // Envia email de verificação
        Mail::to($customer->email)->send(new \LaravelEcommerce\Store\Mail\CustomerEmailVerification($customer, $token));

        Log::info('Email de verificação enviado', [
            'customer_id' => $customer->id,
            'email' => $customer->email,
        ]);
    }

    /**
     * Envia email de boas-vindas
     */
    public function sendWelcomeEmail(Customer $customer): void
    {
        Mail::to($customer->email)->send(new \LaravelEcommerce\Store\Mail\CustomerWelcomeMail($customer));

        Log::info('Email de boas-vindas enviado', [
            'customer_id' => $customer->id,
            'email' => $customer->email,
        ]);
    }

    /**
     * Altera a senha do cliente
     */
    public function changePassword(Customer $customer, string $currentPassword, string $newPassword): bool
    {
        if (!Hash::check($currentPassword, $customer->password)) {
            throw new \Exception('Senha atual incorreta');
        }

        $customer->update(['password' => $newPassword]);

        Log::info('Senha alterada', [
            'customer_id' => $customer->id,
            'email' => $customer->email,
        ]);

        return true;
    }

    /**
     * Redefine a senha do cliente
     */
    public function resetPassword(Customer $customer, string $newPassword): void
    {
        $customer->update(['password' => $newPassword]);

        Log::info('Senha redefinida', [
            'customer_id' => $customer->id,
            'email' => $customer->email,
        ]);
    }

    /**
     * Envia email de redefinição de senha
     */
    public function sendPasswordResetEmail(string $email): bool
    {
        $customer = Customer::where('email', $email)->first();

        if (!$customer) {
            return false;
        }

        $token = \Illuminate\Support\Str::random(64);

        $customer->setMeta('password_reset_token', $token);
        $customer->setMeta('password_reset_expires', now()->addHour());

        Mail::to($customer->email)->send(new \LaravelEcommerce\Store\Mail\CustomerPasswordResetMail($customer, $token));

        Log::info('Email de redefinição de senha enviado', [
            'customer_id' => $customer->id,
            'email' => $customer->email,
        ]);

        return true;
    }

    /**
     * Verifica token de redefinição de senha
     */
    public function verifyPasswordResetToken(string $email, string $token): ?Customer
    {
        $customer = Customer::where('email', $email)->first();

        if (!$customer) {
            return null;
        }

        $resetToken = $customer->getMeta('password_reset_token');
        $resetExpires = $customer->getMeta('password_reset_expires');

        if (!$resetToken || $resetToken !== $token) {
            return null;
        }

        if (!$resetExpires || now()->isAfter($resetExpires)) {
            return null;
        }

        return $customer;
    }

    /**
     * Cria um endereço para o cliente
     */
    public function createAddress(Customer $customer, array $data): CustomerAddress
    {
        $address = $customer->addAddress($data);

        Log::info('Endereço criado', [
            'customer_id' => $customer->id,
            'address_id' => $address->id,
            'type' => $address->type,
        ]);

        return $address;
    }

    /**
     * Atualiza um endereço do cliente
     */
    public function updateAddress(CustomerAddress $address, array $data): CustomerAddress
    {
        $address->update($data);

        Log::info('Endereço atualizado', [
            'customer_id' => $address->customer_id,
            'address_id' => $address->id,
            'type' => $address->type,
        ]);

        return $address;
    }

    /**
     * Remove um endereço do cliente
     */
    public function deleteAddress(CustomerAddress $address): bool
    {
        // Não permite deletar endereço padrão
        if ($address->is_default) {
            throw new \Exception('Não é possível deletar endereço padrão');
        }

        $address->delete();

        Log::info('Endereço removido', [
            'customer_id' => $address->customer_id,
            'address_id' => $address->id,
            'type' => $address->type,
        ]);

        return true;
    }

    /**
     * Define um endereço como padrão
     */
    public function setDefaultAddress(Customer $customer, int $addressId): bool
    {
        return $customer->setDefaultAddress($addressId);
    }

    /**
     * Obtém clientes por filtros
     */
    public function getCustomers(array $filters = [], int $limit = 50)
    {
        $query = Customer::query();

        if (isset($filters['search'])) {
            $query->search($filters['search']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (isset($filters['is_verified'])) {
            $query->where('email_verified', $filters['is_verified']);
        }

        if (isset($filters['created_from'])) {
            $query->where('created_at', '>=', $filters['created_from']);
        }

        if (isset($filters['created_until'])) {
            $query->where('created_at', '<=', $filters['created_until']);
        }

        return $query->orderBy('created_at', 'desc')->limit($limit)->get();
    }

    /**
     * Obtém estatísticas dos clientes
     */
    public function getCustomerStats(int $days = 30): array
    {
        $query = Customer::where('created_at', '>=', now()->subDays($days));

        return [
            'total_customers' => Customer::count(),
            'active_customers' => Customer::where('is_active', true)->count(),
            'verified_customers' => Customer::where('email_verified', true)->count(),
            'new_customers_today' => (clone $query)->whereDate('created_at', today())->count(),
            'new_customers_this_week' => (clone $query)->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'new_customers_this_month' => $query->count(),
            'customers_by_gender' => Customer::selectRaw('gender, COUNT(*) as count')
                ->whereNotNull('gender')
                ->groupBy('gender')
                ->pluck('count', 'gender'),
            'customers_by_state' => Customer::join('customer_addresses', 'customers.id', '=', 'customer_addresses.customer_id')
                ->selectRaw('customer_addresses.state, COUNT(DISTINCT customers.id) as count')
                ->where('customer_addresses.is_default', true)
                ->whereNotNull('customer_addresses.state')
                ->groupBy('customer_addresses.state')
                ->orderByDesc('count')
                ->limit(10)
                ->pluck('count', 'state'),
        ];
    }

    /**
     * Obtém clientes inativos
     */
    public function getInactiveCustomers(int $days = 90, int $limit = 50)
    {
        return Customer::where('last_login_at', '<', now()->subDays($days))
            ->orWhereNull('last_login_at')
            ->where('is_active', true)
            ->orderBy('last_login_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Obtém clientes com pedidos recentes
     */
    public function getCustomersWithRecentOrders(int $days = 30, int $limit = 50)
    {
        return Customer::whereHas('orders', function ($query) use ($days) {
            $query->where('created_at', '>=', now()->subDays($days));
        })
        ->with(['orders' => function ($query) use ($days) {
            $query->where('created_at', '>=', now()->subDays($days))
                  ->orderBy('created_at', 'desc');
        }])
        ->orderBy('created_at', 'desc')
        ->limit($limit)
        ->get();
    }

    /**
     * Obtém melhores clientes
     */
    public function getTopCustomers(int $limit = 20)
    {
        return Customer::select('customers.*')
            ->join('orders', 'customers.id', '=', 'orders.customer_id')
            ->selectRaw('customers.*, SUM(orders.total) as total_spent, COUNT(orders.id) as total_orders, AVG(orders.total) as average_order_value')
            ->where('orders.status', '!=', 'cancelled')
            ->groupBy('customers.id')
            ->orderByDesc('total_spent')
            ->limit($limit)
            ->get();
    }

    /**
     * Valida dados do cliente
     */
    protected function validateCustomerData(array $data, int $customerId = null): void
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'password' => $customerId ? 'nullable|string|min:8' : 'required|string|min:8',
            'phone' => 'nullable|string|max:20',
            'document' => 'nullable|string|max:20',
            'birth_date' => 'nullable|date|before:today',
            'gender' => 'nullable|in:male,female,other,masculino,feminino,outro',
            'is_active' => 'nullable|boolean',
            'email_verified' => 'nullable|boolean',
        ];

        $validator = \Illuminate\Support\Facades\Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new \Exception('Dados inválidos: ' . implode(', ', $validator->errors()->all()));
        }
    }

    /**
     * Exporta dados dos clientes
     */
    public function exportCustomers(array $filters = [], string $format = 'csv'): string
    {
        $customers = $this->getCustomers($filters, 1000);

        if ($format === 'csv') {
            $filename = 'customers_' . date('Y-m-d_H-i-s') . '.csv';
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ];

            $output = fopen('php://output', 'w');

            // Cabeçalhos
            fputcsv($output, [
                'ID',
                'Nome',
                'Email',
                'Telefone',
                'Documento',
                'Data de Nascimento',
                'Gênero',
                'Ativo',
                'Email Verificado',
                'Último Login',
                'Data de Criação',
            ]);

            // Dados
            foreach ($customers as $customer) {
                fputcsv($output, [
                    $customer->id,
                    $customer->name,
                    $customer->email,
                    $customer->phone,
                    $customer->document,
                    $customer->birth_date?->format('Y-m-d'),
                    $customer->gender,
                    $customer->is_active ? 'Sim' : 'Não',
                    $customer->email_verified ? 'Sim' : 'Não',
                    $customer->last_login_at?->format('Y-m-d H:i:s'),
                    $customer->created_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($output);

            return response()->stream(function () {
                // O conteúdo já foi enviado
            }, 200, $headers);
        }

        throw new \Exception('Formato não suportado');
    }

    /**
     * Remove clientes duplicados
     */
    public function removeDuplicateCustomers(): array
    {
        $duplicates = Customer::select('email')
            ->groupBy('email')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('email');

        $removed = 0;

        foreach ($duplicates as $email) {
            $customers = Customer::where('email', $email)
                ->orderBy('created_at', 'asc')
                ->get();

            // Mantém o primeiro e remove os outros
            $customers->shift(); // Remove o primeiro da coleção

            foreach ($customers as $customer) {
                $customer->delete();
                $removed++;
            }
        }

        return [
            'duplicates_found' => $duplicates->count(),
            'customers_removed' => $removed,
        ];
    }

    /**
     * Limpa clientes inativos
     */
    public function cleanupInactiveCustomers(int $days = 365): int
    {
        return Customer::where('is_active', false)
            ->where('created_at', '<', now()->subDays($days))
            ->delete();
    }
}