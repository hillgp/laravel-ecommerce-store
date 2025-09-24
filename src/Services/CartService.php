<?php

namespace LaravelEcommerce\Store\Services;

use LaravelEcommerce\Store\Models\Cart;
use LaravelEcommerce\Store\Models\CartItem;
use LaravelEcommerce\Store\Models\Product;
use LaravelEcommerce\Store\Models\ProductVariant;
use LaravelEcommerce\Store\Models\Customer;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;

class CartService
{
    /**
     * Obtém ou cria um carrinho para o cliente/sessão atual
     */
    public function getCurrentCart(): Cart
    {
        $customer = Auth::guard('customer')->user();

        return Cart::getCurrentCart($customer);
    }

    /**
     * Obtém um carrinho pelo ID
     */
    public function getCartById(int $cartId): ?Cart
    {
        return Cart::find($cartId);
    }

    /**
     * Adiciona um produto ao carrinho
     */
    public function addProduct(int $productId, int $quantity = 1, ?int $variantId = null, ?array $options = null): CartItem
    {
        $product = Product::findOrFail($productId);
        $variant = $variantId ? ProductVariant::findOrFail($variantId) : null;

        // Verifica se o produto/variação pode ser comprado
        if (!$product->canBePurchased()) {
            throw new \Exception('Produto não disponível para compra');
        }

        if ($variant && !$variant->canBePurchased()) {
            throw new \Exception('Variação do produto não disponível para compra');
        }

        $cart = $this->getCurrentCart();

        // Verifica se há estoque suficiente
        $availableQuantity = $variant ?
            $variant->getAvailableQuantity() :
            $product->getAvailableQuantity();

        if ($quantity > $availableQuantity) {
            throw new \Exception('Quantidade solicitada não disponível em estoque');
        }

        $price = $variant ? $variant->getFinalPrice() : $product->getFinalPrice();

        return $cart->addItem([
            'product_id' => $productId,
            'product_variant_id' => $variantId,
            'quantity' => $quantity,
            'price' => $price,
            'options' => $options,
            'meta_data' => [
                'added_at' => now(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ],
        ]);
    }

    /**
     * Atualiza a quantidade de um item no carrinho
     */
    public function updateItemQuantity(int $itemId, int $quantity): bool
    {
        $cart = $this->getCurrentCart();
        $item = $cart->items()->find($itemId);

        if (!$item) {
            throw new \Exception('Item não encontrado no carrinho');
        }

        // Verifica se a quantidade pode ser alterada
        if (!$item->canChangeQuantity($quantity)) {
            throw new \Exception('Quantidade não disponível em estoque');
        }

        return $cart->updateItemQuantity($itemId, $quantity);
    }

    /**
     * Remove um item do carrinho
     */
    public function removeItem(int $itemId): bool
    {
        $cart = $this->getCurrentCart();
        return $cart->removeItem($itemId);
    }

    /**
     * Remove múltiplos itens do carrinho
     */
    public function removeItems(array $itemIds): int
    {
        $cart = $this->getCurrentCart();
        $removedCount = 0;

        foreach ($itemIds as $itemId) {
            if ($cart->removeItem($itemId)) {
                $removedCount++;
            }
        }

        return $removedCount;
    }

    /**
     * Limpa o carrinho
     */
    public function clearCart(): void
    {
        $cart = $this->getCurrentCart();
        $cart->clear();
    }

    /**
     * Aplica um cupom ao carrinho
     */
    public function applyCoupon(string $couponCode): bool
    {
        $cart = $this->getCurrentCart();
        return $cart->applyCoupon($couponCode);
    }

    /**
     * Remove o cupom do carrinho
     */
    public function removeCoupon(): void
    {
        $cart = $this->getCurrentCart();
        $cart->removeCoupon();
    }

    /**
     * Calcula o frete do carrinho
     */
    public function calculateShipping(array $address): float
    {
        $cart = $this->getCurrentCart();
        return $cart->calculateShipping($address);
    }

    /**
     * Calcula os impostos do carrinho
     */
    public function calculateTax(): float
    {
        $cart = $this->getCurrentCart();
        return $cart->calculateTax();
    }

    /**
     * Obtém os itens do carrinho
     */
    public function getCartItems(): \Illuminate\Database\Eloquent\Collection
    {
        $cart = $this->getCurrentCart();
        return $cart->items;
    }

    /**
     * Obtém a contagem de itens no carrinho
     */
    public function getCartItemsCount(): int
    {
        $cart = $this->getCurrentCart();
        return $cart->items_count;
    }

    /**
     * Obtém o total do carrinho
     */
    public function getCartTotal(): float
    {
        $cart = $this->getCurrentCart();
        return $cart->total;
    }

    /**
     * Verifica se o carrinho está vazio
     */
    public function isCartEmpty(): bool
    {
        $cart = $this->getCurrentCart();
        return $cart->isEmpty();
    }

    /**
     * Verifica se o carrinho tem itens
     */
    public function hasCartItems(): bool
    {
        $cart = $this->getCurrentCart();
        return $cart->hasItems();
    }

    /**
     * Mescla carrinho de convidado com carrinho de cliente logado
     */
    public function mergeGuestCart(Customer $customer): void
    {
        $guestCart = Cart::where('session_id', Session::getId())->first();

        if (!$guestCart || $guestCart->isEmpty()) {
            return;
        }

        $customerCart = Cart::where('customer_id', $customer->id)->first();

        if (!$customerCart) {
            // Converte o carrinho de convidado para o cliente
            $guestCart->update(['customer_id' => $customer->id, 'session_id' => null]);
            return;
        }

        // Mescla os carrinhos
        $customerCart->mergeGuestCart($guestCart);
    }

    /**
     * Transfere itens de um carrinho para outro
     */
    public function transferCartItems(int $fromCartId, int $toCartId): int
    {
        $fromCart = Cart::findOrFail($fromCartId);
        $toCart = Cart::findOrFail($toCartId);

        $transferredCount = 0;

        foreach ($fromCart->items as $item) {
            $existingItem = $toCart->items()
                ->where('product_id', $item->product_id)
                ->where('product_variant_id', $item->product_variant_id)
                ->first();

            if ($existingItem) {
                $existingItem->update([
                    'quantity' => $existingItem->quantity + $item->quantity,
                    'total' => ($existingItem->quantity + $item->quantity) * $existingItem->price,
                ]);
            } else {
                $item->update(['cart_id' => $toCart->id]);
            }

            $transferredCount++;
        }

        $toCart->updateTotals();
        $fromCart->clear();

        return $transferredCount;
    }

    /**
     * Salva o carrinho (atualiza atividade)
     */
    public function saveCart(): void
    {
        $cart = $this->getCurrentCart();
        $cart->update(['last_activity' => now()]);
    }

    /**
     * Obtém carrinhos abandonados
     */
    public function getAbandonedCarts(int $minutes = 60): \Illuminate\Database\Eloquent\Collection
    {
        return Cart::where('last_activity', '<', now()->subMinutes($minutes))
            ->where('items_count', '>', 0)
            ->with(['customer', 'items.product'])
            ->get();
    }

    /**
     * Remove carrinhos antigos
     */
    public function cleanupOldCarts(int $days = 30): int
    {
        return Cart::where('last_activity', '<', now()->subDays($days))
            ->where('items_count', 0)
            ->delete();
    }

    /**
     * Obtém estatísticas do carrinho
     */
    public function getCartStats(): array
    {
        $cart = $this->getCurrentCart();

        return [
            'items_count' => $cart->items_count,
            'subtotal' => $cart->subtotal,
            'discount_amount' => $cart->discount_amount,
            'total' => $cart->total,
            'is_empty' => $cart->isEmpty(),
            'has_items' => $cart->hasItems(),
            'has_coupon' => !empty($cart->coupon_code),
            'coupon_code' => $cart->coupon_code,
            'items' => $cart->items->map(function ($item) {
                return $item->display_info;
            }),
        ];
    }

    /**
     * Valida o carrinho para checkout
     */
    public function validateCartForCheckout(): array
    {
        $cart = $this->getCurrentCart();
        $errors = [];

        if ($cart->isEmpty()) {
            $errors[] = 'Carrinho vazio';
        }

        foreach ($cart->items as $item) {
            if (!$item->canBePurchased()) {
                $errors[] = "Produto '{$item->product_name}' não disponível para compra";
            }

            if (!$item->canChangeQuantity($item->quantity)) {
                $errors[] = "Quantidade indisponível para '{$item->product_name}'";
            }
        }

        return $errors;
    }

    /**
     * Obtém sugestões de produtos baseados no carrinho
     */
    public function getCartRecommendations(int $limit = 5): \Illuminate\Database\Eloquent\Collection
    {
        $cart = $this->getCurrentCart();

        if ($cart->isEmpty()) {
            return collect();
        }

        $categoryIds = $cart->items->pluck('product.category_id')->unique()->filter();
        $brandIds = $cart->items->pluck('product.brand_id')->unique()->filter();

        return Product::where('is_active', true)
            ->where('is_featured', true)
            ->where(function ($query) use ($categoryIds, $brandIds) {
                if ($categoryIds->isNotEmpty()) {
                    $query->whereIn('category_id', $categoryIds);
                }
                if ($brandIds->isNotEmpty()) {
                    $query->orWhereIn('brand_id', $brandIds);
                }
            })
            ->whereNotIn('id', $cart->items->pluck('product_id'))
            ->inRandomOrder()
            ->limit($limit)
            ->get();
    }

    /**
     * Obtém histórico de carrinhos do cliente
     */
    public function getCustomerCartHistory(Customer $customer, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return Cart::where('customer_id', $customer->id)
            ->where('items_count', '>', 0)
            ->orderBy('last_activity', 'desc')
            ->limit($limit)
            ->with(['items.product', 'items.variant'])
            ->get();
    }
}