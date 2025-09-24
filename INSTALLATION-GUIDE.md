# 🚀 Guia de Instalação - Sistema de Wishlist e Comparação de Produtos

Este guia explica como instalar e configurar os sistemas de **Wishlist/Favoritos** e **Comparação de Produtos** no seu projeto Laravel E-commerce.

---

## 📋 Pré-requisitos

- ✅ **Laravel 12.x** instalado e configurado
- ✅ **PHP 8.2+** com extensões necessárias
- ✅ **Composer** instalado
- ✅ **Banco de dados** configurado (MySQL, PostgreSQL, SQLite)
- ✅ **Projeto Laravel E-commerce** já instalado

---

## 1️⃣ Instalação do Package

### Passo 1: Adicionar ao Composer

```bash
# Se estiver usando como package
= se for desenvolvimento local
# O package já deve estar instalado no seu projeto
```

### Passo 2: Publicar Assets

```bash
# Publicar arquivos de configuração
php artisan vendor:publish --provider="LaravelEcommerce\Store\StoreServiceProvider" --tag=store-config

# Publicar views (opcional - se quiser personalizar)
php artisan vendor:publish --provider="LaravelEcommerce\Store\StoreServiceProvider" --tag=store-views

# Publicar assets (CSS/JS)
php artisan vendor:publish --provider="LaravelEcommerce\Store\StoreServiceProvider" --tag=store-assets
```

### Passo 3: Executar Migrations

```bash
# Executar todas as migrations do sistema
php artisan migrate

# Ou executar apenas as novas migrations de comparação
php artisan migrate --path=vendor/laravel-ecommerce/store/database/migrations
```

---

## 2️⃣ Configuração do Sistema

### Passo 1: Variáveis de Ambiente

Adicione/edit estas variáveis no seu arquivo `.env`:

```env
# Sistema de Wishlist
STORE_WISHLIST_ENABLED=true
STORE_WISHLIST_MAX_ITEMS=50
STORE_WISHLIST_ALLOW_GUESTS=true
STORE_WISHLIST_SHAREABLE=true
STORE_WISHLIST_PUBLIC_BY_DEFAULT=false

# Sistema de Comparação
STORE_COMPARISON_ENABLED=true
STORE_COMPARISON_MAX_ITEMS=4
STORE_COMPARISON_ALLOW_GUESTS=true
STORE_COMPARISON_SHOW_DIFFERENCES=true

# Configurações Gerais
STORE_NAME="Minha Loja Online"
STORE_EMAIL="contato@minhaloja.com.br"
STORE_CURRENCY_CODE=BRL
STORE_CURRENCY_SYMBOL="R$"
```

### Passo 2: Configuração no Código

```php
// Em config/store.php (arquivo já configurado)
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

'wishlist' => [
    'enabled' => env('STORE_WISHLIST_ENABLED', true),
    'max_items' => env('STORE_WISHLIST_MAX_ITEMS', 50),
    'allow_guests' => env('STORE_WISHLIST_ALLOW_GUESTS', true),
    'shareable' => env('STORE_WISHLIST_SHAREABLE', true),
    'public_by_default' => env('STORE_WISHLIST_PUBLIC_BY_DEFAULT', false),
],
```

---

## 3️⃣ Integração Frontend

### Passo 1: Incluir CSS e JavaScript

```html
<!-- No seu layout principal (resources/views/layouts/app.blade.php) -->

<head>
    <!-- CSS do Store -->
    <link href="{{ asset('vendor/store/css/store.css') }}" rel="stylesheet">

    <!-- Seu CSS personalizado -->
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
</head>

<body>
    <!-- Seu conteúdo -->

    <!-- JavaScript do Store -->
    <script src="{{ asset('vendor/store/js/store.js') }}"></script>

    <!-- Seu JavaScript -->
    <script src="{{ asset('js/app.js') }}"></script>
</body>
```

### Passo 2: Adicionar Meta CSRF Token

```html
<!-- No head do seu layout -->
<meta name="csrf-token" content="{{ csrf_token() }}">
```

### Passo 3: Botões de Comparação

