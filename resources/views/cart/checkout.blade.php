@extends('store::layouts.app')

@section('title', 'Finalizar Compra - ' . config('store.name'))

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-4">Finalizar Compra</h1>
        <nav class="flex" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-1 md:space-x-3">
                <li class="inline-flex items-center">
                    <a href="{{ route('store.products.index') }}" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600">
                        <i class="fas fa-home mr-2"></i>
                        Início
                    </a>
                </li>
                <li>
                    <div class="flex items-center">
                        <i class="fas fa-chevron-right text-gray-400 mx-1"></i>
                        <a href="{{ route('store.cart.index') }}" class="text-sm font-medium text-gray-700 hover:text-blue-600">
                            Carrinho
                        </a>
                    </div>
                </li>
                <li>
                    <div class="flex items-center">
                        <i class="fas fa-chevron-right text-gray-400 mx-1"></i>
                        <span class="text-sm font-medium text-gray-500">Checkout</span>
                    </div>
                </li>
            </ol>
        </nav>
    </div>

    <form id="checkoutForm" class="space-y-8">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Billing Information -->
            <div class="space-y-6">
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h2 class="text-lg font-semibold mb-4">Informações de Cobrança</h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nome Completo *</label>
                            <input type="text" name="billing_address[name]" required
                                   class="w-full border border-gray-300 rounded px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                            <input type="email" name="billing_address[email]" required
                                   class="w-full border border-gray-300 rounded px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Telefone *</label>
                            <input type="tel" name="billing_address[phone]" required
                                   class="w-full border border-gray-300 rounded px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">CPF/CNPJ *</label>
                            <input type="text" name="billing_address[document]" required
                                   class="w-full border border-gray-300 rounded px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>

                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Endereço *</label>
                        <input type="text" name="billing_address[address]" required
                               class="w-full border border-gray-300 rounded px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Número *</label>
                            <input type="text" name="billing_address[number]" required
                                   class="w-full border border-gray-300 rounded px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Complemento</label>
                            <input type="text" name="billing_address[complement]"
                                   class="w-full border border-gray-300 rounded px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Bairro *</label>
                            <input type="text" name="billing_address[neighborhood]" required
                                   class="w-full border border-gray-300 rounded px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">CEP *</label>
                            <input type="text" name="billing_address[postal_code]" required
                                   class="w-full border border-gray-300 rounded px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Cidade *</label>
                            <input type="text" name="billing_address[city]" required
                                   class="w-full border border-gray-300 rounded px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Estado *</label>
                            <select name="billing_address[state]" required
                                    class="w-full border border-gray-300 rounded px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Selecione</option>
                                <option value="AC">Acre</option>
                                <option value="AL">Alagoas</option>
                                <option value="AP">Amapá</option>
                                <option value="AM">Amazonas</option>
                                <option value="BA">Bahia</option>
                                <option value="CE">Ceará</option>
                                <option value="DF">Distrito Federal</option>
                                <option value="ES">Espírito Santo</option>
                                <option value="GO">Goiás</option>
                                <option value="MA">Maranhão</option>
                                <option value="MT">Mato Grosso</option>
                                <option value="MS">Mato Grosso do Sul</option>
                                <option value="MG">Minas Gerais</option>
                                <option value="PA">Pará</option>
                                <option value="PB">Paraíba</option>
                                <option value="PR">Paraná</option>
                                <option value="PE">Pernambuco</option>
                                <option value="PI">Piauí</option>
                                <option value="RJ">Rio de Janeiro</option>
                                <option value="RN">Rio Grande do Norte</option>
                                <option value="RS">Rio Grande do Sul</option>
                                <option value="RO">Rondônia</option>
                                <option value="RR">Roraima</option>
                                <option value="SC">Santa Catarina</option>
                                <option value="SP">São Paulo</option>
                                <option value="SE">Sergipe</option>
                                <option value="TO">Tocantins</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">País *</label>
                            <input type="text" name="billing_address[country]" value="Brasil" required
                                   class="w-full border border-gray-300 rounded px-3 py-2 bg-gray-50">
                        </div>
                    </div>
                </div>

                <!-- Shipping Information -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold">Informações de Entrega</h2>
                        <label class="flex items-center">
                            <input type="checkbox" id="sameAsBilling" checked
                                   class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 mr-2">
                            <span class="text-sm text-gray-600">Mesmo que cobrança</span>
                        </label>
                    </div>

                    <div id="shippingFields" class="space-y-4" style="display: none;">
                        <!-- Same fields as billing but for shipping -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nome Completo *</label>
                                <input type="text" name="shipping_address[name]"
                                       class="w-full border border-gray-300 rounded px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Telefone *</label>
                                <input type="tel" name="shipping_address[phone]"
                                       class="w-full border border-gray-300 rounded px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Endereço *</label>
                            <input type="text" name="shipping_address[address]"
                                   class="w-full border border-gray-300 rounded px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Número *</label>
                                <input type="text" name="shipping_address[number]"
                                       class="w-full border border-gray-300 rounded px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Complemento</label>
                                <input type="text" name="shipping_address[complement]"
                                       class="w-full border border-gray-300 rounded px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Bairro *</label>
                                <input type="text" name="shipping_address[neighborhood]"
                                       class="w-full border border-gray-300 rounded px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">CEP *</label>
                                <input type="text" name="shipping_address[postal_code]"
                                       class="w-full border border-gray-300 rounded px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Cidade *</label>
                                <input type="text" name="shipping_address[city]"
                                       class="w-full border border-gray-300 rounded px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Estado *</label>
                                <select name="shipping_address[state]"
                                        class="w-full border border-gray-300 rounded px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Selecione</option>
                                    <option value="AC">Acre</option>
                                    <option value="AL">Alagoas</option>
                                    <option value="AP">Amapá</option>
                                    <option value="AM">Amazonas</option>
                                    <option value="BA">Bahia</option>
                                    <option value="CE">Ceará</option>
                                    <option value="DF">Distrito Federal</option>
                                    <option value="ES">Espírito Santo</option>
                                    <option value="GO">Goiás</option>
                                    <option value="MA">Maranhão</option>
                                    <option value="MT">Mato Grosso</option>
                                    <option value="MS">Mato Grosso do Sul</option>
                                    <option value="MG">Minas Gerais</option>
                                    <option value="PA">Pará</option>
                                    <option value="PB">Paraíba</option>
                                    <option value="PR">Paraná</option>
                                    <option value="PE">Pernambuco</option>
                                    <option value="PI">Piauí</option>
                                    <option value="RJ">Rio de Janeiro</option>
                                    <option value="RN">Rio Grande do Norte</option>
                                    <option value="RS">Rio Grande do Sul</option>
                                    <option value="RO">Rondônia</option>
                                    <option value="RR">Roraima</option>
                                    <option value="SC">Santa Catarina</option>
                                    <option value="SP">São Paulo</option>
                                    <option value="SE">Sergipe</option>
                                    <option value="TO">Tocantins</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">País *</label>
                                <input type="text" name="shipping_address[country]" value="Brasil"
                                       class="w-full border border-gray-300 rounded px-3 py-2 bg-gray-50">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Order Summary -->
            <div class="space-y-6">
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h2 class="text-lg font-semibold mb-4">Resumo do Pedido</h2>

                    <!-- Order Items -->
                    <div class="space-y-3 mb-4">
                        @foreach($cartItems as $item)
                            <div class="flex justify-between items-center">
                                <div class="flex-1">
                                    <h4 class="font-medium">{{ $item->product->name }}</h4>
                                    <p class="text-sm text-gray-600">Quantidade: {{ $item->quantity }}</p>
                                </div>
                                <span class="font-medium">R$ {{ number_format($item->quantity * $item->price, 2, ',', '.') }}</span>
                            </div>
                        @endforeach
                    </div>

                    <hr class="my-4">

                    <!-- Totals -->
                    <div class="space-y-2">
                        <div class="flex justify-between">
                            <span>Subtotal</span>
                            <span>R$ {{ number_format($cartSummary['subtotal'], 2, ',', '.') }}</span>
                        </div>

                        @if($cartSummary['discount_amount'] > 0)
                            <div class="flex justify-between text-green-600">
                                <span>Desconto</span>
                                <span>- R$ {{ number_format($cartSummary['discount_amount'], 2, ',', '.') }}</span>
                            </div>
                        @endif

                        <div class="flex justify-between">
                            <span>Frete</span>
                            <span id="shippingCost">R$ {{ number_format($cartSummary['shipping_amount'], 2, ',', '.') }}</span>
                        </div>

                        @if($cartSummary['tax_amount'] > 0)
                            <div class="flex justify-between">
                                <span>Impostos</span>
                                <span>R$ {{ number_format($cartSummary['tax_amount'], 2, ',', '.') }}</span>
                            </div>
                        @endif

                        <hr class="my-2">

                        <div class="flex justify-between text-lg font-bold">
                            <span>Total</span>
                            <span id="totalAmount">R$ {{ number_format($cartSummary['total'], 2, ',', '.') }}</span>
                        </div>
                    </div>
                </div>

                <!-- Payment Method -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h2 class="text-lg font-semibold mb-4">Método de Pagamento</h2>

                    <div class="space-y-3">
                        <label class="flex items-center p-3 border border-gray-300 rounded cursor-pointer hover:bg-gray-50">
                            <input type="radio" name="payment_method" value="credit_card" checked
                                   class="text-blue-600 focus:ring-blue-500">
                            <i class="fas fa-credit-card ml-3 text-gray-600"></i>
                            <span class="ml-3">Cartão de Crédito</span>
                        </label>

                        <label class="flex items-center p-3 border border-gray-300 rounded cursor-pointer hover:bg-gray-50">
                            <input type="radio" name="payment_method" value="boleto"
                                   class="text-blue-600 focus:ring-blue-500">
                            <i class="fas fa-barcode ml-3 text-gray-600"></i>
                            <span class="ml-3">Boleto Bancário</span>
                        </label>

                        <label class="flex items-center p-3 border border-gray-300 rounded cursor-pointer hover:bg-gray-50">
                            <input type="radio" name="payment_method" value="pix"
                                   class="text-blue-600 focus:ring-blue-500">
                            <i class="fas fa-qrcode ml-3 text-gray-600"></i>
                            <span class="ml-3">PIX</span>
                        </label>
                    </div>
                </div>

                <!-- Shipping Method -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h2 class="text-lg font-semibold mb-4">Método de Entrega</h2>

                    <div class="space-y-3">
                        @foreach($shippingMethods as $method => $config)
                            <label class="flex items-center p-3 border border-gray-300 rounded cursor-pointer hover:bg-gray-50">
                                <input type="radio" name="shipping_method" value="{{ $method }}" {{ $method === 'standard' ? 'checked' : '' }}
                                       class="text-blue-600 focus:ring-blue-500" onchange="updateShippingCost('{{ $method }}')">
                                <div class="ml-3 flex-1">
                                    <div class="font-medium">{{ $config['name'] }}</div>
                                    <div class="text-sm text-gray-600">{{ $config['description'] ?? 'Entrega padrão' }}</div>
                                </div>
                                <div class="text-right">
                                    <div class="font-medium">R$ {{ number_format($config['cost'] ?? 0, 2, ',', '.') }}</div>
                                    <div class="text-sm text-gray-600">{{ $config['days'] ?? '3-5 dias' }}</div>
                                </div>
                            </label>
                        @endforeach
                    </div>
                </div>

                <!-- Order Notes -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h2 class="text-lg font-semibold mb-4">Observações do Pedido</h2>
                    <textarea name="notes" rows="3"
                              class="w-full border border-gray-300 rounded px-3 py-2 focus:ring-blue-500 focus:border-blue-500"
                              placeholder="Alguma observação especial para o pedido?"></textarea>
                </div>

                <!-- Terms and Conditions -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <label class="flex items-start">
                        <input type="checkbox" name="terms" required
                               class="mt-1 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span class="ml-3 text-sm text-gray-700">
                            Eu li e concordo com os <a href="#" class="text-blue-600 hover:underline">Termos e Condições</a>
                            e <a href="#" class="text-blue-600 hover:underline">Política de Privacidade</a> *
                        </span>
                    </label>
                </div>

                <!-- Submit Button -->
                <button type="submit"
                        class="w-full bg-blue-600 text-white px-6 py-4 rounded-lg hover:bg-blue-700 transition-colors font-medium text-lg">
                    <i class="fas fa-lock mr-2"></i>
                    Finalizar Pedido
                </button>
            </div>
        </div>
    </form>
</div>

<script>
document.getElementById('sameAsBilling').addEventListener('change', function() {
    const shippingFields = document.getElementById('shippingFields');
    if (this.checked) {
        shippingFields.style.display = 'none';
    } else {
        shippingFields.style.display = 'block';
    }
});

function updateShippingCost(method) {
    // This would calculate shipping cost based on method
    console.log('Shipping method changed:', method);
}

document.getElementById('checkoutForm').addEventListener('submit', function(e) {
    e.preventDefault();

    // Show loading state
    const submitBtn = e.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Processando...';
    submitBtn.disabled = true;

    const formData = new FormData(this);

    fetch('/store/checkout/process', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.href = data.redirect;
        } else {
            alert(data.message || 'Erro ao processar pedido');
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }
    })
    .catch(error => {
        alert('Erro ao processar pedido. Tente novamente.');
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
});
</script>
@endsection