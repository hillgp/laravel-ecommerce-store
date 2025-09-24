@extends('store::layouts.app')

@section('title', 'Comparação Compartilhada')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-4">Comparação Compartilhada</h1>
        <p class="text-gray-600">Comparação de produtos compartilhada por outro usuário</p>
    </div>

    @if($comparisonData['products']->isEmpty())
    <div class="text-center py-12">
        <div class="mb-6">
            <svg class="mx-auto h-24 w-24 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
            </svg>
        </div>
        <h3 class="text-lg font-medium text-gray-900 mb-2">Comparação não encontrada</h3>
        <p class="text-gray-500 mb-6">Esta comparação pode ter sido removida ou os produtos não estão mais disponíveis</p>
        <a href="{{ route('products.index') }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
            Ver Produtos
        </a>
    </div>
    @else
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <!-- Informações da comparação -->
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="text-lg font-medium text-gray-900">
                        Comparação de {{ $comparisonData['products']->count() }} produto(s)
                    </h2>
                    <p class="text-sm text-gray-500">
                        Compartilhada em {{ date('d/m/Y \à\s H:i') }}
                    </p>
                </div>
                <div class="flex space-x-2">
                    <button onclick="shareComparison()" class="inline-flex items-center px-3 py-1 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.367 2.684 3 3 0 00-5.367-2.684z" />
                        </svg>
                        Compartilhar
                    </button>
                    <a href="{{ route('products.comparison') }}" class="inline-flex items-center px-3 py-1 border border-blue-300 text-sm font-medium rounded-md text-blue-700 bg-white hover:bg-blue-50">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                        Criar Comparação
                    </a>
                </div>
            </div>
        </div>

        <!-- Tabela de comparação -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Produto
                        </th>
                        @foreach($comparisonData['products'] as $product)
                        <th scope="col" class="px-6 py-3 text-center">
                            <div class="max-w-xs mx-auto">
                                <img class="h-32 w-32 object-cover rounded-lg mx-auto mb-2" src="{{ $product->getFirstImageUrl() }}" alt="{{ $product->name }}">
                                <h3 class="text-sm font-medium text-gray-900 mb-1">{{ $product->name }}</h3>
                                <div class="text-lg font-bold text-blue-600">
                                    R$ {{ number_format($product->price, 2, ',', '.') }}
                                </div>
                                @if($product->compare_price)
                                <div class="text-sm text-gray-500 line-through">
                                    R$ {{ number_format($product->compare_price, 2, ',', '.') }}
                                </div>
                                @endif
                            </div>
                        </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($comparisonData['attributes'] as $attribute)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            {{ $attribute['label'] }}
                        </td>
                        @foreach($attribute['values'] as $value)
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                            @if($attribute['type'] === 'currency')
                                @if($value)
                                    R$ {{ number_format($value, 2, ',', '.') }}
                                @else
                                    -
                                @endif
                            @elseif($attribute['type'] === 'rating')
                                @if($value)
                                    <div class="flex items-center justify-center">
                                        @for($i = 1; $i <= 5; $i++)
                                            @if($i <= $value)
                                                <svg class="w-4 h-4 text-yellow-400 fill-current" viewBox="0 0 20 20">
                                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                                </svg>
                                            @else
                                                <svg class="w-4 h-4 text-gray-300" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                                </svg>
                                            @endif
                                        @endfor
                                        <span class="ml-1 text-sm text-gray-600">({{ number_format($value, 1) }})</span>
                                    </div>
                                @else
                                    -
                                @endif
                            @else
                                @if($value)
                                    {{ $value }}
                                @else
                                    -
                                @endif
                            @endif
                        </td>
                        @endforeach
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Resumo da comparação -->
        @if(isset($comparisonData['summary']) && !empty($comparisonData['summary']))
        <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
            <h4 class="text-lg font-medium text-gray-900 mb-4">Resumo da Comparação</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white p-4 rounded-lg">
                    <div class="text-sm text-gray-500">Faixa de Preço</div>
                    <div class="text-lg font-semibold text-gray-900">
                        R$ {{ number_format($comparisonData['summary']['price_range']['min'], 2, ',', '.') }} -
                        R$ {{ number_format($comparisonData['summary']['price_range']['max'], 2, ',', '.') }}
                    </div>
                    <div class="text-sm text-gray-500">
                        Média: R$ {{ number_format($comparisonData['summary']['price_range']['average'], 2, ',', '.') }}
                    </div>
                </div>
                <div class="bg-white p-4 rounded-lg">
                    <div class="text-sm text-gray-500">Avaliação</div>
                    <div class="text-lg font-semibold text-gray-900">
                        {{ number_format($comparisonData['summary']['rating_range']['min'], 1) }} -
                        {{ number_format($comparisonData['summary']['rating_range']['max'], 1) }}
                    </div>
                    <div class="text-sm text-gray-500">
                        Média: {{ number_format($comparisonData['summary']['rating_range']['average'], 1) }}
                    </div>
                </div>
                <div class="bg-white p-4 rounded-lg">
                    <div class="text-sm text-gray-500">Em Estoque</div>
                    <div class="text-lg font-semibold text-green-600">
                        {{ $comparisonData['summary']['in_stock_count'] }} produto(s)
                    </div>
                </div>
                <div class="bg-white p-4 rounded-lg">
                    <div class="text-sm text-gray-500">Em Promoção</div>
                    <div class="text-lg font-semibold text-blue-600">
                        {{ $comparisonData['summary']['on_sale_count'] }} produto(s)
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>

    <!-- Ações disponíveis -->
    <div class="mt-8 bg-blue-50 border border-blue-200 rounded-lg p-6">
        <h3 class="text-lg font-medium text-blue-900 mb-4">O que você pode fazer?</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <a href="{{ route('products.comparison') }}" class="flex items-center p-4 bg-white rounded-lg border border-blue-200 hover:border-blue-300">
                <svg class="w-8 h-8 text-blue-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
                <div>
                    <div class="font-medium text-blue-900">Criar sua própria comparação</div>
                    <div class="text-sm text-blue-700">Compare produtos do seu interesse</div>
                </div>
            </a>

            <a href="{{ route('products.index') }}" class="flex items-center p-4 bg-white rounded-lg border border-blue-200 hover:border-blue-300">
                <svg class="w-8 h-8 text-blue-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                </svg>
                <div>
                    <div class="font-medium text-blue-900">Ver todos os produtos</div>
                    <div class="text-sm text-blue-700">Explore nosso catálogo completo</div>
                </div>
            </a>

            <div class="flex items-center p-4 bg-white rounded-lg border border-blue-200">
                <svg class="w-8 h-8 text-blue-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div>
                    <div class="font-medium text-blue-900">Dica</div>
                    <div class="text-sm text-blue-700">Use os filtros para encontrar produtos similares</div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

