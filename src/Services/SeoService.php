<?php

namespace SupernovaCorp\LaravelEcommerceStore\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Collection;
use Carbon\Carbon;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Page;

class SeoService
{
    protected CacheService $cacheService;
    protected array $config;

    public function __construct(CacheService $cacheService)
    {
        $this->cacheService = $cacheService;
        $this->config = config('ecommerce.seo', []);
    }

    /**
     * Gera dados SEO para produtos
     */
    public function getProductSeoData(Product $product): array
    {
        $title = $product->meta_title ?: $product->name . ' - ' . config('app.name');
        $description = $product->meta_description ?: $this->generateProductDescription($product);
        $keywords = $this->generateProductKeywords($product);

        return [
            'title' => $title,
            'description' => $description,
            'keywords' => $keywords,
            'canonical' => $this->generateProductCanonical($product),
            'og' => $this->generateProductOpenGraph($product),
            'twitter' => $this->generateProductTwitter($product),
            'schema' => $this->generateProductSchema($product),
            'breadcrumbs' => $this->generateProductBreadcrumbs($product)
        ];
    }

    /**
     * Gera dados SEO para categorias
     */
    public function getCategorySeoData(Category $category): array
    {
        $title = $category->meta_title ?: $category->name . ' - ' . config('app.name');
        $description = $category->meta_description ?: $this->generateCategoryDescription($category);
        $keywords = $this->generateCategoryKeywords($category);

        return [
            'title' => $title,
            'description' => $description,
            'keywords' => $keywords,
            'canonical' => $this->generateCategoryCanonical($category),
            'og' => $this->generateCategoryOpenGraph($category),
            'twitter' => $this->generateCategoryTwitter($category),
            'schema' => $this->generateCategorySchema($category),
            'breadcrumbs' => $this->generateCategoryBreadcrumbs($category)
        ];
    }

    /**
     * Gera dados SEO para marcas
     */
    public function getBrandSeoData(Brand $brand): array
    {
        $title = $brand->meta_title ?: $brand->name . ' - ' . config('app.name');
        $description = $brand->meta_description ?: $this->generateBrandDescription($brand);
        $keywords = $this->generateBrandKeywords($brand);

        return [
            'title' => $title,
            'description' => $description,
            'keywords' => $keywords,
            'canonical' => $this->generateBrandCanonical($brand),
            'og' => $this->generateBrandOpenGraph($brand),
            'twitter' => $this->generateBrandTwitter($brand),
            'schema' => $this->generateBrandSchema($brand),
            'breadcrumbs' => $this->generateBrandBreadcrumbs($brand)
        ];
    }

    /**
     * Gera dados SEO para páginas
     */
    public function getPageSeoData(Page $page): array
    {
        return [
            'title' => $page->meta_title ?: $page->title . ' - ' . config('app.name'),
            'description' => $page->meta_description ?: $this->generatePageDescription($page),
            'keywords' => $page->meta_keywords ?: $this->generatePageKeywords($page),
            'canonical' => $this->generatePageCanonical($page),
            'og' => $this->generatePageOpenGraph($page),
            'twitter' => $this->generatePageTwitter($page),
            'schema' => $this->generatePageSchema($page)
        ];
    }

    /**
     * Gera dados SEO para a página inicial
     */
    public function getHomeSeoData(): array
    {
        $config = $this->config;

        return [
            'title' => $config['home_title'] ?? config('app.name') . ' - Sua loja online',
            'description' => $config['home_description'] ?? 'Encontre os melhores produtos com os melhores preços',
            'keywords' => $config['home_keywords'] ?? 'loja online, produtos, compras',
            'canonical' => URL::to('/'),
            'og' => $this->generateHomeOpenGraph(),
            'twitter' => $this->generateHomeTwitter(),
            'schema' => $this->generateHomeSchema()
        ];
    }

    /**
     * Gera sitemap XML
     */
    public function generateSitemap(): string
    {
        $cacheKey = 'ecommerce_sitemap';
        $ttl = $this->config['sitemap_cache_ttl'] ?? 3600;

        return Cache::remember($cacheKey, $ttl, function () {
            $xml = $this->getSitemapHeader();

            // URLs da loja
            $xml .= $this->getStoreUrls();

            // URLs de produtos
            $xml .= $this->getProductUrls();

            // URLs de categorias
            $xml .= $this->getCategoryUrls();

            // URLs de marcas
            $xml .= $this->getBrandUrls();

            // URLs de páginas
            $xml .= $this->getPageUrls();

            $xml .= $this->getSitemapFooter();

            return $xml;
        });
    }

