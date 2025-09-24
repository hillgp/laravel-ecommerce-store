# Sistema de Comparação de Produtos

Este documento descreve como usar o sistema de comparação de produtos implementado no Laravel E-commerce Store.

## 📋 Funcionalidades

### ✅ Recursos Implementados

- **Comparação lado a lado**: Visualize produtos em uma tabela comparativa
- **Limite configurável**: Máximo de 4 produtos por comparação (configurável)
- **Compartilhamento**: Links seguros para compartilhar comparações
- **Sessões**: Suporte a usuários logados e convidados
- **Interface responsiva**: Design otimizado para desktop e mobile
- **API completa**: Endpoints REST para integração
- **JavaScript interativo**: Funcionalidades dinâmicas sem recarregar página

## 🚀 Como Usar

### Para Usuários

#### 1. Adicionar Produtos à Comparação

```html
<!-- Botão para adicionar produto -->
<button class="btn btn-primary btn-add-to-comparison"
        data-product-id="{{ $product->id }}">
    <i class="fas fa-chart-bar"></i> Comparar
</button>

<!-- Ou botão toggle -->
<button class="btn btn-outline-primary btn-toggle-comparison"
        data-product-id="{{ $product->id }}">
    <i class="far fa-chart-bar"></i> Comparar
</button>
```

#### 2. Visualizar Comparação

```php
// Rota para página de comparação
Route::get('/comparacao', [ComparisonController::class, 'index'])
    ->name('comparison.index');
```

#### 3. Compartilhar Comparação

```php
// Gerar link de compartilhamento
$comparison = ProductComparison::getCurrent();
$shareUrl = $comparison->share();
```

### Para Desenvolvedores

#### 1. Model ProductComparison

```php
use SupernovaCorp\LaravelEcommerceStore\Models\ProductComparison;

// Obter comparação atual
$comparison = ProductComparison::getCurrent();

// Adicionar produto
$comparison->addProduct($productId, $notes);

// Remover produto
$comparison->removeProduct($productId);

// Verificar se produto está na comparação
$hasProduct = $comparison->hasProduct($productId);

// Obter produtos da comparação
$products = $comparison->products;

// Compartilhar comparação
$shareUrl = $comparison->share();
```

#### 2. Controller ComparisonController

```php
use SupernovaCorp\LaravelEcommerceStore\Http\Controllers\ComparisonController;

// Adicionar produto via AJAX
POST /comparacao/adicionar/{productId}

// Remover produto via AJAX
POST /comparacao/remover/{productId}

// Limpar comparação
POST /comparacao/limpar

// Compartilhar comparação
POST /comparacao/compartilhar

// Visualizar comparação compartilhada
GET /comparacao/compartilhada/{token}
```

#### 3. JavaScript API

```javascript
// Adicionar produto à comparação
window.StoreApp.addToComparison(productId);

// Remover produto da comparação
window.StoreApp.removeFromComparison(productId);

// Toggle produto na comparação
window.StoreApp.toggleComparison(productId);

// Limpar comparação
window.StoreApp.clearComparison();

// Compartilhar comparação
window.StoreApp.shareComparison();
```

## ⚙️ Configuração

### Variáveis de Ambiente

```env
# Ativar/desativar sistema de comparação
STORE_COMPARISON_ENABLED=true

# Número máximo de produtos por comparação
STORE_COMPARISON_MAX_ITEMS=4

# Permitir uso por convidados
STORE_COMPARISON_ALLOW_GUESTS=true

# Mostrar diferenças entre produtos
STORE_COMPARISON_SHOW_DIFFERENCES=true
```

### Configuração no Código

```php
// Em config/store.php
'comparison' => [
    'enabled' => env('STORE_COMPARISON_ENABLED', true),
    'max_items' => env('STORE_COMPARISON_MAX_ITEMS', 4),
    'allow_guests' => env('STORE_COMPARISON_ALLOW_GUESTS', true),
    'show_differences' => env('STORE_COMPARISON_SHOW_DIFFERENCES', true),
    'attributes' => [
        'price',
        'brand',
        'rating',
        'stock',
        'weight',
        'dimensions',
        'color',
        'size',
    ],
],
```

## 📊 Banco de Dados

### Tabelas Criadas

#### `product_comparisons`
```sql
CREATE TABLE product_comparisons (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(40) NOT NULL,
    customer_id BIGINT UNSIGNED NULL,
    name VARCHAR(255) DEFAULT 'Comparação de Produtos',
    max_products INT DEFAULT 4,
    expires_at TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT true,
    meta JSON NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL
);
```

#### `product_comparison_items`
```sql
CREATE TABLE product_comparison_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_comparison_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    sort_order INT DEFAULT 0,
    notes TEXT NULL,
    added_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    UNIQUE KEY unique_comparison_product (product_comparison_id, product_id)
);
```

## 🎨 Personalização

### Adicionar Atributos de Comparação

```php
// Em ProductComparison model
public function getComparisonDataAttribute(): array
{
    $products = $this->products;

    $comparisonFields = [
        'price' => 'Preço',
        'compare_price' => 'Preço Original',
        'stock_quantity' => 'Estoque',
        'weight' => 'Peso',
        'dimensions' => 'Dimensões',
        'brand' => 'Marca',
        'category' => 'Categoria',
        'sku' => 'SKU',
        'rating' => 'Avaliação',
        'review_count' => 'Nº de Avaliações',
        // Adicione seus campos personalizados aqui
    ];

    // ... resto da implementação
}
```

### Estilização Customizada

```css
/* Personalize os estilos em resources/css/ */
.comparison-table {
    /* Seus estilos personalizados */
}

.product-comparison-card {
    /* Estilos para cards de produto na comparação */
}
```

## 🔒 Segurança

### Tokens de Compartilhamento

