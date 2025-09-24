@extends('store::layouts.app')

@section('title', 'Pedido Confirmado - ' . config('store.name'))

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-2xl mx-auto text-center">
        <!-- Success Icon -->
        <div class="mb-8">
            <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-check text-3xl text-green-600"></i>
            </div>
            <h1 class="text-3xl font-bold text-gray-900 mb-4">Pedido Confirmado!</h1>
            <p class="text-gray-600">Obrigado pela sua compra. Seu pedido foi recebido e está sendo processado.</p>
        </div>

        <!-- Order Information -->
        <div class="bg-white rounded-lg shadow-sm p-6 mb-8">
            <h2 class="text-lg font-semibold mb-4">Detalhes do Pedido</h2>

            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="text-gray-600">Número do Pedido:</span>
                    <span class="font-medium">{{ $order->order_number }}</span>
                </div>

                <div class="flex justify-between">
                    <span class="text-gray-600">Data:</span>
                    <span class="font-medium">{{ $order->created_at->format('d/m/Y H:i') }}</span>
                </div>

                <div class="flex justify-between">
                    <span class="text-gray-600">Status:</span>
                    <span class="font-medium">
                        @switch($order->status)
                            @case('pending')
                                <span class="bg-yellow-100 text-yellow-800 px-2 py-1 rounded text-sm">Pendente</span>
                                @break
                            @case('confirmed')
                                <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded text-sm">Confirmado</span>
                                @break
                            @case('shipped')
                                <span class="bg-purple-100 text-purple-800 px-2 py-1 rounded text-sm">Enviado</span>
                                @break
                            @case('delivered')
                                <span class="bg-green-100 text-green-800 px-2 py-1 rounded text-sm">Entregue</span>
                                @break
                            @default
                                <span class="bg-gray-100 text-gray-800 px-2 py-1 rounded text-sm">{{ ucfirst($order->status) }}</span>
                        @endswitch
                    </span>
                </div>

                <div class="flex justify-between">
                    <span class="text-gray-600">Método de Pagamento:</span>
                    <span class="font-medium">{{ $order->payment_method ?? 'Não informado' }}</span>
                </div>

                <div class="flex justify-between">
                    <span class="text-gray-600">Método de Entrega:</span>
                    <span class="font-medium">{{ $order->shipping_method ?? 'Não informado' }}</span>
                </div>

                <hr class="my-4">

                <div class="flex justify-between text-lg font-bold">
                    <span>Total:</span>
                    <span class="text-blue-600">R$ {{ number_format($order->total, 2, ',', '.') }}</span>
                </div>
            </div>
        </div>

        <!-- Order Items -->
        <div class="bg-white rounded-lg shadow-sm p-6 mb-8">
            <h2 class="text-lg font-semibold mb-4">Itens do Pedido</h2>

            <div class="space-y-4">
                @foreach($order->items as $item)
                    <div class="flex justify-between items-center">
                        <div class="flex-1">
                            <h4 class="font-medium">{{ $item->product_name }}</h4>
                            <p class="text-sm text-gray-600">Quantidade: {{ $item->quantity }}</p>
                            @if($item->options)
                                <p class="text-sm text-gray-600">
                                    @foreach($item->options as $key => $value)
                                        {{ ucfirst($key) }}: {{ $value }}
                                    @endforeach
                                </p>
                            @endif
                        </div>
                        <div class="text-right">
                            <div class="font-medium">R$ {{ number_format($item->total, 2, ',', '.') }}</div>
                            <div class="text-sm text-gray-600">R$ {{ number_format($item->price, 2, ',', '.') }} cada</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Shipping Information -->
        @if($order->shipping_address)
            <div class="bg-white rounded-lg shadow-sm p-6 mb-8">
                <h2 class="text-lg font-semibold mb-4">Endereço de Entrega</h2>

                <div class="text-gray-700">
                    <p>{{ $order->shipping_address['name'] ?? '' }}</p>
                    <p>{{ $order->shipping_address['address'] ?? '' }}, {{ $order->shipping_address['number'] ?? '' }}</p>
                    @if($order->shipping_address['complement'])
                        <p>{{ $order->shipping_address['complement'] }}</p>
                    @endif
                    <p>{{ $order->shipping_address['neighborhood'] ?? '' }}, {{ $order->shipping_address['city'] ?? '' }} - {{ $order->shipping_address['state'] ?? '' }}</p>
                    <p>CEP: {{ $order->shipping_address['postal_code'] ?? '' }}</p>
                </div>
            </div>
        @endif

        <!-- Next Steps -->
        <div class="bg-blue-50 rounded-lg p-6 mb-8">
            <h2 class="text-lg font-semibold text-blue-900 mb-3">Próximos Passos</h2>

            <div class="space-y-3 text-blue-800">
                <div class="flex items-center">
                    <i class="fas fa-envelope mr-3"></i>
                    <span>Você receberá um email de confirmação em breve</span>
                </div>

                @if($order->status === 'confirmed')
                    <div class="flex items-center">
                        <i class="fas fa-truck mr-3"></i>
                        <span>Seu pedido será preparado e enviado em até 2 dias úteis</span>
                    </div>
                @endif

                <div class="flex items-center">
                    <i class="fas fa-eye mr-3"></i>
                    <span>Acompanhe o status do seu pedido na sua conta</span>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="{{ route('store.customer.orders') }}"
               class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors font-medium">
                <i class="fas fa-list mr-2"></i>
                Ver Meus Pedidos
            </a>

            <a href="{{ route('store.products.index') }}"
               class="bg-gray-200 text-gray-700 px-6 py-3 rounded-lg hover:bg-gray-300 transition-colors font-medium">
                <i class="fas fa-shopping-bag mr-2"></i>
                Continuar Comprando
            </a>
        </div>

        <!-- Contact Information -->
        <div class="mt-8 text-center text-gray-600">
            <p>Precisa de ajuda? Entre em contato conosco:</p>
            <p class="mt-2">
                <i class="fas fa-phone mr-2"></i>
                (11) 9999-9999 |
                <i class="fas fa-envelope ml-2 mr-2"></i>
                contato@loja.com
            </p>
        </div>
    </div>
</div>
@endsection