    /**
     * Gera robots.txt
     */
    public function generateRobotsTxt(): string
    {
        $config = $this->config;

        $robots = "User-agent: *\n";
        $robots .= "Allow: /\n\n";

        // Sitemap
        if ($config['sitemap_enabled'] ?? true) {
            $robots .= "Sitemap: " . URL::to('/sitemap.xml') . "\n\n";
        }

        // Disallow rules
        $disallow = $config['robots_disallow'] ?? [];
        foreach ($disallow as $path) {
            $robots .= "Disallow: $path\n";
        }

        // Crawl delay
        if ($crawlDelay = $config['crawl_delay'] ?? null) {
            $robots .= "\nCrawl-delay: $crawlDelay\n";
        }

        return $robots;
    }

    /**
     * Gera meta tags estruturadas
     */
    public function generateStructuredData($type, $data): string
    {
        $method = 'generate' . ucfirst($type) . 'Schema';

        if (method_exists($this, $method)) {
            return $this->$method($data);
        }

        return '';
    }

    /**
     * Gera canonical URL
     */
    public function generateCanonical(string $url): string
    {
        return URL::to($url);
    }

    /**
     * Gera meta tags para redes sociais
     */
    public function generateSocialMeta(array $data): array
    {
        return [
            'og' => $data['og'] ?? [],
            'twitter' => $data['twitter'] ?? []
        ];
    }

    /**
     * Otimiza imagens para SEO
     */
    public function optimizeImageForSeo(string $imagePath, array $attributes = []): array
    {
        return [
            'src' => $imagePath,
            'alt' => $attributes['alt'] ?? '',
            'title' => $attributes['title'] ?? '',
            'loading' => $attributes['loading'] ?? 'lazy',
            'width' => $attributes['width'] ?? null,
            'height' => $attributes['height'] ?? null,
            'sizes' => $attributes['sizes'] ?? '(max-width: 768px) 100vw, (max-width: 1200px) 50vw, 33vw'
        ];
    }

    /**
     * Gera dados para breadcrumbs
     */
    public function generateBreadcrumbs(array $items): array
    {
        $breadcrumbs = [
            [
                'name' => 'Início',
                'url' => URL::to('/')
            ]
        ];

        foreach ($items as $item) {
            $breadcrumbs[] = [
                'name' => $item['name'],
                'url' => $item['url'] ?? null
            ];
        }

        return $breadcrumbs;
    }

    /**
     * Gera dados para paginação SEO-friendly
     */
    public function generatePaginationMeta(int $currentPage, int $lastPage, string $baseUrl): array
    {
        $meta = [];

        if ($currentPage > 1) {
            $meta['prev'] = URL::to($baseUrl . '?page=' . ($currentPage - 1));
        }

        if ($currentPage < $lastPage) {
            $meta['next'] = URL::to($baseUrl . '?page=' . ($currentPage + 1));
        }

        return $meta;
    }

    /**
     * Gera dados para search engines
     */
    public function generateSearchEngineMeta(): array
    {
        return [
            'robots' => 'index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1',
            'googlebot' => 'index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1',
            'bingbot' => 'index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1',
            'author' => config('app.name'),
            'language' => 'pt-BR',
            'revisit-after' => '7 days',
            'rating' => 'general',
            'distribution' => 'global',
            'coverage' => 'worldwide'
        ];
    }

    /**
     * Gera dados para mobile optimization
     */
    public function generateMobileMeta(): array
    {
        return [
            'viewport' => 'width=device-width, initial-scale=1.0',
            'mobile-web-app-capable' => 'yes',
            'apple-mobile-web-app-capable' => 'yes',
            'apple-mobile-web-app-status-bar-style' => 'black-translucent',
            'apple-mobile-web-app-title' => config('app.name'),
            'theme-color' => '#ffffff',
            'msapplication-TileColor' => '#ffffff',
            'msapplication-config' => URL::to('/browserconfig.xml')
        ];
    }

