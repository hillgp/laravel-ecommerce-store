@extends('store::layouts.app')

@section('title', 'Escrever Avaliação - ' . $product->name)

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Cabeçalho -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <div class="flex items-center space-x-4">
            <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="w-16 h-16 object-cover rounded-lg">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Escrever Avaliação</h1>
                <p class="text-gray-600">{{ $product->name }}</p>
            </div>
        </div>
    </div>

    <form action="{{ route('store.reviews.store', $product) }}" method="POST" enctype="multipart/form-data" class="max-w-4xl mx-auto">
        @csrf

        <div class="bg-white rounded-lg shadow-md p-6">
            <!-- Rating -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Avaliação Geral <span class="text-red-500">*</span>
                </label>
                <div class="flex items-center space-x-1">
                    @for($i = 1; $i <= 5; $i++)
                        <input type="radio" name="rating" value="{{ $i }}" id="rating-{{ $i }}" class="sr-only" {{ old('rating') == $i ? 'checked' : '' }}>
                        <label for="rating-{{ $i }}" class="cursor-pointer">
                            <span class="text-2xl star-rating {{ $i <= old('rating', 0) ? 'text-yellow-400' : 'text-gray-300' }}" data-rating="{{ $i }}">★</span>
                        </label>
                    @endfor
                </div>
                @error('rating')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-sm text-gray-500">Clique nas estrelas para avaliar</p>
            </div>

            <!-- Título -->
            <div class="mb-6">
                <label for="title" class="block text-sm font-medium text-gray-700 mb-2">
                    Título da Avaliação
                </label>
                <input type="text" name="title" id="title" value="{{ old('title') }}" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" placeholder="Resuma sua experiência em poucas palavras">
                @error('title')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Comentário -->
            <div class="mb-6">
                <label for="comment" class="block text-sm font-medium text-gray-700 mb-2">
                    Comentário Detalhado
                </label>
                <textarea name="comment" id="comment" rows="6" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" placeholder="Compartilhe sua experiência detalhada com este produto...">{{ old('comment') }}</textarea>
                @error('comment')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-sm text-gray-500">Mínimo 10 caracteres recomendado</p>
            </div>

            <!-- Prós e Contras -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label for="pros" class="block text-sm font-medium text-gray-700 mb-2">
                        Pontos Positivos
                    </label>
                    <textarea name="pros" id="pros" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" placeholder="O que você gostou neste produto?">{{ old('pros') }}</textarea>
                    @error('pros')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="cons" class="block text-sm font-medium text-gray-700 mb-2">
                        Pontos Negativos
                    </label>
                    <textarea name="cons" id="cons" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500" placeholder="O que poderia ser melhorado?">{{ old('cons') }}</textarea>
                    @error('cons')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Upload de Imagens -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Fotos do Produto (Opcional)
                </label>
                <div class="border-2 border-dashed border-gray-300 rounded-lg p-6">
                    <div class="text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                            <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        <div class="mt-4">
                            <label for="images" class="cursor-pointer">
                                <span class="mt-2 block text-sm font-medium text-gray-900">Adicionar fotos</span>
                                <span class="mt-1 block text-xs text-gray-500">PNG, JPG, GIF até 2MB cada</span>
                                <input type="file" name="images[]" id="images" multiple accept="image/*" class="sr-only" onchange="previewImages(this)">
                            </label>
                        </div>
                    </div>
                </div>
                <div id="image-preview" class="mt-4 grid grid-cols-2 md:grid-cols-4 gap-4"></div>
                @error('images.*')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Upload de Vídeos -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Vídeos do Produto (Opcional)
                </label>
                <div class="border-2 border-dashed border-gray-300 rounded-lg p-6">
                    <div class="text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                            <path d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        <div class="mt-4">
                            <label for="videos" class="cursor-pointer">
                                <span class="mt-2 block text-sm font-medium text-gray-900">Adicionar vídeos</span>
                                <span class="mt-1 block text-xs text-gray-500">MP4, MOV, AVI até 10MB cada</span>
                                <input type="file" name="videos[]" id="videos" multiple accept="video/*" class="sr-only" onchange="previewVideos(this)">
                            </label>
                        </div>
                    </div>
                </div>
                <div id="video-preview" class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4"></div>
                @error('videos.*')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Aviso -->
            <div class="bg-blue-50 border border-blue-200 rounded-md p-4 mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-blue-800">Dicas para uma boa avaliação</h3>
                        <div class="mt-2 text-sm text-blue-700">
                            <ul class="list-disc list-inside space-y-1">
                                <li>Seja específico sobre os aspectos que gostou ou não gostou</li>
                                <li>Mencione o contexto de uso do produto</li>
                                <li>Fotos e vídeos ajudam outros clientes</li>
                                <li>Seja respeitoso e construtivo</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Botões -->
            <div class="flex items-center justify-end space-x-4">
                <a href="{{ route('store.reviews.index', $product) }}" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Cancelar
                </a>
                <button type="submit" class="px-4 py-2 bg-blue-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Enviar Avaliação
                </button>
            </div>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const ratingInputs = document.querySelectorAll('input[name="rating"]');
    const stars = document.querySelectorAll('.star-rating');

    // Sistema de rating com estrelas
    ratingInputs.forEach((input, index) => {
        input.addEventListener('change', function() {
            const rating = parseInt(this.value);

            stars.forEach((star, starIndex) => {
                if (starIndex < rating) {
                    star.classList.remove('text-gray-300');
                    star.classList.add('text-yellow-400');
                } else {
                    star.classList.remove('text-yellow-400');
                    star.classList.add('text-gray-300');
                }
            });
        });
    });

    // Preview de imagens
    window.previewImages = function(input) {
        const preview = document.getElementById('image-preview');
        preview.innerHTML = '';

        if (input.files) {
            Array.from(input.files).forEach((file, index) => {
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const div = document.createElement('div');
                        div.className = 'relative';
                        div.innerHTML = `
                            <img src="${e.target.result}" class="w-full h-24 object-cover rounded">
                            <button type="button" onclick="removeFile('images', ${index})" class="absolute top-1 right-1 bg-red-500 text-white rounded-full w-5 h-5 text-xs">×</button>
                        `;
                        preview.appendChild(div);
                    };
                    reader.readAsDataURL(file);
                }
            });
        }
    };

    // Preview de vídeos
    window.previewVideos = function(input) {
        const preview = document.getElementById('video-preview');
        preview.innerHTML = '';

        if (input.files) {
            Array.from(input.files).forEach((file, index) => {
                if (file.type.startsWith('video/')) {
                    const div = document.createElement('div');
                    div.className = 'relative';
                    div.innerHTML = `
                        <video class="w-full h-24 object-cover rounded">
                            <source src="${URL.createObjectURL(file)}" type="${file.type}">
                        </video>
                        <button type="button" onclick="removeFile('videos', ${index})" class="absolute top-1 right-1 bg-red-500 text-white rounded-full w-5 h-5 text-xs">×</button>
                    `;
                    preview.appendChild(div);
                }
            });
        }
    };

    // Remover arquivo
    window.removeFile = function(type, index) {
        const input = document.getElementById(type);
        const dt = new DataTransfer();

        Array.from(input.files).forEach((file, i) => {
            if (i !== index) {
                dt.items.add(file);
            }
        });

        input.files = dt.files;

        // Recarregar preview
        if (type === 'images') {
            previewImages(input);
        } else {
            previewVideos(input);
        }
    };
});
</script>

<style>
.star-rating {
    transition: color 0.2s ease;
}

.star-rating:hover {
    color: #fbbf24 !important;
}

input[type="radio"]:checked ~ .star-rating {
    color: #fbbf24 !important;
}
</style>
@endsection