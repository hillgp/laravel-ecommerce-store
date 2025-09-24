<?php

namespace LaravelEcommerce\Store\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class CustomerPasswordResetMail extends Mailable
{
    use Queueable, SerializesModels;

    public $customer;
    public $resetUrl;

    /**
     * Create a new message instance.
     */
    public function __construct($customer, $token)
    {
        $this->customer = $customer;
        $this->resetUrl = $this->generateResetUrl($token);
    }

    /**
     * Build the message.
     */
    public function build(): self
    {
        return $this->subject('Redefinição de Senha')
            ->view('store::emails.customers.password-reset')
            ->with([
                'customer' => $this->customer,
                'resetUrl' => $this->resetUrl,
                'expiresIn' => '24 horas',
            ]);
    }

    /**
     * Get the message subject.
     */
    public function getSubject(): string
    {
        return 'Redefinição de Senha';
    }

    /**
     * Get the message content type.
     */
    public function getContentType(): string
    {
        return 'text/html';
    }

    /**
     * Generate password reset URL.
     */
    protected function generateResetUrl(string $token): string
    {
        return URL::temporarySignedRoute(
            'customer.password.reset',
            now()->addHours(24),
            [
                'token' => $token,
                'email' => $this->customer->email,
            ]
        );
    }

    /**
     * Get security tips.
     */
    public function getSecurityTips(): array
    {
        return [
            'Não compartilhe este email com ninguém',
            'O link expira em 24 horas por segurança',
            'Se você não solicitou esta redefinição, ignore este email',
            'Recomendamos usar uma senha forte com pelo menos 8 caracteres',
            'Use uma combinação de letras, números e símbolos',
        ];
    }

    /**
     * Get password requirements.
     */
    public function getPasswordRequirements(): array
    {
        return [
            'Mínimo de 8 caracteres',
            'Pelo menos uma letra maiúscula',
            'Pelo menos uma letra minúscula',
            'Pelo menos um número',
            'Pelo menos um símbolo especial',
        ];
    }

    /**
     * Get help information.
     */
    public function getHelpInfo(): array
    {
        return [
            'Se o link não funcionar, copie e cole no navegador',
            'Entre em contato conosco se precisar de ajuda',
            'Nosso suporte está disponível 24/7',
        ];
    }

    /**
     * Get warning message.
     */
    public function getWarningMessage(): string
    {
        return 'Se você não solicitou esta redefinição de senha, por favor, ignore este email. Sua senha permanecerá inalterada.';
    }

    /**
     * Get support contact.
     */
    public function getSupportContact(): array
    {
        return [
            'email' => config('store.support.email', 'suporte@loja.com'),
            'phone' => config('store.support.phone', '(11) 99999-9999'),
            'hours' => config('store.support.hours', '24/7'),
        ];
    }
}