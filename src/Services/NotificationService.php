<?php

namespace LaravelEcommerce\Store\Services;

use LaravelEcommerce\Store\Models\Order;
use LaravelEcommerce\Store\Models\Customer;
use LaravelEcommerce\Store\Models\Notification;
use LaravelEcommerce\Store\Models\NotificationTemplate;
use LaravelEcommerce\Store\Models\NotificationSetting;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class NotificationService
{
    /**
     * Send order confirmation email.
     */
    public function sendOrderConfirmation(Order $order): void
    {
        try {
            Mail::to($order->user->email ?? $order->billing_email)
                ->send(new \LaravelEcommerce\Store\Mail\OrderConfirmation($order));

            Log::info("Order confirmation email sent for order {$order->order_number}");
        } catch (\Exception $e) {
            Log::error("Failed to send order confirmation email for order {$order->order_number}: " . $e->getMessage());
        }
    }

    /**
     * Send order shipped email.
     */
    public function sendOrderShipped(Order $order): void
    {
        try {
            Mail::to($order->user->email ?? $order->billing_email)
                ->send(new \LaravelEcommerce\Store\Mail\OrderShipped($order));

            Log::info("Order shipped email sent for order {$order->order_number}");
        } catch (\Exception $e) {
            Log::error("Failed to send order shipped email for order {$order->order_number}: " . $e->getMessage());
        }
    }

    /**
     * Send order delivered email.
     */
    public function sendOrderDelivered(Order $order): void
    {
        try {
            Mail::to($order->user->email ?? $order->billing_email)
                ->send(new \LaravelEcommerce\Store\Mail\OrderDelivered($order));

            Log::info("Order delivered email sent for order {$order->order_number}");
        } catch (\Exception $e) {
            Log::error("Failed to send order delivered email for order {$order->order_number}: " . $e->getMessage());
        }
    }

    /**
     * Send order cancelled email.
     */
    public function sendOrderCancelled(Order $order): void
    {
        try {
            Mail::to($order->user->email ?? $order->billing_email)
                ->send(new \LaravelEcommerce\Store\Mail\OrderCancelled($order));

            Log::info("Order cancelled email sent for order {$order->order_number}");
        } catch (\Exception $e) {
            Log::error("Failed to send order cancelled email for order {$order->order_number}: " . $e->getMessage());
        }
    }

    /**
     * Send welcome email to customer.
     */
    public function sendWelcomeEmail(Customer $customer): void
    {
        try {
            Mail::to($customer->email)
                ->send(new \LaravelEcommerce\Store\Mail\Welcome($customer));

            Log::info("Welcome email sent to customer {$customer->email}");
        } catch (\Exception $e) {
            Log::error("Failed to send welcome email to customer {$customer->email}: " . $e->getMessage());
        }
    }

    /**
     * Send password reset email.
     */
    public function sendPasswordReset(Customer $customer, string $token): void
    {
        try {
            Mail::to($customer->email)
                ->send(new \LaravelEcommerce\Store\Mail\PasswordReset($customer, $token));

            Log::info("Password reset email sent to customer {$customer->email}");
        } catch (\Exception $e) {
            Log::error("Failed to send password reset email to customer {$customer->email}: " . $e->getMessage());
        }
    }

    /**
     * Send email verification.
     */
    public function sendEmailVerification(Customer $customer, string $token): void
    {
        try {
            Mail::to($customer->email)
                ->send(new \LaravelEcommerce\Store\Mail\EmailVerification($customer, $token));

            Log::info("Email verification sent to customer {$customer->email}");
        } catch (\Exception $e) {
            Log::error("Failed to send email verification to customer {$customer->email}: " . $e->getMessage());
        }
    }

    /**
     * Send low stock notification.
     */
    public function sendLowStockNotification(int $productId, int $currentStock): void
    {
        try {
            $admins = config('store.notification_admins', []);
            $product = \LaravelEcommerce\Store\Models\Product::find($productId);

            foreach ($admins as $email) {
                Mail::to($email)->send(new \LaravelEcommerce\Store\Mail\LowStockAlert($product, $currentStock));
            }

            Log::info("Low stock notification sent for product {$productId}");
        } catch (\Exception $e) {
            Log::error("Failed to send low stock notification for product {$productId}: " . $e->getMessage());
        }
    }

    /**
     * Send new order notification to admin.
     */
    public function sendNewOrderNotification(Order $order): void
    {
        try {
            $admins = config('store.notification_admins', []);

            foreach ($admins as $email) {
                Mail::to($email)->send(new \LaravelEcommerce\Store\Mail\NewOrder($order));
            }

            Log::info("New order notification sent for order {$order->order_number}");
        } catch (\Exception $e) {
            Log::error("Failed to send new order notification for order {$order->order_number}: " . $e->getMessage());
        }
    }

    /**
     * Send SMS notification.
     */
    public function sendSMS(string $phone, string $message): void
    {
        // This would integrate with SMS service provider
        // Example: Twilio, AWS SNS, etc.

        Log::info("SMS sent to {$phone}: {$message}");
    }

    /**
     * Send WhatsApp notification.
     */
    public function sendWhatsApp(string $phone, string $message): void
    {
        // This would integrate with WhatsApp Business API

        Log::info("WhatsApp message sent to {$phone}: {$message}");
    }

    /**
     * Send push notification.
     */
    public function sendPushNotification(string $token, string $title, string $body, array $data = []): void
    {
        // This would integrate with push notification service
        // Example: Firebase Cloud Messaging, OneSignal, etc.

        Log::info("Push notification sent: {$title} - {$body}");
    }

    /**
     * Send order status update notification.
     */
    public function sendOrderStatusUpdate(Order $order, string $status): void
    {
        $message = $this->getStatusMessage($status);

        // Send email
        $this->sendOrderStatusEmail($order, $status, $message);

        // Send SMS if phone is available
        if ($order->user->phone ?? false) {
            $this->sendSMS($order->user->phone, "Seu pedido {$order->order_number} foi {$message}");
        }

        // Send push notification if token is available
        if ($order->user->push_token ?? false) {
            $this->sendPushNotification(
                $order->user->push_token,
                'Atualização do Pedido',
                "Seu pedido {$order->order_number} foi {$message}"
            );
        }
    }

    /**
     * Send order status email.
     */
    protected function sendOrderStatusEmail(Order $order, string $status, string $message): void
    {
        try {
            Mail::to($order->user->email ?? $order->billing_email)
                ->send(new \LaravelEcommerce\Store\Mail\OrderStatusUpdate($order, $status, $message));

            Log::info("Order status email sent for order {$order->order_number}: {$status}");
        } catch (\Exception $e) {
            Log::error("Failed to send order status email for order {$order->order_number}: " . $e->getMessage());
        }
    }

    /**
     * Get status message.
     */
    protected function getStatusMessage(string $status): string
    {
        return match ($status) {
            'confirmed' => 'confirmado',
            'shipped' => 'enviado',
            'delivered' => 'entregue',
            'cancelled' => 'cancelado',
            default => 'atualizado',
        };
    }

    /**
     * Send newsletter email.
     */
    public function sendNewsletter(array $emails, string $subject, string $content): void
    {
        try {
            foreach ($emails as $email) {
                Mail::to($email)->send(new \LaravelEcommerce\Store\Mail\Newsletter($subject, $content));
            }

            Log::info("Newsletter sent to " . count($emails) . " recipients");
        } catch (\Exception $e) {
            Log::error("Failed to send newsletter: " . $e->getMessage());
        }
    }

    /**
     * Send promotional email.
     */
    public function sendPromotionalEmail(Customer $customer, string $subject, string $content): void
    {
        try {
            Mail::to($customer->email)
                ->send(new \LaravelEcommerce\Store\Mail\Promotion($customer, $subject, $content));

            Log::info("Promotional email sent to customer {$customer->email}");
        } catch (\Exception $e) {
            Log::error("Failed to send promotional email to customer {$customer->email}: " . $e->getMessage());
        }
    }


    /**
     * Update notification preferences.
     */
    public function updateNotificationPreferences(Customer $customer, array $preferences): void
    {
        $customer->update(['notification_preferences' => $preferences]);
    }

    /**
     * Criar uma nova notificação.
     */
    public function createNotification(array $data): Notification
    {
        return Notification::create([
            'type' => $data['type'],
            'channel' => $data['channel'] ?? 'email',
            'recipient_type' => $data['recipient_type'] ?? 'customer',
            'recipient_id' => $data['recipient_id'],
            'title' => $data['title'] ?? null,
            'content' => $data['content'],
            'scheduled_at' => $data['scheduled_at'] ?? null,
            'status' => 'pending',
            'priority' => $data['priority'] ?? 'normal',
            'metadata' => $data['metadata'] ?? null,
        ]);
    }

    /**
     * Enviar notificação usando template.
     */
    public function sendNotificationWithTemplate(
        string $templateCode,
        array $variables,
        string $recipientType,
        int $recipientId,
        string $channel = 'email',
        ?Carbon $scheduledAt = null
    ): bool {
        $template = NotificationTemplate::where('code', $templateCode)
            ->where('channel', $channel)
            ->first();

        if (!$template) {
            Log::error("Template not found: {$templateCode} for channel: {$channel}");
            return false;
        }

        $processedContent = $template->process($variables);

        $notification = $this->createNotification([
            'type' => $template->type,
            'channel' => $channel,
            'recipient_type' => $recipientType,
            'recipient_id' => $recipientId,
            'title' => $processedContent['title'] ?? null,
            'content' => $processedContent['content'],
            'scheduled_at' => $scheduledAt,
            'metadata' => ['template_code' => $templateCode, 'variables' => $variables],
        ]);

        return $this->dispatchNotification($notification);
    }

    /**
     * Despachar notificação para processamento.
     */
    public function dispatchNotification(Notification $notification): bool
    {
        if (!$notification->canBeSent()) {
            return false;
        }

        try {
            if ($notification->scheduled_at && $notification->scheduled_at->isFuture()) {
                // Agendar para envio posterior
                Queue::later(
                    $notification->scheduled_at,
                    new \LaravelEcommerce\Store\Jobs\ProcessNotification($notification)
                );
            } else {
                // Enviar imediatamente
                Queue::push(new \LaravelEcommerce\Store\Jobs\ProcessNotification($notification));
            }

            $notification->markAsSent();
            return true;
        } catch (\Exception $e) {
            $notification->markAsFailed($e->getMessage());
            Log::error("Failed to dispatch notification {$notification->id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Processar notificações pendentes.
     */
    public function processPendingNotifications(): int
    {
        $notifications = Notification::where('status', 'pending')
            ->where(function ($query) {
                $query->whereNull('scheduled_at')
                      ->orWhere('scheduled_at', '<=', Carbon::now());
            })
            ->limit(50)
            ->get();

        $processed = 0;

        foreach ($notifications as $notification) {
            if ($this->dispatchNotification($notification)) {
                $processed++;
            }
        }

        return $processed;
    }

    /**
     * Obter configurações de notificação.
     */
    public function getNotificationSettings(string $type = 'general'): Collection
    {
        return NotificationSetting::where('type', $type)->get();
    }

    /**
     * Atualizar configuração de notificação.
     */
    public function setNotificationSetting(string $key, $value, string $type = 'general'): void
    {
        NotificationSetting::set($key, $value, $type);
    }

    /**
     * Verificar se notificação está habilitada.
     */
    public function isNotificationEnabled(string $key, string $type = 'general'): bool
    {
        $setting = NotificationSetting::where('type', $type)
            ->where('key', $key)
            ->first();

        return $setting ? filter_var($setting->value, FILTER_VALIDATE_BOOLEAN) : false;
    }

    /**
     * Obter estatísticas de notificações.
     */
    public function getNotificationStats(): array
    {
        return [
            'total' => Notification::count(),
            'pending' => Notification::where('status', 'pending')->count(),
            'sent' => Notification::where('status', 'sent')->count(),
            'failed' => Notification::where('status', 'failed')->count(),
            'today' => Notification::whereDate('created_at', Carbon::today())->count(),
        ];
    }

    /**
     * Limpar notificações antigas.
     */
    public function cleanupOldNotifications(int $days = 30): int
    {
        return Notification::where('created_at', '<', Carbon::now()->subDays($days))
            ->delete();
    }

    /**
     * Retry de notificações falhadas.
     */
    public function retryFailedNotifications(): int
    {
        $failedNotifications = Notification::where('status', 'failed')
            ->where('retry_count', '<', 3)
            ->limit(20)
            ->get();

        $retried = 0;

        foreach ($failedNotifications as $notification) {
            $notification->increment('retry_count');

            if ($this->dispatchNotification($notification)) {
                $retried++;
            }
        }

        return $retried;
    }

    /**
     * Enviar notificação de teste.
     */
    public function sendTestNotification(string $channel, string $recipient): bool
    {
        try {
            $notification = $this->createNotification([
                'type' => 'test',
                'channel' => $channel,
                'recipient_type' => 'admin',
                'recipient_id' => 1,
                'title' => 'Notificação de Teste',
                'content' => 'Esta é uma notificação de teste do sistema de notificações.',
                'metadata' => ['test' => true],
            ]);

            return $this->dispatchNotification($notification);
        } catch (\Exception $e) {
            Log::error("Failed to send test notification: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obter templates disponíveis.
     */
    public function getAvailableTemplates(): Collection
    {
        return NotificationTemplate::all();
    }

    /**
     * Criar template de notificação.
     */
    public function createNotificationTemplate(array $data): NotificationTemplate
    {
        return NotificationTemplate::create([
            'code' => $data['code'],
            'type' => $data['type'],
            'channel' => $data['channel'],
            'title' => $data['title'],
            'content' => $data['content'],
            'variables' => $data['variables'] ?? [],
            'locale' => $data['locale'] ?? 'pt-BR',
            'is_active' => $data['is_active'] ?? true,
        ]);
    }

    /**
     * Atualizar template de notificação.
     */
    public function updateNotificationTemplate(string $code, array $data): bool
    {
        $template = NotificationTemplate::where('code', $code)->first();

        if (!$template) {
            return false;
        }

        $template->update($data);
        return true;
    }

    /**
     * Processar variáveis do template.
     */
    public function processTemplateVariables(string $content, array $variables): string
    {
        foreach ($variables as $key => $value) {
            $placeholder = '{{' . $key . '}}';
            $content = str_replace($placeholder, $value, $content);
        }

        return $content;
    }

    /**
     * Validar template de notificação.
     */
    public function validateNotificationTemplate(array $data): array
    {
        $errors = [];

        if (empty($data['code'])) {
            $errors[] = 'Código do template é obrigatório';
        }

        if (empty($data['type'])) {
            $errors[] = 'Tipo do template é obrigatório';
        }

        if (empty($data['channel'])) {
            $errors[] = 'Canal do template é obrigatório';
        }

        if (empty($data['content'])) {
            $errors[] = 'Conteúdo do template é obrigatório';
        }

        return $errors;
    }
}