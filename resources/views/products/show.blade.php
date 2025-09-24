@extends('store::layouts.app')

@section('title', $product->name . ' - ' . config('store.name'))

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Product Images -->
        <div class="space-y-4">
            <div class="aspect-w-1 aspect-h-1">
                <img id="mainImage" src="{{ $product->main_image ?? '/images/no-image.png' }}"
                     alt="{{ $product->name }}" class="w-full h-96 object-cover rounded-lg">
            </div>

            @if($product->images->count() > 1)
                <div class="grid grid-cols-4 gap-2">
                    @foreach($product->images as $image)
                        <img src="{{ $image->url }}" alt="{{ $product->name }}"
                             class="w-full h-20 object-cover rounded cursor-pointer hover:opacity-75 transition-opacity"
                             onclick="changeMainImage('{{ $image->url }}')">
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Product Information -->
        <div class="space-y-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2">{{ $product->name }}</h1>

                <div class="flex items-center space-x-4 mb-4">
                    <div class="flex text-yellow-400">
                        @for($i = 1; $i <= 5; $i++)
                            <i class="fas fa-star {{ $i <= $product->rating ? 'text-yellow-400' : 'text-gray-300' }} text-lg"></i>
                        @endfor
                    </div>
                    <span class="text-gray-600">{{ $product->rating ?? 0 }} ({{ $product->review_count }} avaliações)</span>
                    <span class="text-gray-600">SKU: {{ $product->sku }}</span>
                </div>

                <div class="flex items-center space-x-4 mb-6">
                    @if($product->compare_price)
                        <span class="text-3xl font-bold text-gray-900">
                            R$ {{ number_format($product->price, 2, ',', '.') }}
                        </span>
                        <span class="text-xl text-gray-500 line-through">
                            R$ {{ number_format($product->compare_price, 2, ',', '.') }}
                        </span>
                        <span class="bg-red-100 text-red-800 px-3 py-1 rounded-full text-sm font-semibold">
                            {{ $product->discount_percentage }}% OFF
                        </span>
                    @else
                        <span class="text-3xl font-bold text-gray-900">
                            R$ {{ number_format($product->price, 2, ',', '.') }}
                        </span>
                    @endif
                </div>

                <div class="mb-6">
                    @if($product->is_in_stock)
                        <span class="text-green-600 font-medium">✓ Em estoque</span>
                        <span class="text-gray-600 ml-2">({{ $product->quantity }} disponíveis)</span>
                    @else
                        <span class="text-red-600 font-medium">✗ Fora de estoque</span>
                    @endif
                </div>
            </div>

            <!-- Add to Cart Form -->
            <form id="addToCartForm" class="space-y-4">
                @csrf

                <!-- Quantity -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Quantidade</label>
                    <div class="flex items-center space-x-2">
                        <button type="button" onclick="decreaseQuantity()" class="w-10 h-10 border border-gray-300 rounded flex items-center justify-center hover:bg-gray-50">
                            <i class="fas fa-minus"></i>
                        </button>
                        <input type="number" id="quantity" name="quantity" value="1" min="1" max="{{ $product->quantity }}"
                               class="w-20 text-center border border-gray-300 rounded px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                        <button type="button" onclick="increaseQuantity()" class="w-10 h-10 border border-gray-300 rounded flex items-center justify-center hover:bg-gray-50">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                </div>

                <!-- Options (if any) -->
                @if($product->options ?? false)
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Opções</label>
                        <select name="options[variant]" class="w-full border border-gray-300 rounded px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Selecione uma opção</option>
                            <!-- Options would be populated here -->
                        </select>
                    </div>
                @endif

                <!-- Action Buttons -->
                <div class="flex space-x-3">
                    <button type="submit"
                            class="flex-1 bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors font-medium"
                            {{ $product->is_in_stock ? '' : 'disabled' }}>
                        <i class="fas fa-cart-plus mr-2"></i>
                        {{ $product->is_in_stock ? 'Adicionar ao Carrinho' : 'Indisponível' }}
                    </button>
                    <button type="button" onclick="addToWishlist({{ $product->id }})"
                            class="bg-gray-200 text-gray-700 px-6 py-3 rounded-lg hover:bg-gray-300 transition-colors">
                        <i class="fas fa-heart"></i>
                    </button>
                    <button type="button" onclick="shareProduct()"
                            class="bg-gray-200 text-gray-700 px-6 py-3 rounded-lg hover:bg-gray-300 transition-colors">
                        <i class="fas fa-share"></i>
                    </button>
                </div>
            </form>

            <!-- Product Description -->
            <div class="border-t pt-6">
                <h3 class="text-lg font-semibold mb-3">Descrição</h3>
                <div class="prose max-w-none">
                    {!! nl2br(e($product->description)) !!}
                </div>
            </div>

            <!-- Product Details -->
            @if($product->short_description || $product->weight || $product->brand)
                <div class="border-t pt-6">
                    <h3 class="text-lg font-semibold mb-3">Detalhes do Produto</h3>
                    <dl class="grid grid-cols-1 gap-3">
                        @if($product->short_description)
                            <div>
                                <dt class="font-medium text-gray-900">Resumo</dt>
                                <dd class="text-gray-600">{{ $product->short_description }}</dd>
                            </div>
                        @endif
                        @if($product->weight)
                            <div>
                                <dt class="font-medium text-gray-900">Peso</dt>
                                <dd class="text-gray-600">{{ $product->weight }} {{ $product->weight_unit }}</dd>
                            </div>
                        @endif
                        @if($product->brand)
                            <div>
                                <dt class="font-medium text-gray-900">Marca</dt>
                                <dd class="text-gray-600">{{ $product->brand->name ?? $product->brand_id }}</dd>
                            </div>
                        @endif
                    </dl>
                </div>
            @endif
        </div>
    </div>

    <!-- Related Products -->
    @if($relatedProducts->count() > 0)
        <div class="mt-12">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">Produtos Relacionados</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                @foreach($relatedProducts as $relatedProduct)
                    <div class="bg-white rounded-lg shadow-sm overflow-hidden hover:shadow-md transition-shadow">
                        <img src="{{ $relatedProduct->main_image ?? '/images/no-image.png' }}"
                             alt="{{ $relatedProduct->name }}" class="w-full h-48 object-cover">
                        <div class="p-4">
                            <h3 class="font-semibold mb-2">
                                <a href="{{ route('store.products.show', $relatedProduct->slug) }}" class="text-gray-900 hover:text-blue-600">
                                    {{ Str::limit($relatedProduct->name, 30) }}
                                </a>
                            </h3>
                            <div class="text-lg font-bold text-gray-900">
                                R$ {{ number_format($relatedProduct->price, 2, ',', '.') }}
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Cross-sell Products -->
    @if($crossSellProducts->count() > 0)
        <div class="mt-12">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">Clientes que compraram este produto também compraram</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                @foreach($crossSellProducts as $crossSellProduct)
                    <div class="bg-white rounded-lg shadow-sm overflow-hidden hover:shadow-md transition-shadow">
                        <img src="{{ $crossSellProduct->main_image ?? '/images/no-image.png' }}"
                             alt="{{ $crossSellProduct->name }}" class="w-full h-48 object-cover">
                        <div class="p-4">
                            <h3 class="font-semibold mb-2">
                                <a href="{{ route('store.products.show', $crossSellProduct->slug) }}" class="text-gray-900 hover:text-blue-600">
                                    {{ Str::limit($crossSellProduct->name, 30) }}
                                </a>
                            </h3>
                            <div class="text-lg font-bold text-gray-900">
                                R$ {{ number_format($crossSellProduct->price, 2, ',', '.') }}
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Up-sell Products -->
    @if($upSellProducts->count() > 0)
        <div class="mt-12">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">Recomendados para Você</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                @foreach($upSellProducts as $upSellProduct)
                    <div class="bg-white rounded-lg shadow-sm overflow-hidden hover:shadow-md transition-shadow">
                        <img src="{{ $upSellProduct->main_image ?? '/images/no-image.png' }}"
                             alt="{{ $upSellProduct->name }}" class="w-full h-48 object-cover">
                        <div class="p-4">
                            <h3 class="font-semibold mb-2">
                                <a href="{{ route('store.products.show', $upSellProduct->slug) }}" class="text-gray-900 hover:text-blue-600">
                                    {{ Str::limit($upSellProduct->name, 30) }}
                                </a>
                            </h3>
                            <div class="text-lg font-bold text-gray-900">
                                R$ {{ number_format($upSellProduct->price, 2, ',', '.') }}
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>

