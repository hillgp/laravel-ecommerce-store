@extends('store::layouts.app')

@section('title', 'Avaliações Pendentes - Administração')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Cabeçalho -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Avaliações Pendentes</h1>
                <p class="text-gray-600">Gerencie as avaliações que aguardam aprovação</p>
            </div>
            <div class="flex items-center space-x-4">
                <div class="text-right">
                    <div class="text-2xl font-bold text-blue-600">{{ $reviews->total() }}</div>
                    <div class="text-sm text-gray-600">Total pendente</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label for="search" class="block text-sm font-medium text-gray-700 mb-2">Buscar</label>
                <input type="text" id="search" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" placeholder="Produto, cliente...">
            </div>
            <div>
                <label for="rating" class="block text-sm font-medium text-gray-700 mb-2">Rating</label>
                <select id="rating" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Todos</option>
                    <option value="5">5 estrelas</option>
                    <option value="4">4 estrelas</option>
                    <option value="3">3 estrelas</option>
                    <option value="2">2 estrelas</option>
                    <option value="1">1 estrela</option>
                </select>
            </div>
            <div>
                <label for="date_from" class="block text-sm font-medium text-gray-700 mb-2">Data de</label>
                <input type="date" id="date_from" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div>
                <label for="date_to" class="block text-sm font-medium text-gray-700 mb-2">Data até</label>
                <input type="date" id="date_to" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
            </div>
        </div>
    </div>

    <!-- Lista de Avaliações -->
    <div class="bg-white rounded-lg shadow-md">
        @forelse($reviews as $review)
            <div class="border-b border-gray-200 last:border-b-0">
                <div class="p-6">
                    <div class="flex items-start justify-between">
                        <div class="flex items-start space-x-4 flex-1">
                            <!-- Produto -->
                            <div class="flex-shrink-0">
                                <img src="{{ $review->product->image_url }}" alt="{{ $review->product->name }}" class="w-16 h-16 object-cover rounded-lg">
                            </div>

                            <!-- Conteúdo da Avaliação -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center space-x-2 mb-2">
                                    <h3 class="text-lg font-semibold text-gray-900">{{ $review->product->name }}</h3>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                        Pendente
                                    </span>
                                </div>

                                <div class="flex items-center space-x-4 mb-3">
                                    <div class="flex items-center space-x-1">
                                        @for($i = 1; $i <= 5; $i++)
                                            <span class="text-{{ $i <= $review->rating ? 'yellow' : 'gray' }}-400">
                                                {{ $i <= $review->rating ? '★' : '☆' }}
                                            </span>
                                        @endfor
                                        <span class="ml-2 text-gray-600">{{ $review->rating }}/5</span>
                                    </div>
                                    <div class="text-gray-600">
                                        por <span class="font-medium">{{ $review->customer->name }}</span>
                                    </div>
                                    <div class="text-gray-600">
                                        {{ $review->created_at->format('d/m/Y H:i') }}
                                    </div>
                                </div>

                                @if($review->title)
                                    <h4 class="font-medium text-gray-900 mb-2">{{ $review->title }}</h4>
                                @endif

                                @if($review->comment)
                                    <p class="text-gray-700 mb-3">{{ Str::limit($review->comment, 200) }}</p>
                                @endif

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

                                @if($review->formatted_images || $review->formatted_videos)
                                    <div class="flex items-center space-x-2">
                                        @if($review->formatted_images)
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                {{ count($review->formatted_images) }} foto(s)
                                            </span>
                                        @endif
                                        @if($review->formatted_videos)
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                                {{ count($review->formatted_videos) }} vídeo(s)
                                            </span>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Ações -->
                        <div class="flex items-center space-x-2 ml-4">
                            <button onclick="viewReview({{ $review->id }})" class="px-3 py-1 text-sm bg-gray-100 text-gray-700 rounded hover:bg-gray-200">
                                Ver Detalhes
                            </button>
                            <button onclick="approveReview({{ $review->id }})" class="px-3 py-1 text-sm bg-green-600 text-white rounded hover:bg-green-700">
                                Aprovar
                            </button>
                            <button onclick="rejectReview({{ $review->id }})" class="px-3 py-1 text-sm bg-red-600 text-white rounded hover:bg-red-700">
                                Rejeitar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="p-12 text-center">
                <div class="text-gray-400 mb-4">
                    <svg class="mx-auto h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Nenhuma avaliação pendente</h3>
                <p class="text-gray-500">Todas as avaliações foram processadas!</p>
            </div>
        @endforelse
    </div>

    <!-- Paginação -->
    @if($reviews->hasPages())
        <div class="mt-8">
            {{ $reviews->links() }}
        </div>
    @endif
</div>

<!-- Modal de Detalhes -->
<div id="reviewModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg max-w-4xl w-full max-h-[90vh] overflow-y-auto">
            <div class="p-6">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-semibold text-gray-900">Detalhes da Avaliação</h2>
                    <button onclick="closeReviewModal()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div id="reviewDetails">
                    <!-- Conteúdo será carregado via AJAX -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Rejeição -->
