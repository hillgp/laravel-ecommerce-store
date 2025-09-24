<?php

namespace LaravelEcommerce\Store\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use LaravelEcommerce\Store\Models\Customer;

class CustomerWelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public $customer;
    public $welcomeCoupon;

    /**
     * Create a new message instance.
     */
    public function __construct(Customer $customer, $welcomeCoupon = null)
    {
        $this->customer = $customer;
        $this->welcomeCoupon = $welcomeCoupon;
    }

    /**
     * Build the message.
     */
    public function build(): self
    {
        return $this->subject('Bem-vindo à nossa loja!')
            ->view('store::emails.customers.welcome')
            ->with([
                'customer' => $this->customer,
                'welcomeCoupon' => $this->welcomeCoupon,
                'loginUrl' => route('customer.login'),
                'profileUrl' => route('customer.profile'),
            ]);
    }

    /**
     * Get the message subject.
     */
    public function getSubject(): string
    {
        return 'Bem-vindo à nossa loja!';
    }

    /**
     * Get the message content type.
     */
    public function getContentType(): string
    {
        return 'text/html';
    }

    /**
     * Get welcome benefits.
     */
    public function getWelcomeBenefits(): array
    {
        return [
            'Frete grátis em compras acima de R$ 200',
            'Programa de pontos e cashback',
            'Suporte prioritário',
            'Ofertas exclusivas para membros',
            'Histórico completo de pedidos',
            'Lista de desejos personalizada',
        ];
    }

    /**
     * Get featured categories.
     */
    public function getFeaturedCategories(): \Illuminate\Support\Collection
    {
        return \LaravelEcommerce\Store\Models\Category::active()
            ->featured()
            ->limit(6)
            ->get();
    }

    /**
     * Get featured products.
     */
    public function getFeaturedProducts(): \Illuminate\Support\Collection
    {
        return \LaravelEcommerce\Store\Models\Product::active()
            ->featured()
            ->limit(8)
            ->get();
    }

    /**
     * Get personalized recommendations.
     */
    public function getPersonalizedRecommendations(): \Illuminate\Support\Collection
    {
        // This would use a recommendation engine
        // For now, return popular products
        return \LaravelEcommerce\Store\Models\Product::active()
            ->orderBy('sold_count', 'desc')
            ->limit(4)
            ->get();
    }

    /**
     * Get welcome message.
     */
    public function getWelcomeMessage(): string
    {
        $messages = [
            "Olá {$this->customer->first_name}! Seja muito bem-vindo à nossa loja!",
            "É um prazer ter você conosco, {$this->customer->first_name}!",
            "{$this->customer->first_name}, sua jornada de compras incrível começa agora!",
            "Bem-vindo à família, {$this->customer->first_name}! Esperamos tornar suas compras inesquecíveis!",
        ];

        return $messages[array_rand($messages)];
    }

    /**
     * Get next steps.
     */
    public function getNextSteps(): array
    {
        return [
            [
                'title' => 'Complete seu perfil',
                'description' => 'Adicione suas informações pessoais para uma experiência personalizada',
                'url' => route('customer.profile'),
                'icon' => 'user',
            ],
            [
                'title' => 'Explore nossos produtos',
                'description' => 'Descubra milhares de produtos incríveis',
                'url' => route('products.index'),
                'icon' => 'shopping-bag',
            ],
            [
                'title' => 'Configure preferências',
                'description' => 'Personalize suas notificações e preferências',
                'url' => route('customer.preferences'),
                'icon' => 'settings',
            ],
        ];
    }
}