<!-- Modal de compartilhamento -->
<div id="shareModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900">Compartilhar Comparação</h3>
                <button onclick="closeShareModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div class="mt-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Link para compartilhar:</label>
                <div class="flex">
                    <input type="text" id="shareUrl" class="flex-1 px-3 py-2 border border-gray-300 rounded-l-md focus:outline-none focus:ring-1 focus:ring-blue-500" readonly value="{{ request()->fullUrl() }}">
                    <button onclick="copyShareUrl()" class="px-4 py-2 bg-blue-600 text-white rounded-r-md hover:bg-blue-700">
                        Copiar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function shareComparison() {
    document.getElementById('shareModal').classList.remove('hidden');
}

function closeShareModal() {
    document.getElementById('shareModal').classList.add('hidden');
}

function copyShareUrl() {
    const shareUrlInput = document.getElementById('shareUrl');
    shareUrlInput.select();
    shareUrlInput.setSelectionRange(0, 99999);

    try {
        document.execCommand('copy');
        alert('Link copiado para a área de transferência!');
    } catch (err) {
        alert('Erro ao copiar link');
    }
}

// Fechar modal ao clicar fora
window.onclick = function(event) {
    const modal = document.getElementById('shareModal');
    if (event.target == modal) {
        modal.classList.add('hidden');
    }
}
</script>
@endpush