<div id="rejectModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg max-w-md w-full">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Rejeitar Avaliação</h3>
                <form id="rejectForm">
                    <div class="mb-4">
                        <label for="reject_reason" class="block text-sm font-medium text-gray-700 mb-2">
                            Motivo da Rejeição
                        </label>
                        <textarea name="reason" id="reject_reason" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" placeholder="Explique o motivo da rejeição..."></textarea>
                    </div>
                    <div class="flex items-center justify-end space-x-3">
                        <button type="button" onclick="closeRejectModal()" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                            Cancelar
                        </button>
                        <button type="submit" class="px-4 py-2 bg-red-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-red-700">
                            Rejeitar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
let currentReviewId = null;

function viewReview(reviewId) {
    currentReviewId = reviewId;

    fetch(`/api/admin/reviews/${reviewId}`, {
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('reviewDetails').innerHTML = generateReviewDetails(data.data);
            document.getElementById('reviewModal').classList.remove('hidden');
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao carregar detalhes da avaliação');
    });
}

function generateReviewDetails(review) {
    return `
        <div class="space-y-6">
            <div class="flex items-center space-x-4">
                <img src="${review.product.image_url}" alt="${review.product.name}" class="w-16 h-16 object-cover rounded-lg">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">${review.product.name}</h3>
                    <p class="text-gray-600">por ${review.customer.name}</p>
                </div>
            </div>

            <div class="flex items-center space-x-2">
                <div class="flex items-center">
                    ${Array.from({length: 5}, (_, i) => `<span class="text-${i < review.rating ? 'yellow' : 'gray'}-400">${i < review.rating ? '★' : '☆'}</span>`).join('')}
                </div>
                <span class="text-gray-600">${review.rating}/5</span>
            </div>

            ${review.title ? `<h4 class="text-lg font-medium text-gray-900">${review.title}</h4>` : ''}

            ${review.comment ? `<div class="bg-gray-50 rounded-lg p-4"><p class="text-gray-700 whitespace-pre-wrap">${review.comment}</p></div>` : ''}

            ${review.pros || review.cons ? `
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    ${review.pros ? `<div><h5 class="text-sm font-medium text-green-700 mb-1">Prós:</h5><p class="text-sm text-gray-600">${review.pros}</p></div>` : ''}
                    ${review.cons ? `<div><h5 class="text-sm font-medium text-red-700 mb-1">Contras:</h5><p class="text-sm text-gray-600">${review.cons}</p></div>` : ''}
                </div>
            ` : ''}

            ${review.images || review.videos ? `
                <div>
                    <h5 class="text-sm font-medium text-gray-700 mb-2">Mídia</h5>
                    ${review.images ? `<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">${review.images.map(img => `<img src="${img.url}" alt="Foto" class="w-full h-24 object-cover rounded">`).join('')}</div>` : ''}
                    ${review.videos ? `<div class="grid grid-cols-1 md:grid-cols-2 gap-4">${review.videos.map(vid => `<video class="w-full h-48 object-cover rounded" controls><source src="${vid.url}" type="video/mp4"></video>`).join('')}</div>` : ''}
                </div>
            ` : ''}
        </div>
    `;
}

function approveReview(reviewId) {
    if (confirm('Tem certeza que deseja aprovar esta avaliação?')) {
        fetch(`/api/admin/reviews/${reviewId}/approve`, {
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
                alert(data.message);
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro ao aprovar avaliação');
        });
    }
}

function rejectReview(reviewId) {
    currentReviewId = reviewId;
    document.getElementById('rejectModal').classList.remove('hidden');
}

function closeReviewModal() {
    document.getElementById('reviewModal').classList.add('hidden');
}

function closeRejectModal() {
    document.getElementById('rejectModal').classList.add('hidden');
    document.getElementById('reject_reason').value = '';
}

// Formulário de rejeição
document.getElementById('rejectForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const reason = document.getElementById('reject_reason').value;

    fetch(`/api/admin/reviews/${currentReviewId}/reject`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ reason: reason })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeRejectModal();
            location.reload();
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao rejeitar avaliação');
    });
});

// Filtros
document.getElementById('search').addEventListener('input', filterReviews);
document.getElementById('rating').addEventListener('change', filterReviews);
document.getElementById('date_from').addEventListener('change', filterReviews);
document.getElementById('date_to').addEventListener('change', filterReviews);

function filterReviews() {
    const search = document.getElementById('search').value;
    const rating = document.getElementById('rating').value;
    const dateFrom = document.getElementById('date_from').value;
    const dateTo = document.getElementById('date_to').value;

    // Implementar lógica de filtro (pode ser via AJAX ou redirecionamento)
    const url = new URL(window.location);
    if (search) url.searchParams.set('search', search);
    else url.searchParams.delete('search');

    if (rating) url.searchParams.set('rating', rating);
    else url.searchParams.delete('rating');

    if (dateFrom) url.searchParams.set('date_from', dateFrom);
    else url.searchParams.delete('date_from');

    if (dateTo) url.searchParams.set('date_to', dateTo);
    else url.searchParams.delete('date_to');

    window.location.href = url.toString();
}
</script>
@endsection