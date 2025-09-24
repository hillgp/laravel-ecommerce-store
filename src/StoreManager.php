<?php

namespace LaravelEcommerce\Store;

use Illuminate\Support\Manager;
use LaravelEcommerce\Store\Services\CartService;
use LaravelEcommerce\Store\Services\OrderService;
use LaravelEcommerce\Store\Services\ProductService;
use LaravelEcommerce\Store\Services\PaymentService;
use LaravelEcommerce\Store\Services\CustomerService;
use LaravelEcommerce\Store\Services\ReviewService;
use LaravelEcommerce\Store\Services\CouponService;
use LaravelEcommerce\Store\Services\ShippingService;
use LaravelEcommerce\Store\Services\NotificationService;

class StoreManager extends Manager
{
    /**
     * The application instance.
     */
    protected $app;

    /**
     * Create a new StoreManager instance.
     */
    public function __construct($app)
    {
        $this->app = $app;
        parent::__construct($app);
    }

    /**
     * Get the default driver name.
     */
    public function getDefaultDriver(): string
    {
        return 'store';
    }

    /**
     * Create the store driver.
     */
    protected function createStoreDriver(): Store
    {
        return new Store($this->app);
    }

    /**
     * Create the cart service.
     */
    protected function createCartDriver(): CartService
    {
        return $this->app->make(CartService::class);
    }

    /**
     * Create the order service.
     */
    protected function createOrderDriver(): OrderService
    {
        return $this->app->make(OrderService::class);
    }

    /**
     * Create the product service.
     */
    protected function createProductDriver(): ProductService
    {
        return $this->app->make(ProductService::class);
    }

    /**
     * Create the payment service.
     */
    protected function createPaymentDriver(): PaymentService
    {
        return $this->app->make(PaymentService::class);
    }

    /**
     * Create the customer service.
     */
    protected function createCustomerDriver(): CustomerService
    {
        return $this->app->make(CustomerService::class);
    }

    /**
     * Create the review service.
     */
    protected function createReviewDriver(): ReviewService
    {
        return $this->app->make(ReviewService::class);
    }

    /**
     * Create the coupon service.
     */
    protected function createCouponDriver(): CouponService
    {
        return $this->app->make(CouponService::class);
    }

    /**
     * Create the shipping service.
     */
    protected function createShippingDriver(): ShippingService
    {
        return $this->app->make(ShippingService::class);
    }

    /**
     * Create the notification service.
     */
    protected function createNotificationDriver(): NotificationService
    {
        return $this->app->make(NotificationService::class);
    }

    /**
     * Get the cart service.
     */
    public function cart(): CartService
    {
        return $this->driver('cart');
    }

    /**
     * Get the order service.
     */
    public function orders(): OrderService
    {
        return $this->driver('order');
    }

    /**
     * Get the product service.
     */
    public function products(): ProductService
    {
        return $this->driver('product');
    }

    /**
     * Get the payment service.
     */
    public function payments(): PaymentService
    {
        return $this->driver('payment');
    }

    /**
     * Get the customer service.
     */
    public function customers(): CustomerService
    {
        return $this->driver('customer');
    }

    /**
     * Get the review service.
     */
    public function reviews(): ReviewService
    {
        return $this->driver('review');
    }

    /**
     * Get the coupon service.
     */
    public function coupons(): CouponService
    {
        return $this->driver('coupon');
    }

    /**
     * Get the shipping service.
     */
    public function shipping(): ShippingService
    {
        return $this->driver('shipping');
    }

    /**
     * Get the notification service.
     */
    public function notifications(): NotificationService
    {
        return $this->driver('notification');
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
}