    /**
     * Gera dados para performance
     */
    public function generatePerformanceMeta(): array
    {
        return [
            'dns-prefetch' => [
                '//fonts.googleapis.com',
                '//www.google-analytics.com',
                '//www.googletagmanager.com'
            ],
            'preconnect' => [
                'https://fonts.gstatic.com'
            ],
            'preload' => [
                'fonts' => [
                    'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap'
                ]
            ]
        ];
    }

    /**
     * Gera dados para segurança
     */
    public function generateSecurityMeta(): array
    {
        return [
            'Content-Security-Policy' => "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://www.google-analytics.com https://www.googletagmanager.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data: https:; connect-src 'self' https://www.google-analytics.com https://www.googletagmanager.com",
            'X-Frame-Options' => 'SAMEORIGIN',
            'X-Content-Type-Options' => 'nosniff',
            'X-XSS-Protection' => '1; mode=block',
            'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains; preload',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Permissions-Policy' => 'camera=(), microphone=(), geolocation=()'
        ];
    }

    /**
     * Gera dados para analytics
     */
    public function generateAnalyticsMeta(): array
    {
        $config = $this->config;

        return [
            'google-analytics' => $config['google_analytics_id'] ?? null,
            'google-tag-manager' => $config['gtm_id'] ?? null,
            'facebook-pixel' => $config['facebook_pixel_id'] ?? null,
            'google-site-verification' => $config['google_site_verification'] ?? null,
            'bing-site-verification' => $config['bing_site_verification'] ?? null
        ];
    }

    /**
     * Invalida cache de SEO
     */
    public function invalidateSeoCache(): void
    {
        $this->cacheService->clear();
    }

    /**
     * Invalida cache específico
     */
    public function invalidateSpecificCache(string $type, $id): void
    {
        $cacheKey = "ecommerce_seo_{$type}_{$id}";
        Cache::forget($cacheKey);
    }

    // Métodos auxiliares privados

    private function generateProductDescription(Product $product): string
    {
        $description = $product->short_description ?: strip_tags($product->description);

        if (strlen($description) > 160) {
            $description = substr($description, 0, 157) . '...';
        }

        return $description;
    }

    private function generateProductKeywords(Product $product): string
    {
        $keywords = [];

        if ($product->tags) {
            $keywords = array_merge($keywords, $product->tags);
        }

        $keywords[] = $product->name;
        $keywords[] = $product->category->name;
        $keywords[] = $product->brand->name;
        $keywords[] = 'produto';
        $keywords[] = 'loja online';

        return implode(', ', array_unique($keywords));
    }

    private function generateProductCanonical(Product $product): string
    {
        return URL::to('/produtos/' . $product->slug);
    }

    private function generateProductOpenGraph(Product $product): array
    {
        return [
            'title' => $product->name,
            'description' => $this->generateProductDescription($product),
            'url' => $this->generateProductCanonical($product),
            'type' => 'product',
            'image' => $product->images->first() ? URL::to($product->images->first()->path) : null,
            'price' => [
                'amount' => $product->price,
                'currency' => 'BRL'
            ],
            'availability' => $product->inStock() ? 'in stock' : 'out of stock'
        ];
    }

    private function generateProductTwitter(Product $product): array
    {
        return [
            'card' => 'product',
            'title' => $product->name,
            'description' => $this->generateProductDescription($product),
            'image' => $product->images->first() ? URL::to($product->images->first()->path) : null,
            'price' => $product->price,
            'currency' => 'BRL'
        ];
    }

