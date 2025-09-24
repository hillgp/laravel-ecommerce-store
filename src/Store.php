<?php

namespace LaravelEcommerce\Store;

use Illuminate\Contracts\Foundation\Application;
use LaravelEcommerce\Store\Services\CartService;
use LaravelEcommerce\Store\Services\OrderService;
use LaravelEcommerce\Store\Services\ProductService;
use LaravelEcommerce\Store\Services\PaymentService;
use LaravelEcommerce\Store\Services\CustomerService;
use LaravelEcommerce\Store\Services\ReviewService;
use LaravelEcommerce\Store\Services\CouponService;
use LaravelEcommerce\Store\Services\ShippingService;
use LaravelEcommerce\Store\Services\NotificationService;

class Store
{
    /**
     * The application instance.
     */
    protected Application $app;

    /**
     * The cart service instance.
     */
    protected CartService $cart;

    /**
     * The order service instance.
     */
    protected OrderService $orders;

    /**
     * The product service instance.
     */
    protected ProductService $products;

    /**
     * The payment service instance.
     */
    protected PaymentService $payments;

    /**
     * The customer service instance.
     */
    protected CustomerService $customers;

    /**
     * The review service instance.
     */
    protected ReviewService $reviews;

    /**
     * The coupon service instance.
     */
    protected CouponService $coupons;

    /**
     * The shipping service instance.
     */
    protected ShippingService $shipping;

    /**
     * The notification service instance.
     */
    protected NotificationService $notifications;

    /**
     * Create a new Store instance.
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->initializeServices();
    }

    /**
     * Initialize all services.
     */
    protected function initializeServices(): void
    {
        $this->cart = $this->app->make(CartService::class);
        $this->orders = $this->app->make(OrderService::class);
        $this->products = $this->app->make(ProductService::class);
        $this->payments = $this->app->make(PaymentService::class);
        $this->customers = $this->app->make(CustomerService::class);
        $this->reviews = $this->app->make(ReviewService::class);
        $this->coupons = $this->app->make(CouponService::class);
        $this->shipping = $this->app->make(ShippingService::class);
        $this->notifications = $this->app->make(NotificationService::class);
    }

    /**
     * Get the cart service.
     */
    public function cart(): CartService
    {
        return $this->cart;
    }

    /**
     * Get the order service.
     */
    public function orders(): OrderService
    {
        return $this->orders;
    }

    /**
     * Get the product service.
     */
    public function products(): ProductService
    {
        return $this->products;
    }

    /**
     * Get the payment service.
     */
    public function payments(): PaymentService
    {
        return $this->payments;
    }

    /**
     * Get the customer service.
     */
    public function customers(): CustomerService
    {
        return $this->customers;
    }

    /**
     * Get the review service.
     */
    public function reviews(): ReviewService
    {
        return $this->reviews;
    }

    /**
     * Get the coupon service.
     */
    public function coupons(): CouponService
    {
        return $this->coupons;
    }

    /**
     * Get the shipping service.
     */
    public function shipping(): ShippingService
    {
        return $this->shipping;
    }

    /**
     * Get the notification service.
     */
    public function notifications(): NotificationService
    {
        return $this->notifications;
    }

    /**
     * Get store configuration.
     */
    public function config(string $key = null, $default = null)
    {
        $config = config('store');

        if ($key === null) {
            return $config;
        }

        return data_get($config, $key, $default);
    }

    /**
     * Get store currency.
     */
    public function currency(): string
    {
        return $this->config('currency', 'BRL');
    }

    /**
     * Get store locale.
     */
    public function locale(): string
    {
        return $this->config('locale', 'pt_BR');
    }

    /**
     * Check if store is in maintenance mode.
     */
    public function isMaintenanceMode(): bool
    {
        return $this->config('maintenance', false);
    }

    /**
     * Get available payment gateways.
     */
    public function getPaymentGateways(): array
    {
        return $this->config('gateways', []);
    }

    /**
     * Get available shipping methods.
     */
    public function getShippingMethods(): array
    {
        return $this->config('shipping_methods', []);
    }

    /**
     * Get store settings.
     */
    public function getSettings(): array
    {
        return $this->config('settings', []);
    }

    /**
     * Set store configuration.
     */
    public function setConfig(string $key, $value): void
    {
        config(['store.' . $key => $value]);
    }

    /**
     * Set store currency.
     */
    public function setCurrency(string $currency): void
    {
        $this->setConfig('currency', $currency);
    }

    /**
     * Set store locale.
     */
    public function setLocale(string $locale): void
    {
        $this->setConfig('locale', $locale);
    }

    /**
     * Enable maintenance mode.
     */
    public function enableMaintenanceMode(): void
    {
        $this->setConfig('maintenance', true);
    }

    /**
     * Disable maintenance mode.
     */
    public function disableMaintenanceMode(): void
    {
        $this->setConfig('maintenance', false);
    }

    /**
     * Toggle maintenance mode.
     */
    public function toggleMaintenanceMode(): bool
    {
        $current = $this->isMaintenanceMode();
        $this->setConfig('maintenance', !$current);
        return !$current;
    }

    /**
     * Get the application instance.
     */
    public function getApp(): Application
    {
        return $this->app;
    }

    /**
     * Get the store version.
     */
    public function version(): string
    {
        return '2.0.0';
    }

    /**
     * Get store information.
     */
    public function info(): array
    {
        return [
            'name' => 'Laravel E-commerce Store',
            'version' => $this->version(),
            'currency' => $this->currency(),
            'locale' => $this->locale(),
            'maintenance' => $this->isMaintenanceMode(),
            'gateways' => $this->getPaymentGateways(),
            'shipping_methods' => $this->getShippingMethods(),
            'settings' => $this->getSettings(),
        ];
    }
}