- ✅ Tokens criptografados com expiração
- ✅ Validação de integridade dos dados
- ✅ Limitação de tempo de vida (30 dias padrão)
- ✅ Verificação de sessão ativa

### Validação de Dados

- ✅ Sanitização de entrada
- ✅ Validação de produto ativo
- ✅ Verificação de estoque
- ✅ Limites de quantidade

## 📱 Responsividade

### Design Mobile-First

- ✅ Tabelas com scroll horizontal
- ✅ Cards otimizados para mobile
- ✅ Modais responsivos
- ✅ Touch-friendly interactions

### Breakpoints

```css
/* Mobile */
@media (max-width: 768px) {
    .comparison-table {
        font-size: 0.875rem;
    }
}

/* Tablet */
@media (min-width: 769px) and (max-width: 1024px) {
    .comparison-table {
        font-size: 0.9rem;
    }
}

/* Desktop */
@media (min-width: 1025px) {
    .comparison-table {
        font-size: 1rem;
    }
}
```

## 🔧 Troubleshooting

### Problemas Comuns

#### 1. Produtos não aparecem na comparação
```php
// Verifique se o produto está ativo
$product = Product::find($productId);
if (!$product->is_active) {
    return false; // Produto inativo
}
```

#### 2. Limite de produtos não funciona
```php
// Verifique configuração
config('store.comparison.max_items'); // Deve ser > 0
```

#### 3. Compartilhamento não funciona
```php
// Verifique se a comparação tem produtos
if ($comparison->products->isEmpty()) {
    throw new Exception('Adicione produtos antes de compartilhar');
}
```

### Debug

```php
// Ativar debug no JavaScript
localStorage.setItem('store_debug', 'true');

// Verificar logs do navegador
console.log('Comparison debug:', comparisonData);
```

## 📈 Analytics e Métricas

### Eventos de Tracking

```javascript
// Adicionar ao carrinho após comparação
window.StoreApp.trackEvent('comparison_to_cart', {
    product_id: productId,
    comparison_id: comparisonId
});

// Compartilhamento
window.StoreApp.trackEvent('comparison_shared', {
    comparison_id: comparisonId,
    product_count: productCount
});
```

### Métricas Disponíveis

- ✅ Número total de comparações
- ✅ Produtos mais comparados
- ✅ Taxa de conversão (comparação → compra)
- ✅ Tempo médio de comparação
- ✅ Compartilhamentos por comparação

## 🚀 Performance

### Otimizações Implementadas

- ✅ Cache inteligente de dados
- ✅ Lazy loading de imagens
- ✅ Minificação de assets
- ✅ Compressão de resposta
- ✅ Índices otimizados no banco

### Recomendações

```php
// Cache de comparação
$comparison = Cache::remember(
    "comparison_{$sessionId}",
    3600, // 1 hora
    fn() => ProductComparison::getCurrent($sessionId)
);

// Otimizar queries
$products = $comparison->products()
    ->with(['category', 'brand', 'images'])
    ->get();
```

## 📚 Exemplos Completos

### Exemplo 1: Página de Produto com Botão de Comparação

```php
// Em views/products/show.blade.php
@php
    $comparison = ProductComparison::getCurrent();
    $isInComparison = $comparison->hasProduct($product->id);
@endphp

<div class="product-actions">
    <button class="btn btn-outline-primary btn-toggle-comparison {{ $isInComparison ? 'active' : '' }}"
            data-product-id="{{ $product->id }}"
            data-in-comparison="{{ $isInComparison ? 'true' : 'false' }}">
        <i class="fas fa-chart-bar"></i>
        {{ $isInComparison ? 'Remover da Comparação' : 'Comparar' }}
    </button>
</div>
```

### Exemplo 2: Widget de Contador de Comparação

```php
// Em views/layouts/header.blade.php
@php
    $comparisonCount = ProductComparison::getCurrent()->products_count;
@endphp

<div class="comparison-widget">
    <a href="{{ route('comparison.index') }}" class="comparison-link">
        <i class="fas fa-chart-bar"></i>
        Comparação
        <span class="comparison-count {{ $comparisonCount > 0 ? 'show' : 'hide' }}">
            {{ $comparisonCount }}
        </span>
    </a>
</div>
```

### Exemplo 3: API JavaScript

```javascript
// Adicionar produto à comparação
document.querySelector('.btn-add-to-comparison').addEventListener('click', function() {
    const productId = this.dataset.productId;

    window.StoreApp.addToComparison(productId)
        .then(response => {
            if (response.success) {
                // Atualizar UI
                this.classList.add('active');
                this.innerHTML = '<i class="fas fa-chart-bar"></i> Remover da Comparação';

                // Atualizar contador
                document.querySelector('.comparison-count').textContent = response.comparison_count;
            }
        })
        .catch(error => {
            console.error('Erro ao adicionar à comparação:', error);
        });
});
```

## 🎯 Próximos Passos

### Funcionalidades Futuras

- [ ] Exportação para PDF/Excel
- [ ] Filtros avançados de comparação
- [ ] Histórico de comparações
- [ ] Recomendações baseadas em comparação
- [ ] Integração com redes sociais
- [ ] A/B testing para interface

### Melhorias de Performance

- [ ] Implementar Redis para cache
- [ ] Otimizar queries com subqueries
- [ ] Adicionar índices compostos
- [ ] Implementar CDN para assets
- [ ] Compressão de imagens automática

---

## 📞 Suporte

Para dúvidas ou problemas:

1. **Documentação**: Consulte este README
2. **Issues**: Abra uma issue no repositório
3. **Logs**: Verifique os logs de erro
4. **Debug**: Use as ferramentas de debug disponíveis

---

**Desenvolvido com ❤️ pela equipe Laravel E-commerce**