@extends('store::layouts.app')

@section('title', 'Produtos - ' . config('store.name'))

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-4">Nossos Produtos</h1>
        <p class="text-gray-600">Descubra nossa coleção completa de produtos de qualidade</p>
    </div>

    <div class="flex flex-col lg:flex-row gap-8">
        <!-- Sidebar Filters -->
        <div class="lg:w-1/4">
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-semibold mb-4">Filtros</h3>

                <!-- Categories Filter -->
                <div class="mb-6">
                    <h4 class="font-medium mb-3">Categorias</h4>
                    <div class="space-y-2">
                        @foreach($categories as $category)
                            <label class="flex items-center">
                                <input type="checkbox" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" value="{{ $category->id }}" onchange="updateFilters()">
                                <span class="ml-2 text-sm text-gray-700">{{ $category->name }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <!-- Price Filter -->
                <div class="mb-6">
                    <h4 class="font-medium mb-3">Preço</h4>
                    <div class="space-y-2">
                        <input type="range" class="w-full" min="0" max="1000" value="1000" id="priceRange">
                        <div class="flex justify-between text-sm text-gray-600">
                            <span>R$ 0</span>
                            <span>R$ 1000+</span>
                        </div>
                    </div>
                </div>

                <!-- Rating Filter -->
                <div class="mb-6">
                    <h4 class="font-medium mb-3">Avaliação</h4>
                    <div class="space-y-2">
                        @for($i = 5; $i >= 1; $i--)
                            <label class="flex items-center">
                                <input type="checkbox" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" value="{{ $i }}" onchange="updateFilters()">
                                <span class="ml-2 text-sm text-gray-700">{{ $i }} estrelas</span>
                            </label>
                        @endfor
                    </div>
                </div>

                <!-- Special Filters -->
                <div class="mb-6">
                    <h4 class="font-medium mb-3">Tipo</h4>
                    <div class="space-y-2">
                        <label class="flex items-center">
                            <input type="checkbox" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" value="featured" onchange="updateFilters()">
                            <span class="ml-2 text-sm text-gray-700">Destaques</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" value="on_sale" onchange="updateFilters()">
                            <span class="ml-2 text-sm text-gray-700">Em Promoção</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" value="new_arrival" onchange="updateFilters()">
                            <span class="ml-2 text-sm text-gray-700">Novidades</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Products Grid -->
        <div class="lg:w-3/4">
            <!-- Sort and View Options -->
            <div class="bg-white rounded-lg shadow-sm p-4 mb-6 flex justify-between items-center">
                <div class="flex items-center space-x-4">
                    <span class="text-sm text-gray-600">{{ $products->total() }} produtos encontrados</span>
                </div>
                <div class="flex items-center space-x-4">
                    <select class="border border-gray-300 rounded-md px-3 py-1 text-sm" onchange="updateSort(this.value)">
                        <option value="newest">Mais Recentes</option>
                        <option value="price_asc">Menor Preço</option>
                        <option value="price_desc">Maior Preço</option>
                        <option value="name_asc">Nome A-Z</option>
                        <option value="name_desc">Nome Z-A</option>
                        <option value="rating">Melhor Avaliados</option>
                        <option value="popular">Mais Populares</option>
                    </select>
                    <div class="flex border border-gray-300 rounded-md">
                        <button class="px-3 py-1 text-sm bg-gray-100" onclick="changeView('grid')">
                            <i class="fas fa-th"></i>
                        </button>
                        <button class="px-3 py-1 text-sm" onclick="changeView('list')">
                            <i class="fas fa-list"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Products Grid -->
            <div id="productsContainer" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @forelse($products as $product)
                    <div class="bg-white rounded-lg shadow-sm overflow-hidden hover:shadow-md transition-shadow">
                        <!-- Product Image -->
                        <div class="relative">
                            <img src="{{ $product->main_image ?? '/images/no-image.png' }}"
                                 alt="{{ $product->name }}"
                                 class="w-full h-48 object-cover">
                            @if($product->on_sale)
                                <div class="absolute top-2 left-2 bg-red-500 text-white px-2 py-1 rounded text-xs font-semibold">
                                    {{ $product->discount_percentage }}% OFF
                                </div>
                            @endif
                            @if($product->is_new_arrival)
                                <div class="absolute top-2 right-2 bg-blue-500 text-white px-2 py-1 rounded text-xs">
                                    Novo
                                </div>
                            @endif
                        </div>

                        <!-- Product Info -->
                        <div class="p-4">
                            <h3 class="font-semibold text-lg mb-2">
                                <a href="{{ route('store.products.show', $product->slug) }}" class="text-gray-900 hover:text-blue-600">
                                    {{ Str::limit($product->name, 50) }}
                                </a>
                            </h3>

                            <div class="flex items-center mb-2">
                                <div class="flex text-yellow-400">
                                    @for($i = 1; $i <= 5; $i++)
                                        <i class="fas fa-star {{ $i <= $product->rating ? 'text-yellow-400' : 'text-gray-300' }} text-sm"></i>
                                    @endfor
                                </div>
                                <span class="ml-2 text-sm text-gray-600">({{ $product->review_count }})</span>
                            </div>

                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-2">
                                    @if($product->compare_price)
                                        <span class="text-lg font-bold text-gray-900">
                                            R$ {{ number_format($product->price, 2, ',', '.') }}
                                        </span>
                                        <span class="text-sm text-gray-500 line-through">
                                            R$ {{ number_format($product->compare_price, 2, ',', '.') }}
                                        </span>
                                    @else
                                        <span class="text-lg font-bold text-gray-900">
                                            R$ {{ number_format($product->price, 2, ',', '.') }}
                                        </span>
                                    @endif
                                </div>

                                @if($product->is_in_stock)
                                    <span class="text-xs bg-green-100 text-green-800 px-2 py-1 rounded">
                                        Em Estoque
                                    </span>
                                @else
                                    <span class="text-xs bg-red-100 text-red-800 px-2 py-1 rounded">
                                        Esgotado
                                    </span>
                                @endif
                            </div>

                            <p class="text-sm text-gray-600 mt-2 mb-4">
                                {{ Str::limit($product->short_description ?? $product->description, 100) }}
                            </p>

                            <!-- Actions -->
                            <div class="flex space-x-2">
                                <button onclick="addToCart({{ $product->id }})"
                                        class="flex-1 bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition-colors text-sm"
                                        {{ $product->is_in_stock ? '' : 'disabled' }}>
                                    <i class="fas fa-cart-plus mr-1"></i>
                                    {{ $product->is_in_stock ? 'Adicionar ao Carrinho' : 'Indisponível' }}
                                </button>
                                <button onclick="addToWishlist({{ $product->id }})"
                                        class="bg-gray-200 text-gray-700 px-3 py-2 rounded hover:bg-gray-300 transition-colors">
                                    <i class="fas fa-heart"></i>
                                </button>
                                <a href="{{ route('store.products.show', $product->slug) }}"
                                   class="bg-gray-200 text-gray-700 px-3 py-2 rounded hover:bg-gray-300 transition-colors">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full text-center py-12">
                        <i class="fas fa-box-open text-6xl text-gray-300 mb-4"></i>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Nenhum produto encontrado</h3>
                        <p class="text-gray-600">Tente ajustar os filtros ou pesquise por outros termos.</p>
                    </div>
                @endforelse
            </div>

            <!-- Pagination -->
            @if($products->hasPages())
                <div class="mt-8">
                    {{ $products->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

<script>
function updateFilters() {
    // This would update the products based on selected filters
    console.log('Filters updated');
}

function updateSort(sort) {
    // This would update the sort order
    console.log('Sort updated:', sort);
}

function changeView(view) {
    const container = document.getElementById('productsContainer');
    if (view === 'list') {
        container.className = 'space-y-4';
    } else {
        container.className = 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6';
    }
}

function addToCart(productId) {
    fetch(`/store/cart/add/${productId}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            quantity: 1
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Produto adicionado ao carrinho!');
        } else {
            alert('Erro ao adicionar produto ao carrinho');
        }
    });
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
</script>
@endsection