    private function generateProductSchema(Product $product): string
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $product->name,
            'description' => $this->generateProductDescription($product),
            'sku' => $product->sku,
            'image' => $product->images->pluck('path')->map(fn($path) => URL::to($path))->toArray(),
            'brand' => [
                '@type' => 'Brand',
                'name' => $product->brand->name
            ],
            'category' => $product->category->name,
            'offers' => [
                '@type' => 'Offer',
                'price' => $product->price,
                'priceCurrency' => 'BRL',
                'availability' => $product->inStock() ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
                'url' => $this->generateProductCanonical($product)
            ]
        ];

        if ($product->hasDiscount()) {
            $schema['offers']['priceValidUntil'] = Carbon::now()->addDays(30)->toISOString();
        }

        return json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    private function generateProductBreadcrumbs(Product $product): array
    {
        return [
            [
                'name' => 'Produtos',
                'url' => URL::to('/produtos')
            ],
            [
                'name' => $product->category->name,
                'url' => URL::to('/produtos/categoria/' . $product->category->slug)
            ],
            [
                'name' => $product->brand->name,
                'url' => URL::to('/produtos/marca/' . $product->brand->slug)
            ],
            [
                'name' => $product->name,
                'url' => null
            ]
        ];
    }

    private function generateCategoryDescription(Category $category): string
    {
        return "Explore nossa categoria {$category->name} com os melhores produtos selecionados para você.";
    }

    private function generateCategoryKeywords(Category $category): string
    {
        return "{$category->name}, produtos, loja online, compras";
    }

    private function generateCategoryCanonical(Category $category): string
    {
        return URL::to('/produtos/categoria/' . $category->slug);
    }

    private function generateCategoryOpenGraph(Category $category): array
    {
        return [
            'title' => $category->name,
            'description' => $this->generateCategoryDescription($category),
            'url' => $this->generateCategoryCanonical($category),
            'type' => 'website',
            'image' => $category->image ? URL::to($category->image) : null
        ];
    }

    private function generateCategoryTwitter(Category $category): array
    {
        return [
            'card' => 'summary_large_image',
            'title' => $category->name,
            'description' => $this->generateCategoryDescription($category),
            'image' => $category->image ? URL::to($category->image) : null
        ];
    }

    private function generateCategorySchema(Category $category): string
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'CollectionPage',
            'name' => $category->name,
            'description' => $this->generateCategoryDescription($category),
            'url' => $this->generateCategoryCanonical($category)
        ];

        return json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    private function generateCategoryBreadcrumbs(Category $category): array
    {
        return [
            [
                'name' => 'Produtos',
                'url' => URL::to('/produtos')
            ],
            [
                'name' => $category->name,
                'url' => null
            ]
        ];
    }

    private function generateBrandDescription(Brand $brand): string
    {
        return "Conheça os produtos da marca {$brand->name}, qualidade e confiança em cada item.";
    }

    private function generateBrandKeywords(Brand $brand): string
    {
        return "{$brand->name}, marca, produtos, qualidade";
    }

    private function generateBrandCanonical(Brand $brand): string
    {
        return URL::to('/produtos/marca/' . $brand->slug);
    }

    private function generateBrandOpenGraph(Brand $brand): array
    {
        return [
            'title' => $brand->name,
            'description' => $this->generateBrandDescription($brand),
            'url' => $this->generateBrandCanonical($brand),
            'type' => 'website',
            'image' => $brand->logo ? URL::to($brand->logo) : null
        ];
    }

    private function generateBrandTwitter(Brand $brand): array
    {
        return [
            'card' => 'summary_large_image',
            'title' => $brand->name,
            'description' => $this->generateBrandDescription($brand),
            'image' => $brand->logo ? URL::to($brand->logo) : null
        ];
    }

    private function generateBrandSchema(Brand $brand): string
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Brand',
            'name' => $brand->name,
            'description' => $this->generateBrandDescription($brand),
            'url' => $this->generateBrandCanonical($brand),
            'logo' => $brand->logo ? URL::to($brand->logo) : null
        ];

        return json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    private function generateBrandBreadcrumbs(Brand $brand): array
    {
        return [
            [
                'name' => 'Produtos',
                'url' => URL::to('/produtos')
            ],
            [
                'name' => 'Marcas',
                'url' => URL::to('/produtos/marcas')
            ],
            [
                'name' => $brand->name,
                'url' => null
            ]
        ];
    }

    private function generatePageDescription(Page $page): string
    {
        return $page->excerpt ?: substr(strip_tags($page->content), 0, 160) . '...';
    }

    private function generatePageKeywords(Page $page): string
    {
        return $page->meta_keywords ?: 'página, conteúdo, informação';
    }

    private function generatePageCanonical(Page $page): string
    {
        return URL::to('/' . $page->slug);
    }

    private function generatePageOpenGraph(Page $page): array
    {
        return [
            'title' => $page->title,
            'description' => $this->generatePageDescription($page),
            'url' => $this->generatePageCanonical($page),
            'type' => 'article'
        ];
    }

    private function generatePageTwitter(Page $page): array
    {
        return [
            'card' => 'summary',
            'title' => $page->title,
            'description' => $this->generatePageDescription($page)
        ];
    }

    private function generatePageSchema(Page $page): string
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $page->title,
            'description' => $this->generatePageDescription($page),
            'url' => $this->generatePageCanonical($page),
            'datePublished' => $page->created_at->toISOString(),
            'dateModified' => $page->updated_at->toISOString()
        ];

        return json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    private function generateHomeOpenGraph(): array
    {
        return [
            'title' => config('app.name'),
            'description' => 'Sua loja online com os melhores produtos',
            'url' => URL::to('/'),
            'type' => 'website',
            'site_name' => config('app.name')
        ];
    }

    private function generateHomeTwitter(): array
    {
        return [
            'card' => 'summary_large_image',
            'title' => config('app.name'),
            'description' => 'Sua loja online com os melhores produtos',
            'site' => '@' . config('app.name')
        ];
    }

    private function generateHomeSchema(): string
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => config('app.name'),
            'url' => URL::to('/'),
            'logo' => URL::to('/images/logo.png'),
            'description' => 'Sua loja online com os melhores produtos e preços',
            'sameAs' => [
                'https://facebook.com/' . config('app.name'),
                'https://instagram.com/' . config('app.name'),
                'https://twitter.com/' . config('app.name')
            ]
        ];

        return json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    private function getSitemapHeader(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>' . "\n" .
               '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . "\n" .
               '        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"' . "\n" .
               '        xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9' . "\n" .
               '        http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">' . "\n\n";
    }

    private function getSitemapFooter(): string
    {
        return "\n</urlset>";
    }

    private function getStoreUrls(): string
    {
        $urls = '';
        $routes = ['produtos', 'categorias', 'marcas', 'sobre', 'contato', 'blog'];

        foreach ($routes as $route) {
            $urls .= $this->getSitemapUrl(
                URL::to("/$route"),
                Carbon::now()->format('Y-m-d'),
                'weekly',
                '0.8'
            );
        }

        return $urls;
    }

    private function getProductUrls(): string
    {
        $urls = '';
        $products = Product::active()->select('slug', 'updated_at')->get();

        foreach ($products as $product) {
            $urls .= $this->getSitemapUrl(
                URL::to("/produtos/{$product->slug}"),
                $product->updated_at->format('Y-m-d'),
                'weekly',
                '0.8'
            );
        }

        return $urls;
    }

    private function getCategoryUrls(): string
    {
        $urls = '';
        $categories = Category::active()->select('slug', 'updated_at')->get();

        foreach ($categories as $category) {
            $urls .= $this->getSitemapUrl(
                URL::to("/produtos/categoria/{$category->slug}"),
                $category->updated_at->format('Y-m-d'),
                'weekly',
                '0.7'
            );
        }

        return $urls;
    }

    private function getBrandUrls(): string
    {
        $urls = '';
        $brands = Brand::active()->select('slug', 'updated_at')->get();

        foreach ($brands as $brand) {
            $urls .= $this->getSitemapUrl(
                URL::to("/produtos/marca/{$brand->slug}"),
                $brand->updated_at->format('Y-m-d'),
                'weekly',
                '0.7'
            );
        }

        return $urls;
    }

    private function getPageUrls(): string
    {
        $urls = '';
        $pages = Page::active()->select('slug', 'updated_at')->get();

        foreach ($pages as $page) {
            $urls .= $this->getSitemapUrl(
                URL::to("/{$page->slug}"),
                $page->updated_at->format('Y-m-d'),
                'monthly',
                '0.6'
            );
        }

        return $urls;
    }

    private function getSitemapUrl(string $url, string $lastmod, string $changefreq, string $priority): string
    {
        return "<url>\n" .
               "    <loc>{$url}</loc>\n" .
               "    <lastmod>{$lastmod}</lastmod>\n" .
               "    <changefreq>{$changefreq}</changefreq>\n" .
               "    <priority>{$priority}</priority>\n" .
               "</url>\n\n";
    }
}