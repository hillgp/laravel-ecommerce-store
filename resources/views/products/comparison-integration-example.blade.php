{{-- Exemplo de Integração do Sistema de Comparação --}}

@extends('store::layouts.app')

@section('title', 'Produto Exemplo - Integração Comparação')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <!-- Produto Exemplo -->
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <div class="md:flex">
                <!-- Imagem do Produto -->
                <div class="md:w-1/2">
                    <img src="https://via.placeholder.com/600x600?text=Produto+Exemplo"
                         alt="Produto Exemplo"
                         class="w-full h-96 object-cover">
                </div>

                <!-- Informações do Produto -->
                <div class="md:w-1/2 p-8">
                    <div class="mb-4">
                        <span class="inline-block bg-blue-100 text-blue-800 text-sm px-2 py-1 rounded-full">
                            Categoria Exemplo
                        </span>
                    </div>

                    <h1 class="text-3xl font-bold text-gray-900 mb-4">
                        Smartphone Galaxy S23 Ultra
                    </h1>

                    <div class="flex items-center mb-4">
                        <div class="flex text-yellow-400">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star-half-alt"></i>
                        </div>
                        <span class="ml-2 text-gray-600">(4.5/5 - 128 avaliações)</span>
                    </div>

                    <div class="mb-6">
                        <span class="text-3xl font-bold text-blue-600">R$ 5.999,00</span>
                        <span class="ml-2 text-lg text-gray-500 line-through">R$ 6.999,00</span>
                        <span class="ml-2 inline-block bg-red-100 text-red-800 text-sm px-2 py-1 rounded">
                            14% OFF
                        </span>
                    </div>

                    <p class="text-gray-600 mb-6">
                        Smartphone premium com câmera de 200MP, tela Dynamic AMOLED 6.8" e
                        processador Snapdragon 8 Gen 2. Perfeito para quem busca o melhor em
                        fotografia e performance.
                    </p>

                    <!-- Especificações Rápidas -->
                    <div class="grid grid-cols-2 gap-4 mb-6">
                        <div>
                            <span class="text-gray-500">Armazenamento:</span>
                            <span class="font-semibold">256GB</span>
                        </div>
                        <div>
                            <span class="text-gray-500">RAM:</span>
                            <span class="font-semibold">12GB</span>
                        </div>
                        <div>
                            <span class="text-gray-500">Cor:</span>
                            <span class="font-semibold">Preto</span>
                        </div>
                        <div>
                            <span class="text-gray-500">Estoque:</span>
                            <span class="font-semibold text-green-600">Em estoque</span>
                        </div>
                    </div>

                    <!-- Botões de Ação -->
                    <div class="space-y-3">
                        <!-- Botão Comparar (Toggle) -->
                        <button class="btn-toggle-comparison w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 px-6 rounded-lg transition duration-200 flex items-center justify-center"
                                data-product-id="1"
                                data-in-comparison="false">
                            <i class="far fa-chart-bar mr-2"></i>
                            Comparar Produto
                        </button>

                        <!-- Botão Adicionar ao Carrinho -->
                        <button class="w-full bg-green-600 hover:bg-green-700 text-white font-medium py-3 px-6 rounded-lg transition duration-200 flex items-center justify-center">
                            <i class="fas fa-shopping-cart mr-2"></i>
                            Adicionar ao Carrinho
                        </button>

                        <!-- Botão Favoritar -->
                        <button class="w-full bg-gray-100 hover:bg-gray-200 text-gray-800 font-medium py-3 px-6 rounded-lg transition duration-200 flex items-center justify-center">
                            <i class="far fa-heart mr-2"></i>
                            Adicionar aos Favoritos
                        </button>
                    </div>

                    <!-- Link para Comparação -->
                    <div class="mt-4 text-center">
                        <a href="{{ route('comparison.index') }}" class="text-blue-600 hover:text-blue-800 text-sm">
                            <i class="fas fa-chart-bar mr-1"></i>
                            Ver Comparação (<span class="comparison-count">0</span>)
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Produtos Relacionados para Comparação -->
        <div class="mt-12">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">Compare com Outros Modelos</h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Produto 2 -->
                <div class="bg-white rounded-lg shadow-md overflow-hidden border-2 border-gray-200">
                    <img src="https://via.placeholder.com/400x400?text=iPhone+15"
                         alt="iPhone 15"
                         class="w-full h-48 object-cover">
                    <div class="p-4">
                        <h3 class="font-semibold text-lg mb-2">iPhone 15 Pro</h3>
                        <div class="text-xl font-bold text-blue-600 mb-2">R$ 7.999,00</div>
                        <button class="btn-toggle-comparison w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded transition duration-200"
                                data-product-id="2"
                                data-in-comparison="false">
                            <i class="far fa-chart-bar mr-1"></i>
                            Comparar
                        </button>
                    </div>
                </div>

                <!-- Produto 3 -->
                <div class="bg-white rounded-lg shadow-md overflow-hidden border-2 border-gray-200">
                    <img src="https://via.placeholder.com/400x400?text=Xiaomi+13"
                         alt="Xiaomi 13"
                         class="w-full h-48 object-cover">
                    <div class="p-4">
                        <h3 class="font-semibold text-lg mb-2">Xiaomi 13 Ultra</h3>
                        <div class="text-xl font-bold text-blue-600 mb-2">R$ 4.999,00</div>
                        <button class="btn-toggle-comparison w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded transition duration-200"
                                data-product-id="3"
                                data-in-comparison="false">
                            <i class="far fa-chart-bar mr-1"></i>
                            Comparar
                        </button>
                    </div>
                </div>

                <!-- Produto 4 -->
                <div class="bg-white rounded-lg shadow-md overflow-hidden border-2 border-gray-200">
                    <img src="https://via.placeholder.com/400x400?text=Moto+Edge"
                         alt="Moto Edge"
                         class="w-full h-48 object-cover">
                    <div class="p-4">
                        <h3 class="font-semibold text-lg mb-2">Motorola Edge 40</h3>
                        <div class="text-xl font-bold text-blue-600 mb-2">R$ 3.499,00</div>
                        <button class="btn-toggle-comparison w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded transition duration-200"
                                data-product-id="4"
                                data-in-comparison="false">
                            <i class="far fa-chart-bar mr-1"></i>
                            Comparar
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Instruções de Uso -->
        <div class="mt-12 bg-blue-50 border border-blue-200 rounded-lg p-6">
            <h3 class="text-lg font-semibold text-blue-900 mb-4">
                <i class="fas fa-info-circle mr-2"></i>
                Como Usar a Comparação
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm text-blue-800">
                <div class="flex items-start">
                    <i class="fas fa-plus-circle mt-1 mr-2 text-blue-600"></i>
                    <div>
                        <strong>Clique em "Comparar"</strong> nos produtos que deseja analisar lado a lado
                    </div>
                </div>
                <div class="flex items-start">
                    <i class="fas fa-eye mt-1 mr-2 text-blue-600"></i>
                    <div>
                        <strong>Visualize a comparação</strong> clicando no link "Ver Comparação" ou acesse /comparacao
                    </div>
                </div>
                <div class="flex items-start">
                    <i class="fas fa-share-alt mt-1 mr-2 text-blue-600"></i>
                    <div>
                        <strong>Compartilhe</strong> sua comparação gerando um link seguro para outras pessoas
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Exemplo de uso das funcionalidades de comparação
document.addEventListener('DOMContentLoaded', function() {
    // Atualizar contador de comparação
    window.StoreApp.updateComparisonCount();

    // Exemplo de callback após adicionar à comparação
    const comparisonButtons = document.querySelectorAll('.btn-toggle-comparison');

    comparisonButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();

            const productId = this.dataset.productId;
            const isInComparison = this.dataset.inComparison === 'true';

            if (isInComparison) {
                // Remover da comparação
                window.StoreApp.removeFromComparison(productId);
                this.innerHTML = '<i class="far fa-chart-bar mr-1"></i> Comparar';
                this.classList.remove('active');
                this.dataset.inComparison = 'false';
            } else {
                // Adicionar à comparação
                window.StoreApp.addToComparison(productId);
                this.innerHTML = '<i class="fas fa-chart-bar mr-1"></i> Remover da Comparação';
                this.classList.add('active');
                this.dataset.inComparison = 'true';
            }
        });
    });

    // Exemplo de verificação de status de comparação
    const productIds = [1, 2, 3, 4];
    window.StoreApp.makeRequest('/ajax/produtos/comparacao/status-multiplos', 'GET', {
        product_ids: productIds
    })
    .then(response => {
        if (response.success) {
            // Atualizar botões baseado no status
            Object.keys(response.status).forEach(productId => {
                const button = document.querySelector(`[data-product-id="${productId}"]`);
                if (button && response.status[productId]) {
                    button.classList.add('active');
                    button.dataset.inComparison = 'true';
                    button.innerHTML = '<i class="fas fa-chart-bar mr-1"></i> Remover da Comparação';
                }
            });
        }
    })
    .catch(error => {
        console.error('Erro ao verificar status de comparação:', error);
    });
});
</script>
@endpush

@push('styles')
<style>
.btn-toggle-comparison {
    transition: all 0.2s ease;
}

.btn-toggle-comparison:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.btn-toggle-comparison.active {
    background-color: #ef4444 !important;
}

.btn-toggle-comparison.active:hover {
    background-color: #dc2626 !important;
}

.comparison-count {
    background-color: #ef4444;
    color: white;
    border-radius: 50%;
    padding: 2px 6px;
    font-size: 0.75rem;
    font-weight: bold;
    margin-left: 4px;
    display: inline-block;
    min-width: 18px;
    text-align: center;
}

.comparison-count.hide {
    display: none;
}

.comparison-count.show {
    display: inline-block;
}
</style>
@endpush