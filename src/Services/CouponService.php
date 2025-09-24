<?php

namespace LaravelEcommerce\Store\Services;

use LaravelEcommerce\Store\Models\Coupon;
use LaravelEcommerce\Store\Models\CouponUsage;
use LaravelEcommerce\Store\Models\Customer;
use LaravelEcommerce\Store\Models\Order;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Carbon\Carbon;

class CouponService
{
    /**
     * Aplica um cupom ao carrinho
     */
    public function applyCoupon(string $code, array $cartData, int $customerId): array
    {
        $coupon = $this->getValidCoupon($code, $cartData, $customerId);

        if (!$coupon) {
            return [
                'success' => false,
                'message' => 'Cupom inválido, expirado ou não aplicável',
            ];
        }

        $discount = $this->calculateDiscount($coupon, $cartData);

        return [
            'success' => true,
            'coupon' => $coupon,
            'discount' => $discount,
            'message' => "Cupom aplicado! " . $coupon->getDiscountDescription(),
        ];
    }

    /**
     * Remove um cupom do carrinho
     */
    public function removeCoupon(string $code): array
    {
        // A implementação depende de como os cupons são armazenados na sessão/carrinho
        return [
            'success' => true,
            'message' => 'Cupom removido com sucesso',
        ];
    }

    /**
     * Cria um novo cupom
     */
    public function createCoupon(array $data): Coupon
    {
        // Gera código único se não foi fornecido
        if (empty($data['code'])) {
            $data['code'] = $this->generateUniqueCode();
        } else {
            $data['code'] = strtoupper($data['code']);
        }

        return Coupon::create($data);
    }

    /**
     * Atualiza um cupom
     */
    public function updateCoupon(Coupon $coupon, array $data): Coupon
    {
        if (isset($data['code'])) {
            $data['code'] = strtoupper($data['code']);
        }

        $coupon->update($data);
        return $coupon->fresh();
    }

    /**
     * Exclui um cupom
     */
    public function deleteCoupon(Coupon $coupon): bool
    {
        return $coupon->delete();
    }

    /**
     * Obtém um cupom válido
     */
    public function getValidCoupon(string $code, array $cartData, int $customerId): ?Coupon
    {
        $coupon = Coupon::active()
                       ->byCode($code)
                       ->first();

        if (!$coupon) {
            return null;
        }

        // Verifica se pode ser usado
        if (!$coupon->canBeUsed()) {
            return null;
        }

        // Verifica se o cliente pode usar
        if (!$coupon->canCustomerUse($customerId)) {
            return null;
        }

        // Verifica se pode ser aplicado ao pedido
        $orderData = [
            'total' => $cartData['total'] ?? 0,
            'subtotal' => $cartData['subtotal'] ?? 0,
            'customer_group' => $cartData['customer_group'] ?? null,
        ];

        if (!$coupon->canBeAppliedToOrder($orderData, $customerId)) {
            return null;
        }

        // Verifica se pode ser aplicado aos produtos
        $productIds = $cartData['product_ids'] ?? [];
        if (!$coupon->canBeAppliedToProducts($productIds)) {
            return null;
        }

        return $coupon;
    }

    /**
     * Calcula o desconto para um pedido
     */
    public function calculateDiscount(Coupon $coupon, array $cartData): array
    {
        $subtotal = $cartData['subtotal'] ?? 0;
        $shipping = $cartData['shipping'] ?? 0;
        $total = $subtotal + $shipping;

        $orderData = [
            'subtotal' => $subtotal,
            'shipping' => $shipping,
            'total' => $total,
        ];

        return $coupon->calculateDiscount($orderData);
    }

    /**
     * Obtém cupons ativos
     */
    public function getActiveCoupons(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Coupon::active();

        if (isset($filters['type'])) {
            $query->byType($filters['type']);
        }

        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('code', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('name', 'like', '%' . $filters['search'] . '%');
            });
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Obtém cupons expirados
     */
    public function getExpiredCoupons(): Collection
    {
        return Coupon::where(function ($query) {
            $query->where('expires_at', '<', now())
                  ->orWhere(function ($q) {
                      $q->whereNotNull('usage_limit')
                        ->whereRaw('used_count >= usage_limit');
                  });
        })->get();
    }

