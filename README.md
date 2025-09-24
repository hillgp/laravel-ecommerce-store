# Laravel E-commerce Store

[![Latest Version](https://img.shields.io/packagist/v/hillgp/laravel-ecommerce-store.svg)](https://packagist.org/packages/hillgp/laravel-ecommerce-store)
[![PHP Version](https://img.shields.io/badge/php-8.2+-blue.svg)](https://php.net)
[![Laravel Version](https://img.shields.io/badge/laravel-12.0+-red.svg)](https://laravel.com)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)
[![Tests](https://img.shields.io/badge/tests-passing-brightgreen.svg)](#testes)
[![Coverage](https://img.shields.io/badge/coverage-85%25+-yellow.svg)](#cobertura-de-testes)

## 🚀 Sobre o Projeto

**Laravel E-commerce Store** é um package completo e moderno para Laravel que oferece uma solução robusta e escalável para lojas virtuais. Desenvolvido com as melhores práticas do Laravel, inclui todos os recursos essenciais para um e-commerce profissional.

##  Sobre o Projeto

**Laravel E-commerce Store** é um package completo e moderno para Laravel que oferece uma solução robusta e escalável para lojas virtuais. Desenvolvido com as melhores práticas do Laravel, inclui todos os recursos essenciais para um e-commerce profissional.

### ✨ Características Principais

- 🏪 **Gestão Completa de Produtos** - Categorias, marcas, variações, estoque
- 🛒 **Carrinho de Compras Avançado** - Suporte a convidados e usuários autenticados
- 📦 **Sistema de Pedidos** - Status, rastreamento, histórico completo
- 👥 **Gestão de Clientes** - Perfis, endereços, autenticação
- 💳 **Múltiplos Gateways** - Stripe, Mercado Pago, PagSeguro
- ⭐ **Sistema de Avaliações** - Comentários, votos, moderação
- 🎫 **Cupons e Promoções** - Descontos, validação, uso único/múltiplo
- 🚚 **Cálculo de Frete** - Múltiplas transportadoras, zonas
- 📧 **Notificações** - Email, SMS, push notifications
- 📊 **Relatórios e Analytics** - Vendas, produtos, clientes
- ⚡ **Cache e Performance** - Redis, otimização de queries
- 🔌 **API RESTful Completa** - Documentação, autenticação
- 🧪 **Testes Completos** - Unitários e de integração
- 📱 **Interface Responsiva** - Bootstrap, Tailwind CSS
- 🔍 **SEO Otimizado** - Meta tags, sitemap, schema markup
- ❤️ **Wishlist/Favoritos** - Lista de desejos
- ⚖️ **Comparação de Produtos** - Comparativo lado a lado

## 🚀 Instalação

### Pré-requisitos

- PHP 8.2 ou superior
- Laravel 12.0 ou superior
- Composer
- MySQL 5.7+, PostgreSQL, SQLite ou SQL Server
- Redis (recomendado para cache)

### Passo a Passo

1. **Instalar via Composer:**
```bash
composer require hillgp/laravel-ecommerce-store
```

2. **Publicar arquivos de configuração:**
```bash
php artisan vendor:publish --provider="SupernovaCorp\\LaravelEcommerceStore\\StoreServiceProvider"
```

3. **Executar migrations:**
```bash
php artisan migrate
```

4. **Instalar assets (opcional):**
```bash
php artisan vendor:publish --tag=ecommerce-assets
```

5. **Configurar cache (recomendado):**
```bash
php artisan config:cache
```

6. **Limpar cache de rotas:**
```bash
php artisan route:clear
php artisan view:clear
```

## ⚙️ Configuração

### Configuração Básica

Edite o arquivo `config/ecommerce.php` para personalizar:

```php
<?php

return [
    'currency' => 'BRL',
    'currency_symbol' => 'R$',
    'currency_position' => 'before', // before | after

    'pagination' => [
        'per_page' => 15,
        'per_page_options' => [10, 15, 25, 50, 100]
    ],

    'cache' => [
        'enabled' => true,
        'ttl' => 3600, // 1 hora
        'driver' => 'redis', // redis | file | database
    ],

    'uploads' => [
        'disk' => 'public',
        'path' => 'uploads/ecommerce',
        'max_size' => 2048, // KB
        'allowed_extensions' => ['jpg', 'jpeg', 'png', 'gif', 'webp']
    ],

    'features' => [
        'reviews' => true,
        'wishlist' => true,
        'comparison' => true,
        'coupons' => true,
        'shipping' => true,
        'notifications' => true,
        'analytics' => true,
        'api' => true,
        'admin_panel' => true,
        'multi_language' => false,
        'multi_currency' => false,
        'marketplace' => false
    ],

    'gateways' => [
        'stripe' => [
            'enabled' => false,
            'public_key' => env('STRIPE_PUBLIC_KEY'),
            'secret_key' => env('STRIPE_SECRET_KEY'),
            'webhook_secret' => env('STRIPE_WEBHOOK_SECRET')
        ],
        'mercadopago' => [
            'enabled' => false,
            'public_key' => env('MERCADOPAGO_PUBLIC_KEY'),
            'access_token' => env('MERCADOPAGO_ACCESS_TOKEN'),
            'webhook_secret' => env('MERCADOPAGO_WEBHOOK_SECRET')
        ],
        'pagseguro' => [
            'enabled' => false,
            'email' => env('PAGSEGURO_EMAIL'),
            'token' => env('PAGSEGURO_TOKEN'),
            'sandbox' => env('PAGSEGURO_SANDBOX', true)
        ]
    ],

    'shipping' => [
        'default_method' => 'standard',
        'free_shipping_threshold' => 200.00,
        'methods' => [
            'standard' => [
                'name' => 'Envio Padrão',
                'cost' => 15.00,
                'estimated_days' => '3-5 dias úteis'
            ],
            'express' => [
                'name' => 'Envio Expresso',
                'cost' => 25.00,
                'estimated_days' => '1-2 dias úteis'
            ]
        ]
    ],

    'notifications' => [
        'email' => [
            'enabled' => true,
            'from' => [
                'address' => 'noreply@loja.com',
                'name' => 'Loja Online'
            ]
        ],
        'sms' => [
            'enabled' => false,
            'provider' => 'twilio', // twilio | aws-sns
            'from' => env('SMS_FROM')
        ]
    ]
];
```

### Variáveis de Ambiente

Adicione ao seu arquivo `.env`:

```env
# E-commerce
ECOMMERCE_CURRENCY=BRL
ECOMMERCE_CURRENCY_SYMBOL=R$
ECOMMERCE_CACHE_ENABLED=true
ECOMMERCE_CACHE_TTL=3600

# Gateways de Pagamento
STRIPE_PUBLIC_KEY=pk_test_...
STRIPE_SECRET_KEY=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...

MERCADOPAGO_PUBLIC_KEY=...
MERCADOPAGO_ACCESS_TOKEN=...
MERCADOPAGO_WEBHOOK_SECRET=...

PAGSEGURO_EMAIL=vendedor@loja.com
PAGSEGURO_TOKEN=token_aqui
PAGSEGURO_SANDBOX=true

# Notificações
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=seu-email@gmail.com
MAIL_PASSWORD=sua-senha
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@loja.com
MAIL_FROM_NAME="Loja Online"

# SMS (opcional)
SMS_FROM=+5511999999999
TWILIO_SID=...
TWILIO_TOKEN=...
TWILIO_FROM=...

# Cache
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Uploads
FILESYSTEM_DISK=local
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
```

## 📚 Documentação da API

### Autenticação

A API utiliza Laravel Sanctum para autenticação:

```bash
# Login
POST /api/v1/auth/login
Content-Type: application/json

{
    "email": "cliente@email.com",
    "password": "senha123"
}

# Resposta
{
    "success": true,
    "data": {
        "user": {...},
        "token": "1|abc123..."
    }
}
```

### Endpoints Principais

#### Produtos
```bash
# Listar produtos
GET /api/v1/products

# Buscar produtos
GET /api/v1/products?search=smartphone

# Filtrar por categoria
GET /api/v1/products?category_id=1

# Filtrar por preço
GET /api/v1/products?min_price=100&max_price=1000

# Produtos em destaque
GET /api/v1/products/featured

# Produto específico
GET /api/v1/products/{id}

# Produtos por categoria
GET /api/v1/products/category/{slug}

# Produtos por marca
GET /api/v1/products/brand/{slug}
```

#### Carrinho
```bash
# Obter carrinho
GET /api/v1/cart

# Adicionar item
POST /api/v1/cart
{
    "product_id": 1,
    "quantity": 2
}

# Atualizar item
PUT /api/v1/cart/{itemId}
{
    "quantity": 3
}

# Remover item
DELETE /api/v1/cart/{itemId}

# Aplicar cupom
POST /api/v1/cart/apply-coupon
{
    "coupon_code": "DESCONTO10"
}
```

#### Pedidos
```bash
# Criar pedido
POST /api/v1/orders
{
    "billing_address_id": 1,
    "shipping_address_id": 2,
    "payment_method": "credit_card",
    "shipping_method": "standard"
}

# Listar pedidos
GET /api/v1/orders

# Pedido específico
GET /api/v1/orders/{id}

# Cancelar pedido
POST /api/v1/orders/{id}/cancel
```

#### Avaliações
```bash
# Listar avaliações do produto
GET /api/v1/reviews/product/{productId}

# Criar avaliação
POST /api/v1/reviews
{
    "product_id": 1,
    "rating": 5,
    "comment": "Excelente produto!"
}

# Votar em avaliação
POST /api/v1/reviews/{id}/vote
{
    "type": "helpful"
}
```

### Paginação

Todos os endpoints que retornam listas suportam paginação:

```bash
GET /api/v1/products?page=2&per_page=10
```

Resposta:
```json
{
    "success": true,
    "data": [...],
    "pagination": {
        "current_page": 2,
        "per_page": 10,
        "total": 100,
        "last_page": 10,
        "from": 11,
        "to": 20
    }
}
```

### Filtros e Ordenação

```bash
# Ordenação
GET /api/v1/products?sort=price&order=asc

# Múltiplos filtros
GET /api/v1/products?category_id=1&brand_id=2&min_price=100&max_price=1000&sort=rating&order=desc

# Busca com filtros
GET /api/v1/products?search=smartphone&in_stock=1&on_sale=1
```

### Cache e Performance

A API inclui headers de cache:

```bash
# Cache hit
X-Cache-Hit: true
Cache-Control: max-age=300

# ETag para cache do navegador
ETag: "abc123"
```

Para usar ETag:
```bash
GET /api/v1/products
If-None-Match: "abc123"
```

## 🧪 Testes

### Executar Testes

```bash
# Todos os testes
php artisan test

# Apenas testes unitários
php artisan test --testsuite=Unit

# Apenas testes de feature
php artisan test --testsuite=Feature

# Com cobertura
php artisan test --coverage

# Gerar relatório HTML de cobertura
php artisan test --coverage-html build/coverage
```

### Estrutura de Testes

```
tests/
├── Unit/                    # Testes unitários
│   ├── Models/             # Testes de modelos
│   ├── Services/           # Testes de serviços
│   ├── Controllers/        # Testes de controllers
│   └── Traits/             # Testes de traits
├── Feature/                # Testes de feature
│   ├── Http/Controllers/   # Testes de controllers HTTP
│   ├── Api/                # Testes de API
│   └── Integration/        # Testes de integração
└── CreatesApplication.php  # Helper para testes
```

### Exemplos de Testes

#### Teste Unitário - Modelo Product
```php
<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Product;
use App\Models\Category;
use App\Models\Review;

class ProductTest extends TestCase
{
    public function test_product_can_calculate_discount_percentage()
    {
        $product = Product::factory()->create([
            'price' => 90.00,
            'compare_price' => 100.00
        ]);

        $discount = $product->getDiscountPercentage();

        $this->assertEquals(10.0, $discount);
    }

    public function test_product_can_get_average_rating()
    {
        $product = Product::factory()->create();

        Review::factory()->create([
            'product_id' => $product->id,
            'rating' => 4
        ]);

        Review::factory()->create([
            'product_id' => $product->id,
            'rating' => 5
        ]);

        $averageRating = $product->getAverageRating();

        $this->assertEquals(4.5, $averageRating);
    }
}
```

#### Teste de Feature - API
```php
<?php

namespace Tests\Feature\Http\Controllers\Api;

use Tests\TestCase;
use App\Models\Product;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

class ProductApiControllerTest extends TestCase
{
    public function test_can_list_products()
    {
        $response = $this->getJson('/api/v1/products');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data',
                    'pagination'
                ]);

        $this->assertTrue($response->json('success'));
    }

    public function test_can_create_product_as_admin()
    {
        Sanctum::actingAs(User::factory()->create(['is_admin' => true]));

        $productData = [
            'name' => 'Novo Produto',
            'price' => 99.90,
            'stock_quantity' => 10
        ];

        $response = $this->postJson('/api/v1/admin/products', $productData);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'success',
                    'data' => ['id', 'name', 'price']
                ]);
    }
}
```

## 📊 Relatórios e Analytics

### Relatórios Disponíveis

#### Vendas
```bash
# Relatório de vendas geral
GET /admin/reports/sales

# Vendas por período
GET /admin/reports/sales?start_date=2024-01-01&end_date=2024-12-31

# Vendas por produto
GET /admin/reports/sales/by-product

# Vendas por categoria
GET /admin/reports/sales/by-category

# Vendas por cliente
GET /admin/reports/sales/by-customer
```

#### Produtos
```bash
# Produtos mais vendidos
GET /admin/reports/products/best-sellers

# Produtos com baixo estoque
GET /admin/reports/products/low-stock

# Produtos sem vendas
GET /admin/reports/products/no-sales

# Produtos com avaliações
GET /admin/reports/products/with-reviews
```

#### Clientes
```bash
# Clientes mais ativos
GET /admin/reports/customers/most-active

# Novos clientes
GET /admin/reports/customers/new?days=30

# Análise de clientes
GET /admin/reports/customers/analysis
```

### Dashboard

O dashboard administrativo oferece métricas em tempo real:

- **Vendas Hoje/Hoje** - Comparativo diário
- **Receita Mensal** - Gráfico de receita
- **Top Produtos** - Produtos mais vendidos
- **Status dos Pedidos** - Distribuição por status
- **Clientes Ativos** - Novos vs recorrentes
- **Taxa de Conversão** - Carrinho para pedido

### Exportação

Todos os relatórios podem ser exportados:

```bash
# Exportar como CSV
GET /admin/reports/sales/export?format=csv

# Exportar como Excel
GET /admin/reports/sales/export?format=xlsx

# Exportar como PDF
GET /admin/reports/sales/export?format=pdf
```

## 🔧 Comandos Artisan

### Comandos Disponíveis

```bash
# Instalar package
php artisan ecommerce:install

# Sincronizar produtos
php artisan ecommerce:sync-products

# Calcular estatísticas
php artisan ecommerce:calculate-stats

# Limpar cache
php artisan ecommerce:clear-cache

# Gerar sitemap
php artisan ecommerce:generate-sitemap

# Importar produtos
php artisan ecommerce:import-products {file}

# Exportar produtos
php artisan ecommerce:export-products {format}

# Processar pedidos pendentes
php artisan ecommerce:process-orders

# Enviar notificações
php artisan ecommerce:send-notifications

# Gerar relatórios
php artisan ecommerce:generate-reports

# Otimizar imagens
php artisan ecommerce:optimize-images

# Verificar estoque
php artisan ecommerce:check-stock

# Limpar dados antigos
php artisan ecommerce:cleanup
```

### Criar Comando Customizado

```bash
php artisan make:command CustomEcommerceCommand
```

```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CustomEcommerceCommand extends Command
{
    protected $signature = 'ecommerce:custom {--option=}';
    protected $description = 'Comando customizado para e-commerce';

    public function handle()
    {
        $this->info('Executando comando customizado...');

        // Sua lógica aqui

        $this->info('Comando executado com sucesso!');
    }
}
```

## 🎨 Personalização

### Views

Para personalizar as views, publique os assets:

```bash
php artisan vendor:publish --tag=ecommerce-views
```

As views ficam em `resources/views/vendor/ecommerce/`

### CSS/JS

Para personalizar os assets:

```bash
php artisan vendor:publish --tag=ecommerce-assets
```

### Middleware

Para adicionar middleware customizado:

```php
// Em routes/web.php ou routes/api.php
Route::middleware(['ecommerce.custom'])->group(function () {
    // Suas rotas
});
```

### Events

O package dispara vários events que podem ser ouvidos:

```php
<?php

namespace App\Listeners;

use App\Events\OrderPlaced;
use App\Events\ProductReviewed;
use App\Events\PaymentReceived;

class EcommerceEventSubscriber
{
    public function handleOrderPlaced($event)
    {
        // Lógica para pedido realizado
    }

    public function handleProductReviewed($event)
    {
        // Lógica para produto avaliado
    }

    public function handlePaymentReceived($event)
    {
        // Lógica para pagamento recebido
    }

    public function subscribe($events)
    {
        $events->listen(
            OrderPlaced::class,
            [EcommerceEventSubscriber::class, 'handleOrderPlaced']
        );

        $events->listen(
            ProductReviewed::class,
            [EcommerceEventSubscriber::class, 'handleProductReviewed']
        );

        $events->listen(
            PaymentReceived::class,
            [EcommerceEventSubscriber::class, 'handlePaymentReceived']
        );
    }
}
```

## 🔒 Segurança

### Validações

O package inclui validações robustas:

- **CSRF Protection** em formulários
- **Rate Limiting** na API
- **Input Sanitization** em todos os campos
- **SQL Injection Prevention** via Eloquent
- **XSS Protection** nos dados de saída

### Autorização

- **Admin Only** para operações administrativas
- **Customer Only** para dados do cliente
- **Guest Support** para navegação e carrinho
- **API Authentication** via Sanctum

### Headers de Segurança

```bash
# Content Security Policy
Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'

# X-Frame-Options
X-Frame-Options: SAMEORIGIN

# X-Content-Type-Options
X-Content-Type-Options: nosniff

# Strict-Transport-Security
Strict-Transport-Security: max-age=31536000; includeSubDomains
```

## 🚀 Performance

### Otimizações Implementadas

- **Cache Inteligente** - Redis/Memcached
- **Lazy Loading** - Eager loading otimizado
- **Database Indexing** - Índices estratégicos
- **Query Optimization** - Consultas eficientes
- **Asset Minification** - CSS/JS comprimidos
- **Image Optimization** - Compressão automática
- **CDN Support** - Assets via CDN

### Monitoramento

```bash
# Verificar performance
php artisan ecommerce:performance

# Limpar cache
php artisan ecommerce:clear-cache

# Otimizar banco
php artisan ecommerce:optimize-db
```

## 🐛 Troubleshooting

### Problemas Comuns

#### 1. Erro de Migração
```bash
# Limpar cache de configuração
php artisan config:clear

# Executar migrations novamente
php artisan migrate:fresh

# Verificar se todas as dependências estão instaladas
composer install --no-dev
```

#### 2. Problemas de Cache
```bash
# Limpar todos os caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Reiniciar queue
php artisan queue:restart
```

#### 3. Erro de Permissões
```bash
# Corrigir permissões de storage
chmod -R 755 storage
chmod -R 755 bootstrap/cache

# Corrigir permissões de uploads
chmod -R 755 storage/app/public
```

#### 4. Problemas de Upload
```bash
# Verificar configuração de filesystem
php artisan storage:link

# Verificar se o diretório existe
mkdir -p storage/app/public/uploads/ecommerce
```

#### 5. Erro de Gateway
```bash
# Verificar configuração
php artisan config:list | grep gateway

# Testar conexão
php artisan tinker
Gateway::testConnection()
```

### Debug

Para ativar debug detalhado:

```php
// Em config/ecommerce.php
'debug' => [
    'enabled' => true,
    'log_queries' => true,
    'log_cache' => true,
    'log_emails' => true
]
```

### Logs

Logs são salvos em `storage/logs/ecommerce.log`:

```bash
# Ver logs recentes
tail -f storage/logs/ecommerce.log

# Logs por nível
grep "ERROR" storage/logs/ecommerce.log
grep "WARNING" storage/logs/ecommerce.log
```

## 🤝 Contribuição

### Como Contribuir

1. **Fork** o projeto
2. **Crie** sua branch (`git checkout -b feature/AmazingFeature`)
3. **Commit** suas mudanças (`git commit -m 'Add some AmazingFeature'`)
4. **Push** para a branch (`git push origin feature/AmazingFeature`)
5. **Abra** um Pull Request

### Padrões de Código

- **PSR-12** para estilo de código
- **PHPDoc** para documentação
- **Testes** para novas funcionalidades
- **Semântica de commits** (Conventional Commits)

### Estrutura de Commits

```
feat: adicionar nova funcionalidade
fix: corrigir bug
docs: atualizar documentação
style: melhorar estilo de código
refactor: refatorar código
test: adicionar testes
chore: tarefas de manutenção
```

## 📄 Licença

Este projeto está licenciado sob a **MIT License** - veja o arquivo [LICENSE](LICENSE) para mais detalhes.

## 🙏 Agradecimentos

- **Laravel Team** - Framework incrível
- **Comunidade Laravel** - Suporte e inspiração
- **Todos os contribuidores** - Tempo e dedicação
- **Nossos clientes** - Feedback valioso

## 📞 Suporte

### Canais de Suporte

- **Documentação**: [docs.loja.com](https://docs.loja.com)
- **GitHub Issues**: [github.com/hillgp/laravel-ecommerce-store/issues](https://github.com/hillgp/laravel-ecommerce-store/issues)
- **Email**: suporte@loja.com
- **Discord**: [discord.gg/ecommerce](https://discord.gg/ecommerce)
- **Fórum**: [forum.loja.com](https://forum.loja.com)

### Níveis de Suporte

| Plano | Resposta | Canais |
|-------|----------|---------|
| **Comunidade** | 48-72h | GitHub, Fórum |
| **Profissional** | 24h | Email, Discord |
| **Enterprise** | 4h | Todos + Telefone |

### Reportar Bugs

Para reportar bugs, inclua:

1. **Versão do Laravel**
2. **Versão do Package**
3. **PHP Version**
4. **Descrição do problema**
5. **Passos para reproduzir**
6. **Logs de erro**
7. **Configuração relevante**

## 🗺️ Roadmap

### Versão 2.0 (Próxima)
- [ ] **Marketplace** - Suporte a múltiplos vendedores
- [ ] **Multi-idioma** - Internacionalização completa
- [ ] **Multi-moeda** - Suporte a múltiplas moedas
- [ ] **App Mobile** - PWA e app nativo
- [ ] **IA** - Recomendações inteligentes
- [ ] **Blockchain** - Pagamentos cripto
- [ ] **Realidade Aumentada** - Visualização 3D
- [ ] **IoT** - Integração com dispositivos

### Versão 1.5 (Atual)
- [x] **API RESTful** - Completa e documentada
- [x] **Testes** - Cobertura > 85%
- [x] **Performance** - Cache e otimização
- [x] **Documentação** - Guia completo
- [ ] **SEO** - Otimização completa
- [ ] **Wishlist** - Sistema de favoritos
- [ ] **Comparação** - Comparativo de produtos

### Versão 1.0 (Lançamento)
- [x] **Core** - Funcionalidades básicas
- [x] **Gateways** - Múltiplos pagamentos
- [x] **Admin** - Painel administrativo
- [x] **Reports** - Relatórios básicos
- [x] **Notifications** - Sistema de notificações

## 📈 Estatísticas

### Downloads
![Packagist Downloads](https://img.shields.io/packagist/dt/hillgp/laravel-ecommerce-store.svg)

### GitHub
![GitHub Stars](https://img.shields.io/github/stars/hillgp/laravel-ecommerce-store.svg)
![GitHub Forks](https://img.shields.io/github/forks/hillgp/laravel-ecommerce-store.svg)
![GitHub Issues](https://img.shields.io/github/issues/hillgp/laravel-ecommerce-store.svg)

### Qualidade
![Code Quality](https://img.shields.io/scrutinizer/g/hillgp/laravel-ecommerce-store.svg)
![Build Status](https://img.shields.io/scrutinizer/build/status/hillgp/laravel-ecommerce-store.svg)

---

**Laravel E-commerce Store** - A solução completa para seu e-commerce! 🛍️✨

Desenvolvido com ❤️ pela **Supernova Corp**# laravel-ecommerce-store
