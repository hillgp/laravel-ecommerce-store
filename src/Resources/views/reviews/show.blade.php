@extends('store::layouts.app')

@section('title', $review->title ?? 'Avaliação - ' . $review->product->name)

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Breadcrumb -->
    <nav class="flex mb-8" aria-label="Breadcrumb">
        <ol class="inline-flex items-center space-x-1 md:space-x-3">
            <li class="inline-flex items-center">
                <a href="{{ route('store.products.index') }}" class="text-gray-700 hover:text-blue-600">Produtos</a>
            </li>
            <li>
                <div class="flex items-center">
                    <svg class="w-3 h-3 text-gray-400 mx-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/>
                    </svg>
                    <a href="{{ route('store.products.show', $review->product) }}" class="ml-1 text-gray-700 hover:text-blue-600 md:ml-2">{{ $review->product->name }}</a>
                </div>
            </li>
            <li>
                <div class="flex items-center">
                    <svg class="w-3 h-3 text-gray-400 mx-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/>
                    </svg>
                    <span class="ml-1 text-gray-500 md:ml-2">Avaliação</span>
                </div>
            </li>
        </ol>
    </nav>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Produto -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-lg shadow-md p-6 sticky top-4">
                <div class="text-center">
                    <img src="{{ $review->product->image_url }}" alt="{{ $review->product->name }}" class="w-32 h-32 object-cover rounded-lg mx-auto mb-4">
                    <h2 class="text-lg font-semibold text-gray-900 mb-2">{{ $review->product->name }}</h2>
                    <div class="flex items-center justify-center mb-4">
                        @for($i = 1; $i <= 5; $i++)
                            <span class="text-{{ $i <= $review->product->rating ? 'yellow' : 'gray' }}-400">
                                {{ $i <= $review->product->rating ? '★' : '☆' }}
                            </span>
                        @endfor
                        <span class="ml-2 text-gray-600">({{ $review->product->rating }})</span>
                    </div>
                    <a href="{{ route('store.products.show', $review->product) }}" class="block w-full bg-blue-600 text-white text-center py-2 px-4 rounded-lg hover:bg-blue-700 transition-colors">
                        Ver Produto
                    </a>
                </div>
            </div>
        </div>

        <!-- Avaliação -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow-md p-6">
                <!-- Cabeçalho da Avaliação -->
                <div class="flex items-start justify-between mb-6">
                    <div class="flex items-start space-x-4">
                        <img src="{{ $review->customer_info['avatar'] ?? '/images/default-avatar.png' }}" alt="{{ $review->customer_info['name'] }}" class="w-12 h-12 rounded-full">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">{{ $review->title ?? 'Avaliação do Produto' }}</h1>
                            <div class="flex items-center space-x-2 mt-1">
                                <span class="text-gray-600">por {{ $review->customer_info['name'] }}</span>
                                @if($review->customer_info['verified_purchase'])
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                        Compra Verificada
                                    </span>
                                @endif
                            </div>
                            <div class="flex items-center space-x-2 mt-2">
                                <div class="flex items-center">
                                    @for($i = 1; $i <= 5; $i++)
                                        <span class="text-{{ $i <= $review->rating ? 'yellow' : 'gray' }}-400 text-lg">
                                            {{ $i <= $review->rating ? '★' : '☆' }}
                                        </span>
                                    @endfor
                                </div>
                                <span class="text-gray-600">{{ $review->rating }}/5</span>
                                <span class="text-gray-400">•</span>
                                <span class="text-gray-600">{{ $review->created_at->format('d/m/Y') }}</span>
                            </div>
                        </div>
                    </div>

                    @auth
                        @if($review->customer_id === auth()->id())
                            <div class="flex space-x-2">
                                <a href="{{ route('store.reviews.edit', $review) }}" class="text-blue-600 hover:text-blue-800">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </a>
                                <form action="{{ route('store.reviews.destroy', $review) }}" method="POST" class="inline" onsubmit="return confirm('Tem certeza que deseja excluir esta avaliação?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        @endif
                    @endauth
                </div>

                <!-- Comentário -->
                @if($review->comment)
                    <div class="mb-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-3">Comentário</h3>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <p class="text-gray-700 whitespace-pre-wrap">{{ $review->comment }}</p>
                        </div>
                    </div>
                @endif

                <!-- Prós e Contras -->
                @if($review->pros || $review->cons)
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        @if($review->pros)
                            <div>
                                <h3 class="text-lg font-medium text-green-700 mb-3">Pontos Positivos</h3>
                                <div class="bg-green-50 rounded-lg p-4">
                                    <p class="text-gray-700">{{ $review->pros }}</p>
                                </div>
                            </div>
                        @endif
                        @if($review->cons)
                            <div>
                                <h3 class="text-lg font-medium text-red-700 mb-3">Pontos Negativos</h3>
                                <div class="bg-red-50 rounded-lg p-4">
                                    <p class="text-gray-700">{{ $review->cons }}</p>
                                </div>
                            </div>
                        @endif
                    </div>
                @endif

                <!-- Mídia -->
                @if($review->formatted_images || $review->formatted_videos)
                    <div class="mb-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-3">Mídia</h3>

                        <!-- Imagens -->
                        @if($review->formatted_images)
                            <div class="mb-4">
                                <h4 class="text-md font-medium text-gray-700 mb-2">Fotos</h4>
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                    @foreach($review->formatted_images as $image)
                                        <img src="{{ $image['url'] }}" alt="Foto da avaliação" class="w-full h-24 object-cover rounded-lg cursor-pointer hover:opacity-80" onclick="openImageModal('{{ $image['url'] }}')">
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <!-- Vídeos -->
                        @if($review->formatted_videos)
                            <div>
                                <h4 class="text-md font-medium text-gray-700 mb-2">Vídeos</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    @foreach($review->formatted_videos as $video)
                                        <video class="w-full h-48 object-cover rounded-lg" controls>
                                            <source src="{{ $video['url'] }}" type="video/mp4">
                                            Seu navegador não suporta o elemento de vídeo.
                                        </video>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                @endif

                <!-- Estatísticas da Avaliação -->
                <div class="border-t border-gray-200 pt-6">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-6">
                            <div class="text-center">
                                <div class="text-2xl font-bold text-gray-900">{{ $review->helpful_votes }}</div>
                                <div class="text-sm text-gray-600">Útil</div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-bold text-gray-900">{{ $review->total_votes - $review->helpful_votes }}</div>
                                <div class="text-sm text-gray-600">Não útil</div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-bold text-gray-900">{{ $review->helpful_percentage }}%</div>
                                <div class="text-sm text-gray-600">Taxa de utilidade</div>
                            </div>
                        </div>

                        @auth
                            @if($review->customer_id !== auth()->id())
                                <div class="flex items-center space-x-2">
                                    <span class="text-sm text-gray-600">Esta avaliação foi útil?</span>
                                    <button onclick="voteReview({{ $review->id }}, true)" class="px-3 py-1 text-sm bg-blue-600 text-white rounded hover:bg-blue-700 {{ $review->votes->where('customer_id', auth()->id())->where('is_helpful', true)->count() > 0 ? 'bg-green-600' : '' }}">
                                        👍 Sim
                                    </button>
                                    <button onclick="voteReview({{ $review->id }}, false)" class="px-3 py-1 text-sm bg-gray-300 text-gray-700 rounded hover:bg-gray-400 {{ $review->votes->where('customer_id', auth()->id())->where('is_helpful', false)->count() > 0 ? 'bg-red-600 text-white' : '' }}">
                                        👎 Não
                                    </button>
                                </div>
                            @endif
                        @endauth
                    </div>
                </div>

                <!-- Outras Avaliações -->
                <div class="border-t border-gray-200 pt-6 mt-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Outras Avaliações deste Produto</h3>
                    <div class="space-y-4">
                        @php
                            $otherReviews = $review->product->reviews()->where('id', '!=', $review->id)->limit(3)->get();
                        @endphp

                        @forelse($otherReviews as $otherReview)
                            <div class="flex items-start space-x-3 p-3 bg-gray-50 rounded-lg">
                                <img src="{{ $otherReview->customer_info['avatar'] ?? '/images/default-avatar.png' }}" alt="{{ $otherReview->customer_info['name'] }}" class="w-8 h-8 rounded-full">
                                <div class="flex-1">
                                    <div class="flex items-center space-x-2 mb-1">
                                        <span class="font-medium text-gray-900">{{ $otherReview->customer_info['name'] }}</span>
                                        <div class="flex items-center">
                                            @for($i = 1; $i <= 5; $i++)
                                                <span class="text-{{ $i <= $otherReview->rating ? 'yellow' : 'gray' }}-400 text-sm">
                                                    {{ $i <= $otherReview->rating ? '★' : '☆' }}
                                                </span>
                                            @endfor
                                        </div>
                                    </div>
                                    @if($otherReview->title)
                                        <h4 class="text-sm font-medium text-gray-900 mb-1">{{ $otherReview->title }}</h4>
                                    @endif
                                    @if($otherReview->comment)
                                        <p class="text-sm text-gray-600">{{ Str::limit($otherReview->comment, 100) }}</p>
                                    @endif
                                    <a href="{{ route('store.reviews.show', $otherReview) }}" class="text-sm text-blue-600 hover:text-blue-800">Ver avaliação completa</a>
                                </div>
                            </div>
                        @empty
                            <p class="text-gray-500 text-center py-4">Nenhuma outra avaliação encontrada.</p>
                        @endforelse
                    </div>

                    <div class="text-center mt-4">
                        <a href="{{ route('store.reviews.index', $review->product) }}" class="text-blue-600 hover:text-blue-800 font-medium">
                            Ver todas as avaliações ({{ $review->product->reviews->count() }})
                        </a>
                    </div>
                </div>
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