    /**
     * Obtém estatísticas dos cupons
     */
    public function getCouponStats(): array
    {
        $totalCoupons = Coupon::count();
        $activeCoupons = Coupon::active()->count();
        $expiredCoupons = $this->getExpiredCoupons()->count();
        $usedToday = CouponUsage::whereDate('used_at', today())->count();
        $totalUsage = CouponUsage::count();
        $totalDiscount = CouponUsage::sum('discount_amount');

        return [
            'total_coupons' => $totalCoupons,
            'active_coupons' => $activeCoupons,
            'expired_coupons' => $expiredCoupons,
            'used_today' => $usedToday,
            'total_usage' => $totalUsage,
            'total_discount' => $totalDiscount,
            'formatted_total_discount' => 'R$ ' . number_format($totalDiscount, 2, ',', '.'),
        ];
    }

    /**
     * Obtém relatório de uso de cupons
     */
    public function getUsageReport(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = CouponUsage::with(['coupon', 'customer', 'order']);

        if (isset($filters['coupon_id'])) {
            $query->byCoupon($filters['coupon_id']);
        }

        if (isset($filters['customer_id'])) {
            $query->byCustomer($filters['customer_id']);
        }

        if (isset($filters['date_from'])) {
            $query->whereDate('used_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('used_at', '<=', $filters['date_to']);
        }

        return $query->orderBy('used_at', 'desc')->paginate($perPage);
    }

    /**
     * Registra o uso de um cupom
     */
    public function recordCouponUsage(Coupon $coupon, int $customerId, int $orderId, float $discountAmount): void
    {
        $coupon->recordUsage($customerId, $orderId, $discountAmount);
    }

    /**
     * Obtém cupom por código
     */
    public function getCouponByCode(string $code): ?Coupon
    {
        return Coupon::byCode($code)->first();
    }

    /**
     * Valida dados do cupom
     */
    public function validateCouponData(array $data): array
    {
        $errors = [];

        if (empty($data['code'])) {
            $errors[] = 'Código do cupom é obrigatório';
        } elseif (strlen($data['code']) < 3) {
            $errors[] = 'Código deve ter pelo menos 3 caracteres';
        } elseif (Coupon::byCode(strtoupper($data['code']))->exists()) {
            $errors[] = 'Este código já está em uso';
        }

        if (empty($data['name'])) {
            $errors[] = 'Nome do cupom é obrigatório';
        }

        if (empty($data['type'])) {
            $errors[] = 'Tipo do cupom é obrigatório';
        } elseif (!in_array($data['type'], ['fixed', 'percentage', 'free_shipping'])) {
            $errors[] = 'Tipo de cupom inválido';
        }

        if (!isset($data['value']) || $data['value'] <= 0) {
            $errors[] = 'Valor do cupom deve ser maior que zero';
        }

        if ($data['type'] === 'percentage' && $data['value'] > 100) {
            $errors[] = 'Percentual não pode ser maior que 100%';
        }

        if (isset($data['minimum_amount']) && $data['minimum_amount'] < 0) {
            $errors[] = 'Valor mínimo deve ser positivo';
        }

        if (isset($data['maximum_discount']) && $data['maximum_discount'] < 0) {
            $errors[] = 'Desconto máximo deve ser positivo';
        }

        if (isset($data['usage_limit']) && $data['usage_limit'] < 0) {
            $errors[] = 'Limite de uso deve ser positivo';
        }

        if (isset($data['usage_per_customer']) && $data['usage_per_customer'] < 1) {
            $errors[] = 'Uso por cliente deve ser pelo menos 1';
        }

        if (isset($data['starts_at']) && isset($data['expires_at']) && $data['starts_at'] >= $data['expires_at']) {
            $errors[] = 'Data de início deve ser anterior à data de expiração';
        }

        return $errors;
    }

    /**
     * Gera um código único para cupom
     */
    protected function generateUniqueCode(): string
    {
        do {
            $code = 'CP' . strtoupper(substr(md5(microtime()), 0, 6));
        } while (Coupon::byCode($code)->exists());

        return $code;
    }

    /**
     * Obtém cupons aplicáveis a um carrinho
     */
    public function getApplicableCoupons(array $cartData, int $customerId): Collection
    {
        $productIds = $cartData['product_ids'] ?? [];

        return Coupon::active()
                    ->where(function ($query) use ($cartData, $customerId) {
                        $query->where(function ($q) use ($cartData, $customerId) {
                            // Verifica se pode ser aplicado ao pedido
                            $orderData = [
                                'total' => $cartData['total'] ?? 0,
                                'customer_group' => $cartData['customer_group'] ?? null,
                            ];

                            $q->where(function ($subQuery) use ($orderData, $customerId) {
                                $subQuery->where('minimum_amount', '<=', $orderData['total'])
                                        ->orWhereNull('minimum_amount');
                            })
                            ->where(function ($subQuery) use ($customerId) {
                                $subQuery->where('first_purchase_only', false)
                                        ->orWhere(function ($q) use ($customerId) {
                                            $orderCount = Order::where('customer_id', $customerId)
                                                              ->where('status', '!=', 'cancelled')
                                                              ->count();
                                            $q->where('first_purchase_only', true)
                                              ->where('order_count', '=', 0);
                                        });
                            });
                        });
                    })
                    ->where(function ($query) use ($productIds) {
                        // Verifica se pode ser aplicado aos produtos
                        $query->where(function ($q) {
                            $q->whereNull('applicable_products')
                              ->whereNull('applicable_categories')
                              ->whereNull('applicable_brands');
                        })
                        ->orWhere(function ($q) use ($productIds) {
                            $q->where(function ($subQ) use ($productIds) {
                                $subQ->whereNotNull('applicable_products')
                                     ->whereIn('applicable_products', $productIds);
                            })
                            ->orWhere(function ($subQ) use ($productIds) {
                                $subQ->whereNotNull('applicable_categories')
                                     ->whereHas('products.categories', function ($query) use ($productIds) {
                                         $query->whereIn('products.id', $productIds);
                                     });
                            });
                        });
                    })
                    ->where(function ($query) use ($customerId) {
                        // Verifica uso por cliente
                        $query->where(function ($q) use ($customerId) {
                            $q->where('usage_per_customer', '>', function ($subQuery) use ($customerId) {
                                $subQuery->selectRaw('COUNT(*)')
                                        ->from('coupon_usages')
                                        ->whereColumn('coupon_id', 'coupons.id')
                                        ->where('customer_id', $customerId);
                            })
                            ->orWhereNull('usage_per_customer');
                        });
                    })
                    ->orderBy('created_at', 'desc')
                    ->get();
    }

    /**
     * Obtém histórico de uso de um cliente
     */
    public function getCustomerUsageHistory(int $customerId, int $perPage = 10): LengthAwarePaginator
    {
        return CouponUsage::byCustomer($customerId)
                         ->with(['coupon', 'order'])
                         ->orderBy('used_at', 'desc')
                         ->paginate($perPage);
    }

    /**
     * Ativa um cupom
     */
    public function activateCoupon(Coupon $coupon): void
    {
        $coupon->activate();
    }

    /**
     * Desativa um cupom
     */
    public function deactivateCoupon(Coupon $coupon): void
    {
        $coupon->deactivate();
    }

    /**
     * Duplica um cupom
     */
    public function duplicateCoupon(Coupon $coupon, array $overrides = []): Coupon
    {
        $data = $coupon->toArray();
        unset($data['id'], $data['code'], $data['used_count'], $data['created_at'], $data['updated_at']);

        $data = array_merge($data, $overrides);
        $data['code'] = $this->generateUniqueCode();

        return $this->createCoupon($data);
    }
}