@extends('store::layouts.app')

@section('title', 'Carrinho de Compras - ' . config('store.name'))

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-4">Carrinho de Compras</h1>
        <nav class="flex" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-1 md:space-x-3">
                <li class="inline-flex items-center">
                    <a href="{{ route('store.products.index') }}" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600">
                        <i class="fas fa-home mr-2"></i>
                        Início
                    </a>
                </li>
                <li>
                    <div class="flex items-center">
                        <i class="fas fa-chevron-right text-gray-400 mx-1"></i>
                        <span class="text-sm font-medium text-gray-500">Carrinho</span>
                    </div>
                </li>
            </ol>
        </nav>
    </div>

    @if($cartItems->count() > 0)
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Cart Items -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow-sm">
                    <div class="p-6 border-b">
                        <h2 class="text-lg font-semibold">Itens do Carrinho ({{ $cartItems->count() }})</h2>
                    </div>

                    <div class="divide-y divide-gray-200">
                        @foreach($cartItems as $item)
                            <div class="p-6 flex items-center space-x-4">
                                <!-- Product Image -->
                                <div class="flex-shrink-0">
                                    <img src="{{ $item->product->main_image ?? '/images/no-image.png' }}"
                                         alt="{{ $item->product->name }}"
                                         class="w-20 h-20 object-cover rounded">
                                </div>

                                <!-- Product Details -->
                                <div class="flex-1 min-w-0">
                                    <h3 class="text-lg font-medium text-gray-900">
                                        <a href="{{ route('store.products.show', $item->product->slug) }}" class="hover:text-blue-600">
                                            {{ $item->product->name }}
                                        </a>
                                    </h3>

                                    @if($item->options)
                                        <p class="text-sm text-gray-600 mt-1">
                                            @foreach($item->options as $key => $value)
                                                {{ ucfirst($key) }}: {{ $value }}
                                            @endforeach
                                        </p>
                                    @endif

                                    <div class="flex items-center justify-between mt-2">
                                        <div class="flex items-center space-x-2">
                                            <span class="text-lg font-bold text-gray-900">
                                                R$ {{ number_format($item->price, 2, ',', '.') }}
                                            </span>
                                            @if($item->product->compare_price && $item->product->compare_price > $item->price)
                                                <span class="text-sm text-gray-500 line-through">
                                                    R$ {{ number_format($item->product->compare_price, 2, ',', '.') }}
                                                </span>
                                            @endif
                                        </div>

                                        <div class="flex items-center space-x-2">
                                            <!-- Quantity Controls -->
                                            <div class="flex items-center border border-gray-300 rounded">
                                                <button onclick="updateQuantity({{ $item->id }}, {{ $item->quantity - 1 }})"
                                                        class="px-3 py-1 text-gray-600 hover:bg-gray-50">
                                                    <i class="fas fa-minus text-sm"></i>
                                                </button>
                                                <span class="px-3 py-1 border-x border-gray-300">{{ $item->quantity }}</span>
                                                <button onclick="updateQuantity({{ $item->id }}, {{ $item->quantity + 1 }})"
                                                        class="px-3 py-1 text-gray-600 hover:bg-gray-50">
                                                    <i class="fas fa-plus text-sm"></i>
                                                </button>
                                            </div>

                                            <!-- Remove Button -->
                                            <button onclick="removeItem({{ $item->id }})"
                                                    class="text-red-600 hover:text-red-800 p-1">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="mt-2">
                                        <span class="text-sm font-medium text-gray-900">
                                            Subtotal: R$ {{ number_format($item->quantity * $item->price, 2, ',', '.') }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Cart Summary -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow-sm p-6 sticky top-4">
                    <h2 class="text-lg font-semibold mb-4">Resumo do Pedido</h2>

                    <div class="space-y-3 mb-4">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Subtotal ({{ $cartSummary['item_count'] }} itens)</span>
                            <span class="font-medium">R$ {{ number_format($cartSummary['subtotal'], 2, ',', '.') }}</span>
                        </div>

                        @if($cartSummary['discount_amount'] > 0)
                            <div class="flex justify-between text-green-600">
                                <span>Desconto</span>
                                <span>- R$ {{ number_format($cartSummary['discount_amount'], 2, ',', '.') }}</span>
                            </div>
                        @endif

                        @if($cartSummary['tax_amount'] > 0)
                            <div class="flex justify-between">
                                <span class="text-gray-600">Impostos</span>
                                <span class="font-medium">R$ {{ number_format($cartSummary['tax_amount'], 2, ',', '.') }}</span>
                            </div>
                        @endif

                        @if($cartSummary['shipping_amount'] > 0)
                            <div class="flex justify-between">
                                <span class="text-gray-600">Frete</span>
                                <span class="font-medium">R$ {{ number_format($cartSummary['shipping_amount'], 2, ',', '.') }}</span>
                            </div>
                        @endif

                        <hr class="my-3">

                        <div class="flex justify-between text-lg font-bold">
                            <span>Total</span>
                            <span class="text-blue-600">R$ {{ number_format($cartSummary['total'], 2, ',', '.') }}</span>
                        </div>
                    </div>

                    <!-- Coupon Code -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Cupom de Desconto</label>
                        <div class="flex space-x-2">
                            <input type="text" id="couponCode" placeholder="Digite o código"
                                   class="flex-1 border border-gray-300 rounded px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500">
                            <button onclick="applyCoupon()" class="bg-gray-200 text-gray-700 px-4 py-2 rounded hover:bg-gray-300 transition-colors">
                                Aplicar
                            </button>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="space-y-3">
                        <a href="{{ route('store.cart.checkout') }}"
                           class="w-full bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors font-medium text-center block">
                            <i class="fas fa-credit-card mr-2"></i>
                            Finalizar Compra
                        </a>

                        <a href="{{ route('store.products.index') }}"
                           class="w-full bg-gray-200 text-gray-700 px-6 py-3 rounded-lg hover:bg-gray-300 transition-colors font-medium text-center block">
                            <i class="fas fa-arrow-left mr-2"></i>
                            Continuar Comprando
                        </a>

                        <button onclick="clearCart()"
                                class="w-full bg-red-600 text-white px-6 py-3 rounded-lg hover:bg-red-700 transition-colors font-medium">
                            <i class="fas fa-trash mr-2"></i>
                            Esvaziar Carrinho
                        </button>
                    </div>

                    <!-- Security Notice -->
                    <div class="mt-6 p-3 bg-gray-50 rounded">
                        <div class="flex items-center text-sm text-gray-600">
                            <i class="fas fa-shield-alt text-green-500 mr-2"></i>
                            <span>Compra segura com criptografia SSL</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @else
        <!-- Empty Cart -->
        <div class="text-center py-12">
            <div class="mb-6">
                <i class="fas fa-shopping-cart text-6xl text-gray-300"></i>
            </div>
            <h2 class="text-2xl font-bold text-gray-900 mb-4">Seu carrinho está vazio</h2>
            <p class="text-gray-600 mb-8">Adicione alguns produtos ao seu carrinho para continuar com a compra.</p>
            <a href="{{ route('store.products.index') }}"
               class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 transition-colors">
                <i class="fas fa-shopping-bag mr-2"></i>
                Começar a Comprar
            </a>
        </div>
    @endif
</div>

<script>
function updateQuantity(itemId, quantity) {
    if (quantity < 1) return;

    fetch(`/store/cart/update`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            item_id: itemId,
            quantity: quantity
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Erro ao atualizar quantidade');
        }
    });
}

function removeItem(itemId) {
    if (!confirm('Tem certeza que deseja remover este item do carrinho?')) {
        return;
    }

    fetch(`/store/cart/remove`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            item_id: itemId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Erro ao remover item');
        }
    });
}

function clearCart() {
    if (!confirm('Tem certeza que deseja esvaziar o carrinho?')) {
        return;
    }

    fetch(`/store/cart/clear`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Erro ao esvaziar carrinho');
        }
    });
}

function applyCoupon() {
    const couponCode = document.getElementById('couponCode').value.trim();

    if (!couponCode) {
        alert('Digite um código de cupom');
        return;
    }

    fetch(`/store/cart/apply-coupon`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            coupon_code: couponCode
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Erro ao aplicar cupom');
        }
    });
}

// Update cart count in header if function exists
if (window.updateCartCount) {
    window.updateCartCount({{ $cartSummary['item_count'] ?? 0 }});
}
</script>
@endsection