#### Em páginas de produto:

```php
<!-- Em resources/views/products/show.blade.php -->
@php
    $comparison = \SupernovaCorp\LaravelEcommerceStore\Models\ProductComparison::getCurrent();
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

#### No header (contador):

```php
<!-- Em resources/views/layouts/header.blade.php -->
@php
    $comparisonCount = \SupernovaCorp\LaravelEcommerceStore\Models\ProductComparison::getCurrent()->products_count;
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

### Passo 4: Botões de Wishlist

```php
<!-- Em páginas de produto -->
<button class="btn btn-outline-danger btn-add-to-wishlist"
        data-product-id="{{ $product->id }}">
    <i class="far fa-heart"></i>
    Adicionar aos Favoritos
</button>
```

---

## 4️⃣ Rotas Disponíveis

### Rotas de Comparação

```php
// Página principal de comparação
GET /comparacao

// Comparação compartilhada
GET /comparacao/compartilhada/{token}

// AJAX - Adicionar produto
POST /comparacao/adicionar/{productId}

// AJAX - Remover produto
POST /comparacao/remover/{productId}

// AJAX - Limpar comparação
POST /comparacao/limpar

// AJAX - Compartilhar
POST /comparacao/compartilhar

// AJAX - Status do produto
GET /ajax/produtos/comparacao/status/{product}

// AJAX - Contador
GET /ajax/produtos/comparacao/contador

// AJAX - Toggle produto
POST /ajax/produtos/comparacao/toggle/{product}
```

### Rotas de Wishlist

```php
// Página principal da wishlist
GET /wishlist

// Wishlist compartilhada
GET /wishlist/compartilhada/{token}

// AJAX - Adicionar produto
POST /wishlist/adicionar/{productId}

// AJAX - Remover produto
POST /wishlist/remover/{productId}

// AJAX - Limpar wishlist
POST /wishlist/limpar

// AJAX - Compartilhar
POST /wishlist/compartilhar
```

---

## 5️⃣ JavaScript API

### Funções Disponíveis

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

// Atualizar contador
window.StoreApp.updateComparisonCount();

// Adicionar à wishlist
window.StoreApp.addToWishlist(productId);

// Remover da wishlist
window.StoreApp.removeFromWishlist(productId);
```

### Exemplo de Uso

```javascript
// Em uma página de produto
document.addEventListener('DOMContentLoaded', function() {
    const compareBtn = document.querySelector('.btn-toggle-comparison');

    compareBtn.addEventListener('click', function(e) {
        e.preventDefault();

        const productId = this.dataset.productId;
        const isInComparison = this.dataset.inComparison === 'true';

        if (isInComparison) {
            window.StoreApp.removeFromComparison(productId);
            this.innerHTML = '<i class="far fa-chart-bar"></i> Comparar';
            this.classList.remove('active');
        } else {
            window.StoreApp.addToComparison(productId);
            this.innerHTML = '<i class="fas fa-chart-bar"></i> Remover da Comparação';
            this.classList.add('active');
        }

        this.dataset.inComparison = !isInComparison;
    });
});
```

---

## 6️⃣ Personalização

### CSS Personalizado

```css
/* Em resources/css/app.css ou resources/sass/app.scss */

/* Personalizar botões de comparação */
.btn-toggle-comparison {
    transition: all 0.2s ease;
    border: 2px solid #3b82f6;
    background: transparent;
    color: #3b82f6;
}

.btn-toggle-comparison:hover {
    background: #3b82f6;
    color: white;
    transform: translateY(-1px);
}

.btn-toggle-comparison.active {
    background: #ef4444;
    border-color: #ef4444;
    color: white;
}

