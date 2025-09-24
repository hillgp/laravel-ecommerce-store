<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title')</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Custom Styles -->
    <style>
        .dropdown:hover .dropdown-menu {
            display: block;
        }
    </style>

    @stack('styles')
</head>
<body class="font-sans antialiased bg-gray-50">
    <!-- Header -->
    <header class="bg-white shadow-sm">
        <!-- Top Bar -->
        <div class="bg-gray-900 text-white text-sm">
            <div class="container mx-auto px-4 py-2">
                <div class="flex justify-between items-center">
                    <div class="flex space-x-4">
                        <span><i class="fas fa-phone mr-1"></i> (11) 9999-9999</span>
                        <span><i class="fas fa-envelope mr-1"></i> contato@loja.com</span>
                    </div>
                    <div class="flex space-x-4">
                        @auth('customer')
                            <span>Olá, {{ Auth::guard('customer')->user()->name }}!</span>
                            <a href="{{ route('store.customer.dashboard') }}" class="hover:text-blue-300">Minha Conta</a>
                            <form method="POST" action="{{ route('store.customer.logout') }}" class="inline">
                                @csrf
                                <button type="submit" class="hover:text-blue-300">Sair</button>
                            </form>
                        @else
                            <a href="{{ route('store.customer.create') }}" class="hover:text-blue-300">Cadastrar</a>
                            <a href="{{ route('store.customer.login') }}" class="hover:text-blue-300">Entrar</a>
                        @endauth
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Header -->
        <div class="container mx-auto px-4 py-4">
            <div class="flex items-center justify-between">
                <!-- Logo -->
                <div class="flex-shrink-0">
                    <a href="{{ route('store.products.index') }}" class="text-2xl font-bold text-gray-900">
                        {{ config('store.name', 'Loja Virtual') }}
                    </a>
                </div>

                <!-- Search Bar -->
                <div class="flex-1 max-w-2xl mx-8">
                    <form action="{{ route('store.products.search') }}" method="GET" class="relative">
                        <input type="text" name="q" placeholder="Buscar produtos..."
                               class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                        <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                        <button type="submit" class="absolute right-2 top-1/2 transform -translate-y-1/2 bg-blue-600 text-white px-4 py-1 rounded hover:bg-blue-700">
                            Buscar
                        </button>
                    </form>
                </div>

                <!-- Header Actions -->
                <div class="flex items-center space-x-4">
                    <!-- Wishlist -->
                    <a href="{{ route('store.customer.wishlist') }}" class="text-gray-600 hover:text-blue-600">
                        <i class="fas fa-heart text-xl"></i>
                        <span class="text-sm">Lista de Desejos</span>
                    </a>

                    <!-- Cart -->
                    <a href="{{ route('store.cart.index') }}" class="text-gray-600 hover:text-blue-600 relative">
                        <i class="fas fa-shopping-cart text-xl"></i>
                        <span class="text-sm">Carrinho</span>
                        <span id="cartCount" class="absolute -top-2 -right-2 bg-blue-600 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center">0</span>
                    </a>

                    <!-- User Menu -->
                    @auth('customer')
                        <div class="relative dropdown">
                            <button class="flex items-center text-gray-600 hover:text-blue-600">
                                <i class="fas fa-user text-xl"></i>
                                <i class="fas fa-chevron-down ml-1"></i>
                            </button>
                            <div class="dropdown-menu absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-10 hidden">
                                <a href="{{ route('store.customer.dashboard') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    <i class="fas fa-tachometer-alt mr-2"></i> Dashboard
                                </a>
                                <a href="{{ route('store.customer.orders') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    <i class="fas fa-list mr-2"></i> Meus Pedidos
                                </a>
                                <a href="{{ route('store.customer.addresses') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    <i class="fas fa-address-book mr-2"></i> Endereços
                                </a>
                                <a href="{{ route('store.customer.reviews') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    <i class="fas fa-star mr-2"></i> Avaliações
                                </a>
                                <hr class="my-1">
                                <form method="POST" action="{{ route('store.customer.logout') }}">
                                    @csrf
                                    <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                        <i class="fas fa-sign-out-alt mr-2"></i> Sair
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endauth
                </div>
            </div>
        </div>

        <!-- Navigation -->
        <nav class="bg-gray-800 text-white">
            <div class="container mx-auto px-4">
                <div class="flex items-center justify-between">
                    <div class="flex space-x-8">
                        <a href="{{ route('store.products.index') }}" class="py-4 px-2 text-sm font-medium hover:text-blue-300 transition-colors">
                            Todos os Produtos
                        </a>

                        <!-- Categories Dropdown -->
                        <div class="relative dropdown">
                            <button class="py-4 px-2 text-sm font-medium hover:text-blue-300 transition-colors flex items-center">
                                Categorias
                                <i class="fas fa-chevron-down ml-1"></i>
                            </button>
                            <div class="dropdown-menu absolute left-0 mt-0 w-64 bg-white text-gray-800 rounded-md shadow-lg py-1 z-10 hidden">
                                @foreach(\LaravelEcommerce\Store\Models\Category::roots()->active()->ordered()->get() as $category)
                                    <a href="{{ route('store.categories.show', $category->slug) }}"
                                       class="block px-4 py-2 text-sm hover:bg-gray-100">
                                        {{ $category->name }}
                                    </a>
                                @endforeach
                            </div>
                        </div>

                        <a href="#" class="py-4 px-2 text-sm font-medium hover:text-blue-300 transition-colors">
                            Ofertas
                        </a>

                        <a href="#" class="py-4 px-2 text-sm font-medium hover:text-blue-300 transition-colors">
                            Novidades
                        </a>

                        <a href="#" class="py-4 px-2 text-sm font-medium hover:text-blue-300 transition-colors">
                            Contato
                        </a>
                    </div>

                    <div class="flex items-center space-x-4">
                        <a href="#" class="text-sm hover:text-blue-300">
                            <i class="fab fa-facebook"></i>
                        </a>
                        <a href="#" class="text-sm hover:text-blue-300">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" class="text-sm hover:text-blue-300">
                            <i class="fab fa-whatsapp"></i>
                        </a>
                    </div>
                </div>
            </div>
        </nav>
    </header>

    <!-- Main Content -->
    <main>
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white mt-12">
        <div class="container mx-auto px-4 py-12">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <!-- Company Info -->
                <div class="col-span-1 md:col-span-2">
                    <h3 class="text-xl font-bold mb-4">{{ config('store.name', 'Loja Virtual') }}</h3>
                    <p class="text-gray-300 mb-4">
                        {{ config('store.description', 'Sua loja virtual completa com os melhores produtos e preços.') }}
                    </p>
                    <div class="flex space-x-4">
                        <a href="#" class="text-gray-300 hover:text-white">
                            <i class="fab fa-facebook text-xl"></i>
                        </a>
                        <a href="#" class="text-gray-300 hover:text-white">
                            <i class="fab fa-instagram text-xl"></i>
                        </a>
                        <a href="#" class="text-gray-300 hover:text-white">
                            <i class="fab fa-whatsapp text-xl"></i>
                        </a>
                    </div>
                </div>

                <!-- Quick Links -->
                <div>
                    <h4 class="font-semibold mb-4">Links Rápidos</h4>
                    <ul class="space-y-2">
                        <li><a href="{{ route('store.products.index') }}" class="text-gray-300 hover:text-white">Produtos</a></li>
                        <li><a href="{{ route('store.cart.index') }}" class="text-gray-300 hover:text-white">Carrinho</a></li>
                        <li><a href="{{ route('store.customer.orders') }}" class="text-gray-300 hover:text-white">Meus Pedidos</a></li>
                        <li><a href="{{ route('store.customer.wishlist') }}" class="text-gray-300 hover:text-white">Lista de Desejos</a></li>
                    </ul>
                </div>

                <!-- Customer Service -->
                <div>
                    <h4 class="font-semibold mb-4">Atendimento</h4>
                    <ul class="space-y-2 text-gray-300">
                        <li><i class="fas fa-phone mr-2"></i> (11) 9999-9999</li>
                        <li><i class="fas fa-envelope mr-2"></i> contato@loja.com</li>
                        <li><i class="fas fa-clock mr-2"></i> Seg-Sex: 8h às 18h</li>
                        <li><i class="fas fa-map-marker-alt mr-2"></i> São Paulo, SP</li>
                    </ul>
                </div>
            </div>

            <hr class="my-8 border-gray-700">

            <div class="flex flex-col md:flex-row justify-between items-center">
                <p class="text-gray-300 text-sm">
                    © {{ date('Y') }} {{ config('store.name', 'Loja Virtual') }}. Todos os direitos reservados.
                </p>
                <div class="flex space-x-6 mt-4 md:mt-0">
                    <a href="#" class="text-gray-300 hover:text-white text-sm">Política de Privacidade</a>
                    <a href="#" class="text-gray-300 hover:text-white text-sm">Termos de Uso</a>
                    <a href="#" class="text-gray-300 hover:text-white text-sm">Trocas e Devoluções</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script>
        // Update cart count
        function updateCartCount(count) {
            document.getElementById('cartCount').textContent = count;
        }

        // Mobile menu toggle (if needed)
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize cart count
            fetch('/store/cart/count')
                .then(response => response.json())
                .then(data => {
                    if (data.count !== undefined) {
                        updateCartCount(data.count);
                    }
                })
                .catch(error => console.log('Error fetching cart count:', error));
        });
    </script>

    @stack('scripts')
</body>
</html>