<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Configurações Gerais da Loja
    |--------------------------------------------------------------------------
    |
    | Configurações básicas da loja virtual
    |
    */

    'name' => env('STORE_NAME', 'Minha Loja'),
    'description' => env('STORE_DESCRIPTION', 'Sua loja virtual completa'),
    'url' => env('STORE_URL', 'https://minhaloja.com.br'),
    'email' => env('STORE_EMAIL', 'contato@minhaloja.com.br'),
    'phone' => env('STORE_PHONE', '(11) 99999-9999'),
    'address' => [
        'street' => env('STORE_ADDRESS_STREET', 'Rua das Lojas, 123'),
        'complement' => env('STORE_ADDRESS_COMPLEMENT', 'Sala 101'),
        'city' => env('STORE_ADDRESS_CITY', 'São Paulo'),
        'state' => env('STORE_ADDRESS_STATE', 'SP'),
        'zipcode' => env('STORE_ADDRESS_ZIPCODE', '01234-567'),
        'country' => env('STORE_ADDRESS_COUNTRY', 'Brasil'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Configurações de Moeda
    |--------------------------------------------------------------------------
    |
    | Configurações relacionadas à moeda da loja
    |
    */

    'currency' => [
        'code' => env('STORE_CURRENCY_CODE', 'BRL'),
        'symbol' => env('STORE_CURRENCY_SYMBOL', 'R$'),
        'position' => env('STORE_CURRENCY_POSITION', 'before'), // before, after
        'decimal_places' => env('STORE_CURRENCY_DECIMAL_PLACES', 2),
        'decimal_separator' => env('STORE_CURRENCY_DECIMAL_SEPARATOR', ','),
        'thousands_separator' => env('STORE_CURRENCY_THOUSANDS_SEPARATOR', '.'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Configurações de Produtos
    |--------------------------------------------------------------------------
    |
    | Configurações relacionadas aos produtos da loja
    |
    */

    'products' => [
        'per_page' => env('STORE_PRODUCTS_PER_PAGE', 12),
        'max_images' => env('STORE_PRODUCTS_MAX_IMAGES', 10),
        'image_sizes' => [
            'thumb' => ['width' => 300, 'height' => 300],
            'medium' => ['width' => 600, 'height' => 600],
            'large' => ['width' => 1200, 'height' => 1200],
        ],
        'allow_reviews' => env('STORE_PRODUCTS_ALLOW_REVIEWS', true),
        'auto_approve_reviews' => env('STORE_PRODUCTS_AUTO_APPROVE_REVIEWS', false),
        'show_stock' => env('STORE_PRODUCTS_SHOW_STOCK', true),
        'low_stock_threshold' => env('STORE_PRODUCTS_LOW_STOCK_THRESHOLD', 5),
        'allow_backorders' => env('STORE_PRODUCTS_ALLOW_BACKORDERS', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Configurações do Carrinho
    |--------------------------------------------------------------------------
    |
    | Configurações relacionadas ao carrinho de compras
    |
    */

    'cart' => [
        'session_lifetime' => env('STORE_CART_SESSION_LIFETIME', 30), // dias
        'max_items' => env('STORE_CART_MAX_ITEMS', 100),
        'max_quantity_per_item' => env('STORE_CART_MAX_QUANTITY_PER_ITEM', 99),
        'allow_guests' => env('STORE_CART_ALLOW_GUESTS', true),
        'merge_guest_cart' => env('STORE_CART_MERGE_GUEST_CART', true),
        'auto_save' => env('STORE_CART_AUTO_SAVE', true),
        'save_interval' => env('STORE_CART_SAVE_INTERVAL', 5), // segundos
    ],

    /*
    |--------------------------------------------------------------------------
    | Configurações de Pedidos
    |--------------------------------------------------------------------------
    |
    | Configurações relacionadas aos pedidos
    |
    */

    'orders' => [
        'auto_confirm' => env('STORE_ORDERS_AUTO_CONFIRM', false),
        'require_approval' => env('STORE_ORDERS_REQUIRE_APPROVAL', false),
        'generate_invoice' => env('STORE_ORDERS_GENERATE_INVOICE', true),
        'invoice_prefix' => env('STORE_ORDERS_INVOICE_PREFIX', 'PED'),
        'allow_cancellation' => env('STORE_ORDERS_ALLOW_CANCELLATION', true),
        'cancellation_period' => env('STORE_ORDERS_CANCELLATION_PERIOD', 24), // horas
        'status_history' => env('STORE_ORDERS_STATUS_HISTORY', true),
        'email_notifications' => env('STORE_ORDERS_EMAIL_NOTIFICATIONS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Configurações de Clientes
    |--------------------------------------------------------------------------
    |
    | Configurações relacionadas aos clientes
    |
    */

    'customers' => [
        'require_email_verification' => env('STORE_CUSTOMERS_REQUIRE_EMAIL_VERIFICATION', false),
        'allow_registration' => env('STORE_CUSTOMERS_ALLOW_REGISTRATION', true),
        'auto_login_after_registration' => env('STORE_CUSTOMERS_AUTO_LOGIN_AFTER_REGISTRATION', true),
        'password_min_length' => env('STORE_CUSTOMERS_PASSWORD_MIN_LENGTH', 8),
        'require_strong_password' => env('STORE_CUSTOMERS_REQUIRE_STRONG_PASSWORD', true),
        'allow_social_login' => env('STORE_CUSTOMERS_ALLOW_SOCIAL_LOGIN', false),
        'max_addresses' => env('STORE_CUSTOMERS_MAX_ADDRESSES', 5),
        'newsletter_by_default' => env('STORE_CUSTOMERS_NEWSLETTER_BY_DEFAULT', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Configurações de Pagamento
    |--------------------------------------------------------------------------
    |
    | Configurações relacionadas aos métodos de pagamento
    |
    */

    'payment' => [
        'default_gateway' => env('STORE_PAYMENT_DEFAULT_GATEWAY', 'stripe'),
        'allow_multiple_gateways' => env('STORE_PAYMENT_ALLOW_MULTIPLE_GATEWAYS', true),
        'save_cards' => env('STORE_PAYMENT_SAVE_CARDS', false),
        'require_cvv' => env('STORE_PAYMENT_REQUIRE_CVV', true),
        'supported_gateways' => [
            'stripe' => [
                'enabled' => env('STORE_PAYMENT_STRIPE_ENABLED', false),
                'public_key' => env('STORE_PAYMENT_STRIPE_PUBLIC_KEY'),
                'secret_key' => env('STORE_PAYMENT_STRIPE_SECRET_KEY'),
                'webhook_secret' => env('STORE_PAYMENT_STRIPE_WEBHOOK_SECRET'),
            ],
            'mercadopago' => [
                'enabled' => env('STORE_PAYMENT_MERCADOPAGO_ENABLED', false),
                'public_key' => env('STORE_PAYMENT_MERCADOPAGO_PUBLIC_KEY'),
                'access_token' => env('STORE_PAYMENT_MERCADOPAGO_ACCESS_TOKEN'),
                'webhook_secret' => env('STORE_PAYMENT_MERCADOPAGO_WEBHOOK_SECRET'),
            ],
            'pagseguro' => [
                'enabled' => env('STORE_PAYMENT_PAGSEGURO_ENABLED', false),
                'email' => env('STORE_PAYMENT_PAGSEGURO_EMAIL'),
                'token' => env('STORE_PAYMENT_PAGSEGURO_TOKEN'),
                'sandbox' => env('STORE_PAYMENT_PAGSEGURO_SANDBOX', true),
            ],
        ],
        'installments' => [
            'enabled' => env('STORE_PAYMENT_INSTALLMENTS_ENABLED', true),
            'max_installments' => env('STORE_PAYMENT_MAX_INSTALLMENTS', 12),
            'min_installment_value' => env('STORE_PAYMENT_MIN_INSTALLMENT_VALUE', 5.00),
            'interest_rate' => env('STORE_PAYMENT_INTEREST_RATE', 0.00),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Configurações de Frete
    |--------------------------------------------------------------------------
    |
    | Configurações relacionadas ao cálculo de frete
    |
    */

    'shipping' => [
        'free_shipping_threshold' => env('STORE_SHIPPING_FREE_THRESHOLD', 200.00),
        'default_method' => env('STORE_SHIPPING_DEFAULT_METHOD', 'correios'),
        'calculate_on_cart' => env('STORE_SHIPPING_CALCULATE_ON_CART', true),
        'show_delivery_time' => env('STORE_SHIPPING_SHOW_DELIVERY_TIME', true),
        'methods' => [
            'correios' => [
                'enabled' => env('STORE_SHIPPING_CORREIOS_ENABLED', false),
                'username' => env('STORE_SHIPPING_CORREIOS_USERNAME'),
                'password' => env('STORE_SHIPPING_CORREIOS_PASSWORD'),
                'sandbox' => env('STORE_SHIPPING_CORREIOS_SANDBOX', true),
            ],
            'jadlog' => [
                'enabled' => env('STORE_SHIPPING_JADLOG_ENABLED', false),
                'token' => env('STORE_SHIPPING_JADLOG_TOKEN'),
                'sandbox' => env('STORE_SHIPPING_JADLOG_SANDBOX', true),
            ],
            'fixed' => [
                'enabled' => env('STORE_SHIPPING_FIXED_ENABLED', true),
                'cost' => env('STORE_SHIPPING_FIXED_COST', 15.00),
                'estimated_days' => env('STORE_SHIPPING_FIXED_ESTIMATED_DAYS', '3-5'),
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Configurações de Cupons
    |--------------------------------------------------------------------------
    |
    | Configurações relacionadas aos cupons de desconto
    |
    */

    'coupons' => [
        'enabled' => env('STORE_COUPONS_ENABLED', true),
        'multiple_coupons' => env('STORE_COUPONS_MULTIPLE', false),
        'auto_apply' => env('STORE_COUPONS_AUTO_APPLY', false),
        'show_available' => env('STORE_COUPONS_SHOW_AVAILABLE', true),
        'max_uses_per_coupon' => env('STORE_COUPONS_MAX_USES_PER_COUPON', 100),
        'max_uses_per_customer' => env('STORE_COUPONS_MAX_USES_PER_CUSTOMER', 1),
    ],

    /*
    |--------------------------------------------------------------------------
    | Configurações de Avaliações
    |--------------------------------------------------------------------------
    |
    | Configurações relacionadas às avaliações de produtos
    |
    */

    'reviews' => [
        'enabled' => env('STORE_REVIEWS_ENABLED', true),
        'require_purchase' => env('STORE_REVIEWS_REQUIRE_PURCHASE', true),
        'auto_approve' => env('STORE_REVIEWS_AUTO_APPROVE', false),
        'allow_images' => env('STORE_REVIEWS_ALLOW_IMAGES', true),
        'max_images_per_review' => env('STORE_REVIEWS_MAX_IMAGES_PER_REVIEW', 3),
        'helpful_votes' => env('STORE_REVIEWS_HELPFUL_VOTES', true),
        'show_rating_breakdown' => env('STORE_REVIEWS_SHOW_RATING_BREAKDOWN', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Configurações de Notificações
    |--------------------------------------------------------------------------
    |
    | Configurações relacionadas às notificações por email
    |
    */

    'notifications' => [
        'enabled' => env('STORE_NOTIFICATIONS_ENABLED', true),
        'from_email' => env('STORE_NOTIFICATIONS_FROM_EMAIL', 'noreply@minhaloja.com.br'),
        'from_name' => env('STORE_NOTIFICATIONS_FROM_NAME', 'Minha Loja'),
        'templates' => [
            'order_confirmation' => env('STORE_NOTIFICATIONS_ORDER_CONFIRMATION', true),
            'order_shipped' => env('STORE_NOTIFICATIONS_ORDER_SHIPPED', true),
            'order_delivered' => env('STORE_NOTIFICATIONS_ORDER_DELIVERED', true),
            'customer_welcome' => env('STORE_NOTIFICATIONS_CUSTOMER_WELCOME', true),
            'password_reset' => env('STORE_NOTIFICATIONS_PASSWORD_RESET', true),
            'abandoned_cart' => env('STORE_NOTIFICATIONS_ABANDONED_CART', true),
        ],
        'queue_notifications' => env('STORE_NOTIFICATIONS_QUEUE', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Configurações de SEO
    |--------------------------------------------------------------------------
    |
    | Configurações relacionadas ao SEO da loja
    |
    */

    'seo' => [
        'meta_title_suffix' => env('STORE_SEO_TITLE_SUFFIX', ' | Minha Loja'),
        'meta_description_length' => env('STORE_SEO_DESCRIPTION_LENGTH', 160),
        'generate_sitemap' => env('STORE_SEO_GENERATE_SITEMAP', true),
        'robots_txt' => env('STORE_SEO_ROBOTS_TXT', true),
        'structured_data' => env('STORE_SEO_STRUCTURED_DATA', true),
        'open_graph' => env('STORE_SEO_OPEN_GRAPH', true),
        'twitter_cards' => env('STORE_SEO_TWITTER_CARDS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Configurações de Cache
    |--------------------------------------------------------------------------
    |
    | Configurações relacionadas ao cache da loja
    |
    */

    'cache' => [
        'enabled' => env('STORE_CACHE_ENABLED', true),
        'ttl' => [
            'products' => env('STORE_CACHE_PRODUCTS_TTL', 3600), // 1 hora
            'categories' => env('STORE_CACHE_CATEGORIES_TTL', 7200), // 2 horas
            'brands' => env('STORE_CACHE_BRANDS_TTL', 7200), // 2 horas
            'settings' => env('STORE_CACHE_SETTINGS_TTL', 86400), // 24 horas
        ],
        'tags_enabled' => env('STORE_CACHE_TAGS_ENABLED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Configurações de Segurança
    |--------------------------------------------------------------------------
    |
    | Configurações relacionadas à segurança da loja
    |
    */

    'security' => [
        'csrf_protection' => env('STORE_SECURITY_CSRF_PROTECTION', true),
        'rate_limiting' => env('STORE_SECURITY_RATE_LIMITING', true),
        'max_login_attempts' => env('STORE_SECURITY_MAX_LOGIN_ATTEMPTS', 5),
        'lockout_duration' => env('STORE_SECURITY_LOCKOUT_DURATION', 300), // 5 minutos
        'password_reset_expire' => env('STORE_SECURITY_PASSWORD_RESET_EXPIRE', 60), // 1 hora
        'session_lifetime' => env('STORE_SECURITY_SESSION_LIFETIME', 120), // 2 horas
        'secure_cookies' => env('STORE_SECURITY_SECURE_COOKIES', false),
        'http_only_cookies' => env('STORE_SECURITY_HTTP_ONLY_COOKIES', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Configurações de API
    |--------------------------------------------------------------------------
    |
    | Configurações relacionadas à API da loja
    |
    */

    'api' => [
        'enabled' => env('STORE_API_ENABLED', true),
        'version' => env('STORE_API_VERSION', 'v1'),
        'rate_limit' => env('STORE_API_RATE_LIMIT', 60), // requests por minuto
        'throttle' => env('STORE_API_THROTTLE', true),
        'authentication' => env('STORE_API_AUTHENTICATION', 'sanctum'),
        'pagination' => env('STORE_API_PAGINATION', 15),
    ],

    /*
    |--------------------------------------------------------------------------
    | Configurações de Analytics
    |--------------------------------------------------------------------------
    |
    | Configurações relacionadas ao analytics da loja
    |
    */

    'analytics' => [
        'google_analytics' => [
            'enabled' => env('STORE_ANALYTICS_GA_ENABLED', false),
            'tracking_id' => env('STORE_ANALYTICS_GA_TRACKING_ID'),
        ],
        'facebook_pixel' => [
            'enabled' => env('STORE_ANALYTICS_FB_PIXEL_ENABLED', false),
            'pixel_id' => env('STORE_ANALYTICS_FB_PIXEL_ID'),
        ],
        'google_tag_manager' => [
            'enabled' => env('STORE_ANALYTICS_GTM_ENABLED', false),
            'container_id' => env('STORE_ANALYTICS_GTM_CONTAINER_ID'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Configurações de Desenvolvimento
    |--------------------------------------------------------------------------
    |
    | Configurações específicas para ambiente de desenvolvimento
    |
    */

    'development' => [
        'debug' => env('STORE_DEBUG', false),
        'log_queries' => env('STORE_LOG_QUERIES', false),
        'cache_disabled' => env('STORE_CACHE_DISABLED', false),
        'demo_mode' => env('STORE_DEMO_MODE', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Configurações de Integração
    |--------------------------------------------------------------------------
    |
    | Configurações para integração com sistemas externos
    |
    */

    'integrations' => [
        'erp' => [
            'enabled' => env('STORE_INTEGRATION_ERP_ENABLED', false),
            'system' => env('STORE_INTEGRATION_ERP_SYSTEM', 'bling'),
            'api_key' => env('STORE_INTEGRATION_ERP_API_KEY'),
            'sync_products' => env('STORE_INTEGRATION_ERP_SYNC_PRODUCTS', true),
            'sync_orders' => env('STORE_INTEGRATION_ERP_SYNC_ORDERS', true),
            'sync_customers' => env('STORE_INTEGRATION_ERP_SYNC_CUSTOMERS', true),
        ],
        'marketplace' => [
            'enabled' => env('STORE_INTEGRATION_MARKETPLACE_ENABLED', false),
            'platform' => env('STORE_INTEGRATION_MARKETPLACE_PLATFORM', 'shopify'),
            'api_key' => env('STORE_INTEGRATION_MARKETPLACE_API_KEY'),
            'sync_inventory' => env('STORE_INTEGRATION_MARKETPLACE_SYNC_INVENTORY', true),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Configurações de Wishlist
    |--------------------------------------------------------------------------
    |
    | Configurações relacionadas ao sistema de favoritos
    |
    */

    'wishlist' => [
        'enabled' => env('STORE_WISHLIST_ENABLED', true),
        'max_items' => env('STORE_WISHLIST_MAX_ITEMS', 50),
        'allow_guests' => env('STORE_WISHLIST_ALLOW_GUESTS', true),
        'shareable' => env('STORE_WISHLIST_SHAREABLE', true),
        'public_by_default' => env('STORE_WISHLIST_PUBLIC_BY_DEFAULT', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Configurações de Comparação de Produtos
    |--------------------------------------------------------------------------
    |
    | Configurações relacionadas ao sistema de comparação
    |
    */

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

    /*
    |--------------------------------------------------------------------------
    | Configurações de Localização
    |--------------------------------------------------------------------------
    |
    | Configurações relacionadas à localização e idioma
    |
    */

    'locale' => [
        'default' => env('STORE_LOCALE_DEFAULT', 'pt_BR'),
        'supported' => ['pt_BR', 'en_US', 'es_ES'],
        'currency_format' => env('STORE_LOCALE_CURRENCY_FORMAT', 'pt_BR'),
        'date_format' => env('STORE_LOCALE_DATE_FORMAT', 'd/m/Y'),
        'time_format' => env('STORE_LOCALE_TIME_FORMAT', 'H:i:s'),
        'datetime_format' => env('STORE_LOCALE_DATETIME_FORMAT', 'd/m/Y H:i:s'),
        'timezone' => env('STORE_LOCALE_TIMEZONE', 'America/Sao_Paulo'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Configurações de Performance
    |--------------------------------------------------------------------------
    |
    | Configurações relacionadas ao desempenho da loja
    |
    */

    'performance' => [
        'image_optimization' => env('STORE_PERFORMANCE_IMAGE_OPTIMIZATION', true),
        'lazy_loading' => env('STORE_PERFORMANCE_LAZY_LOADING', true),
        'cdn_enabled' => env('STORE_PERFORMANCE_CDN_ENABLED', false),
        'cdn_url' => env('STORE_PERFORMANCE_CDN_URL'),
        'minify_assets' => env('STORE_PERFORMANCE_MINIFY_ASSETS', true),
        'compress_html' => env('STORE_PERFORMANCE_COMPRESS_HTML', true),
        'cache_assets' => env('STORE_PERFORMANCE_CACHE_ASSETS', true),
        'preload_critical_assets' => env('STORE_PERFORMANCE_PRELOAD_CRITICAL_ASSETS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Configurações de Logs
    |--------------------------------------------------------------------------
    |
    | Configurações relacionadas ao sistema de logs
    |
    */

    'logging' => [
        'enabled' => env('STORE_LOGGING_ENABLED', true),
        'level' => env('STORE_LOGGING_LEVEL', 'info'),
        'channels' => [
            'orders' => env('STORE_LOGGING_ORDERS', true),
            'payments' => env('STORE_LOGGING_PAYMENTS', true),
            'customers' => env('STORE_LOGGING_CUSTOMERS', true),
            'products' => env('STORE_LOGGING_PRODUCTS', true),
            'cart' => env('STORE_LOGGING_CART', true),
            'errors' => env('STORE_LOGGING_ERRORS', true),
        ],
        'retention_days' => env('STORE_LOGGING_RETENTION_DAYS', 30),
    ],
];