<script>
function changeMainImage(imageUrl) {
    document.getElementById('mainImage').src = imageUrl;
}

function decreaseQuantity() {
    const input = document.getElementById('quantity');
    if (input.value > 1) {
        input.value = parseInt(input.value) - 1;
    }
}

function increaseQuantity() {
    const input = document.getElementById('quantity');
    const max = parseInt(input.getAttribute('max')) || 99;
    if (input.value < max) {
        input.value = parseInt(input.value) + 1;
    }
}

function addToWishlist(productId) {
    fetch(`/store/customer/wishlist/add/${productId}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Produto adicionado à lista de desejos!');
        } else {
            alert('Erro ao adicionar produto à lista de desejos');
        }
    });
}

function shareProduct() {
    if (navigator.share) {
        navigator.share({
            title: '{{ $product->name }}',
            text: '{{ $product->short_description ?? "Confira este produto!" }}',
            url: window.location.href
        });
    } else {
        // Fallback - copy to clipboard
        navigator.clipboard.writeText(window.location.href);
        alert('Link copiado para a área de transferência!');
    }
}

// Add to cart form submission
document.getElementById('addToCartForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);

    fetch(`/store/cart/add/{{ $product->id }}`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Produto adicionado ao carrinho!');
            // Update cart count in header if exists
            if (window.updateCartCount) {
                window.updateCartCount(data.cart.item_count);
            }
        } else {
            alert('Erro ao adicionar produto ao carrinho');
        }
    });
});
</script>
@endsection