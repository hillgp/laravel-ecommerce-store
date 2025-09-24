@extends('store::layouts.app')

@section('title', 'Avaliações - ' . $product->name)

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Cabeçalho do Produto -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <div class="flex items-center space-x-4">
            <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="w-16 h-16 object-cover rounded-lg">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ $product->name }}</h1>
                <div class="flex items-center space-x-2 mt-1">
                    <div class="flex items-center">
                        @for($i = 1; $i <= 5; $i++)
                            <span class="text-{{ $i <= $product->rating ? 'yellow' : 'gray' }}-400">
                                {{ $i <= $product->rating ? '★' : '☆' }}
                            </span>
                        @endfor
                    </div>
                    <span class="text-gray-600">({{ $product->rating }})</span>
                    <span class="text-gray-600">•</span>
                    <span class="text-gray-600">{{ $stats['total_reviews'] }} avaliações</span>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Estatísticas -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Estatísticas</h2>

                <!-- Rating Médio -->
                <div class="text-center mb-6">
                    <div class="text-4xl font-bold text-gray-900">{{ $stats['average_rating'] }}</div>
                    <div class="flex items-center justify-center mt-2">
                        @for($i = 1; $i <= 5; $i++)
                            <span class="text-{{ $i <= $stats['average_rating'] ? 'yellow' : 'gray' }}-400 text-xl">
                                {{ $i <= $stats['average_rating'] ? '★' : '☆' }}
                            </span>
                        @endfor
                    </div>
                    <div class="text-gray-600 mt-1">{{ $stats['total_reviews'] }} avaliações</div>
                </div>

                <!-- Distribuição de Ratings -->
                <div class="space-y-2">
                    @for($i = 5; $i >= 1; $i--)
                        <div class="flex items-center space-x-2">
                            <span class="text-sm font-medium w-8">{{ $i }}★</span>
                            <div class="flex-1 bg-gray-200 rounded-full h-2">
                                <div class="bg-yellow-400 h-2 rounded-full" style="width: {{ $stats['total_reviews'] > 0 ? ($stats['rating_distribution'][$i] / $stats['total_reviews']) * 100 : 0 }}%"></div>
                            </div>
                            <span class="text-sm text-gray-600 w-8">{{ $stats['rating_distribution'][$i] ?? 0 }}</span>
                        </div>
                    @endfor
                </div>

                <!-- Avaliações Verificadas -->
                <div class="mt-6 pt-6 border-t border-gray-200">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">Compra verificada</span>
                        <span class="text-sm font-medium">{{ $stats['verified_reviews'] }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lista de Avaliações -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-semibold text-gray-900">Avaliações dos Clientes</h2>
                    @auth
                        @if($reviewService->canReviewProduct(auth()->id(), $product->id))
                            <a href="{{ route('store.reviews.create', $product) }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                                Escrever Avaliação
                            </a>
                        @endif
                    @endauth
                </div>

                <!-- Filtros -->
                <div class="flex flex-wrap gap-2 mb-6">
                    <a href="{{ route('store.reviews.index', $product) }}" class="px-3 py-1 text-sm rounded-full {{ !request('rating') && !request('verified') ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">
                        Todas
                    </a>
                    @for($i = 5; $i >= 1; $i--)
                        <a href="{{ route('store.reviews.index', ['product' => $product, 'rating' => $i]) }}" class="px-3 py-1 text-sm rounded-full {{ request('rating') == $i ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">
                            {{ $i }}★
                        </a>
                    @endfor
                    <a href="{{ route('store.reviews.index', ['product' => $product, 'verified' => 1]) }}" class="px-3 py-1 text-sm rounded-full {{ request('verified') == 1 ? 'bg-green-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">
                        Compra Verificada
                    </a>
                </div>

                <!-- Lista de Avaliações -->
                @forelse($reviews as $review)
                    <div class="border-b border-gray-200 pb-6 mb-6 last:border-b-0 last:pb-0 last:mb-0">
                        <div class="flex items-start space-x-4">
                            <!-- Avatar do Cliente -->
                            <img src="{{ $review->customer_info['avatar'] ?? '/images/default-avatar.png' }}" alt="{{ $review->customer_info['name'] }}" class="w-10 h-10 rounded-full">

                            <div class="flex-1">
                                <!-- Cabeçalho da Avaliação -->
                                <div class="flex items-center justify-between mb-2">
                                    <div>
                                        <h4 class="font-medium text-gray-900">{{ $review->customer_info['name'] }}</h4>
                                        @if($review->customer_info['verified_purchase'])
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                                Compra Verificada
                                            </span>
                                        @endif
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        {{ $review->created_at->format('d/m/Y') }}
                                    </div>
                                </div>

                                <!-- Rating -->
                                <div class="flex items-center mb-2">
                                    @for($i = 1; $i <= 5; $i++)
                                        <span class="text-{{ $i <= $review->rating ? 'yellow' : 'gray' }}-400">
                                            {{ $i <= $review->rating ? '★' : '☆' }}
                                        </span>
                                    @endfor
                                    @if($review->title)
                                        <span class="ml-2 font-medium text-gray-900">{{ $review->title }}</span>
                                    @endif
                                </div>

                                <!-- Comentário -->
                                @if($review->comment)
                                    <p class="text-gray-700 mb-3">{{ $review->comment }}</p>
                                @endif

                                <!-- Prós e Contras -->
                                @if($review->pros || $review->cons)
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-3">
                                        @if($review->pros)
                                            <div>
                                                <h5 class="text-sm font-medium text-green-700 mb-1">Prós:</h5>
                                                <p class="text-sm text-gray-600">{{ $review->pros }}</p>
                                            </div>
                                        @endif
                                        @if($review->cons)
                                            <div>
                                                <h5 class="text-sm font-medium text-red-700 mb-1">Contras:</h5>
                                                <p class="text-sm text-gray-600">{{ $review->cons }}</p>
                                            </div>
                                        @endif
                                    </div>
                                @endif

                                <!-- Imagens -->
                                @if($review->formatted_images)
                                    <div class="flex flex-wrap gap-2 mb-3">
                                        @foreach($review->formatted_images as $image)
                                            <img src="{{ $image['thumbnail'] }}" alt="Imagem da avaliação" class="w-16 h-16 object-cover rounded cursor-pointer hover:opacity-80" onclick="openImageModal('{{ $image['url'] }}')">
                                        @endforeach
                                    </div>
                                @endif

                                <!-- Estatísticas da Avaliação -->
                                <div class="flex items-center justify-between text-sm text-gray-500">
                                    <div class="flex items-center space-x-4">
                                        <button onclick="voteReview({{ $review->id }}, true)" class="flex items-center space-x-1 hover:text-green-600 {{ $review->votes->where('customer_id', auth()->id())->where('is_helpful', true)->count() > 0 ? 'text-green-600' : '' }}">
                                            <span>👍</span>
                                            <span>{{ $review->helpful_votes }}</span>
                                        </button>
                                        <button onclick="voteReview({{ $review->id }}, false)" class="flex items-center space-x-1 hover:text-red-600 {{ $review->votes->where('customer_id', auth()->id())->where('is_helpful', false)->count() > 0 ? 'text-red-600' : '' }}">
                                            <span>👎</span>
                                            <span>{{ $review->total_votes - $review->helpful_votes }}</span>
                                        </button>
                                    </div>
                                    <div>
                                        {{ $review->helpful_percentage }}% útil
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-12">
                        <div class="text-gray-400 mb-4">
                            <svg class="mx-auto h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Nenhuma avaliação encontrada</h3>
                        <p class="text-gray-500 mb-4">Seja o primeiro a avaliar este produto!</p>
                        @auth
                            @if($reviewService->canReviewProduct(auth()->id(), $product->id))
                                <a href="{{ route('store.reviews.create', $product) }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                                    Escrever Primeira Avaliação
                                </a>
                            @endif
                        @endauth
                    </div>
                @endforelse

                <!-- Paginação -->
                @if($reviews->hasPages())
                    <div class="mt-8">
                        {{ $reviews->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Modal de Imagem -->
<div id="imageModal" class="fixed inset-0 bg-black bg-opacity-75 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="relative max-w-4xl max-h-full">
            <img id="modalImage" src="" alt="Imagem da avaliação" class="max-w-full max-h-full object-contain">
            <button onclick="closeImageModal()" class="absolute top-4 right-4 text-white text-2xl hover:text-gray-300">
                ×
            </button>
        </div>
    </div>
</div>

<script>
function voteReview(reviewId, isHelpful) {
    @auth
        fetch(`/reviews/${reviewId}/vote`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ is_helpful: isHelpful })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message);
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro ao votar na avaliação');
        });
    @else
        alert('Você precisa estar logado para votar');
    @endauth
}

function openImageModal(imageUrl) {
    document.getElementById('modalImage').src = imageUrl;
    document.getElementById('imageModal').classList.remove('hidden');
}

function closeImageModal() {
    document.getElementById('imageModal').classList.add('hidden');
}
</script>
@endsection