/* Contador de comparação */
.comparison-count {
    background: #ef4444;
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
```

### Views Personalizadas

```php
// Para personalizar as views, copie de:
vendor/laravel-ecommerce/store/resources/views/

// Para:
resources/views/vendor/store/

// E personalize conforme necessário
```

---

## 7️⃣ Testando a Instalação

### Passo 1: Verificar Rotas

```bash
# Listar rotas do sistema
php artisan route:list --name=comparison
php artisan route:list --name=wishlist
```

### Passo 2: Testar Funcionalidades

1. **Acesse uma página de produto**
2. **Clique em "Comparar"** - Deve adicionar à comparação
3. **Clique novamente** - Deve remover da comparação
4. **Acesse `/comparacao`** - Deve mostrar página de comparação
5. **Teste o compartilhamento** - Gere link e teste

### Passo 3: Verificar Banco de Dados

```sql
-- Verificar tabelas criadas
SHOW TABLES LIKE '%comparison%';
SHOW TABLES LIKE '%wishlist%';

-- Verificar dados
SELECT * FROM product_comparisons;
SELECT * FROM product_comparison_items;
SELECT * FROM wishlists;
SELECT * FROM wishlist_items;
```

---

## 8️⃣ Troubleshooting

### Problema: Botões não funcionam

```javascript
// Verificar se o JavaScript está carregado
console.log('StoreApp loaded:', typeof window.StoreApp);

// Verificar CSRF token
console.log('CSRF Token:', document.querySelector('meta[name="csrf-token"]'));
```

### Problema: Rotas não encontradas

```bash
# Verificar se ServiceProvider está registrado
php artisan route:list | grep comparison

# Se não aparecer, verificar se o package está instalado
composer show laravel-ecommerce/store
```

### Problema: Migrations não executam

```bash
# Verificar status das migrations
php artisan migrate:status

# Forçar execução
php artisan migrate --force
```

### Problema: Assets não carregam

```bash
# Verificar se assets foram publicados
ls -la public/vendor/store/

# Se não existir, publicar novamente
php artisan vendor:publish --tag=store-assets --force
```

---

## 9️⃣ Configurações Avançadas

### Cache e Performance

```php
// Em config/store.php
'cache' => [
    'enabled' => env('STORE_CACHE_ENABLED', true),
    'ttl' => [
        'products' => 3600,      // 1 hora
        'categories' => 7200,    // 2 horas
        'comparison' => 1800,    // 30 minutos
        'wishlist' => 1800,      // 30 minutos
    ],
],
```

### Limites e Validações

```php
// Em config/store.php
'comparison' => [
    'max_items' => 4,           // Máximo de produtos
    'session_lifetime' => 30,   // Dias para expiração
    'allow_guests' => true,     // Permitir não logados
],

'wishlist' => [
    'max_items' => 50,          // Máximo de favoritos
    'max_lists' => 5,           // Máximo de listas por usuário
    'shareable' => true,        // Permitir compartilhamento
],
```

---

## 🔧 Comandos Úteis

```bash
# Limpar cache
php artisan cache:clear
php artisan config:clear
php artisan view:clear

# Otimizar
php artisan optimize
php artisan config:cache

# Verificar logs
tail -f storage/logs/laravel.log

# Monitorar jobs (se usar queue)
php artisan queue:work
```

---

## 📚 Documentação Adicional

- 📖 **README-COMPARISON.md** - Documentação completa do sistema de comparação
- 📖 **README.md** - Documentação geral do package
- 🔗 **API Documentation** - Documentação das APIs REST
- 💻 **Exemplos** - Templates de exemplo em `resources/views/`

---

## 🎯 Status da Instalação

Após seguir este guia, você terá:

- ✅ **Sistema de Wishlist** - 100% funcional
- ✅ **Sistema de Comparação** - 100% funcional
- ✅ **Interface responsiva** - Desktop e mobile
- ✅ **API completa** - Para integrações
- ✅ **JavaScript interativo** - Funcionalidades dinâmicas
- ✅ **Documentação completa** - Para manutenção

**🎉 Parabéns! Seus sistemas de Wishlist e Comparação estão instalados e prontos para uso!**

---

## 📞 Suporte

Se encontrar algum problema:

1. **Verifique os logs**: `storage/logs/laravel.log`
2. **Teste as rotas**: Use `php artisan route:list`
3. **Verifique migrations**: Use `php artisan migrate:status`
4. **Consulte documentação**: Leia os READMEs incluídos

**Equipe Laravel E-commerce** 🚀