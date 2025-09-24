<?php

namespace LaravelEcommerce\Store\Jobs;

use LaravelEcommerce\Store\Models\Notification;
use LaravelEcommerce\Store\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [10, 30, 60]; // Retry delays in seconds

    protected Notification $notification;

    /**
     * Create a new job instance.
     */
    public function __construct(Notification $notification)
    {
        $this->notification = $notification;
        $this->onQueue('notifications');
    }

    /**
     * Execute the job.
     */
    public function handle(NotificationService $notificationService): void
    {
        try {
            Log::info("Processing notification {$this->notification->id} via {$this->notification->channel}");

            $sent = false;

            switch ($this->notification->channel) {
                case 'email':
                    $sent = $this->sendEmailNotification();
                    break;

                case 'sms':
                    $sent = $this->sendSMSNotification();
                    break;

                case 'push':
                    $sent = $this->sendPushNotification();
                    break;

                case 'database':
                    $sent = $this->sendDatabaseNotification();
                    break;

                default:
                    Log::warning("Unknown notification channel: {$this->notification->channel}");
                    return;
            }

            if ($sent) {
                $this->notification->update([
                    'status' => 'sent',
                    'sent_at' => now(),
                    'error_message' => null,
                ]);

                Log::info("Notification {$this->notification->id} sent successfully");
            } else {
                throw new \Exception("Failed to send notification via {$this->notification->channel}");
            }

        } catch (\Exception $e) {
            Log::error("Failed to process notification {$this->notification->id}: " . $e->getMessage());

            $this->notification->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'failed_at' => now(),
            ]);

            // Re-throw to trigger retry logic
            throw $e;
        }
    }

    /**
     * Send email notification.
     */
    protected function sendEmailNotification(): bool
    {
        try {
            $recipient = $this->getRecipient();

            if (!$recipient || !isset($recipient->email)) {
                Log::warning("No email found for notification recipient");
                return false;
            }

            \Illuminate\Support\Facades\Mail::to($recipient->email)->send(
                new \LaravelEcommerce\Store\Mail\GenericNotification($this->notification)
            );

            return true;
        } catch (\Exception $e) {
            Log::error("Failed to send email notification: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send SMS notification.
     */
    protected function sendSMSNotification(): bool
    {
        try {
            $recipient = $this->getRecipient();

            if (!$recipient || !isset($recipient->phone)) {
                Log::warning("No phone found for SMS notification recipient");
                return false;
            }

            // Integration with SMS service provider (Twilio, AWS SNS, etc.)
            $notificationService = app(NotificationService::class);
            $notificationService->sendSMS(
                $recipient->phone,
                $this->notification->content
            );

            return true;
        } catch (\Exception $e) {
            Log::error("Failed to send SMS notification: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send push notification.
     */
    protected function sendPushNotification(): bool
    {
        try {
            $recipient = $this->getRecipient();

            if (!$recipient || !isset($recipient->push_token)) {
                Log::warning("No push token found for notification recipient");
                return false;
            }

            // Integration with push notification service (Firebase, OneSignal, etc.)
            $notificationService = app(NotificationService::class);
            $notificationService->sendPushNotification(
                $recipient->push_token,
                $this->notification->title ?? 'Notificação',
                $this->notification->content
            );

            return true;
        } catch (\Exception $e) {
            Log::error("Failed to send push notification: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send database notification.
     */
    protected function sendDatabaseNotification(): bool
    {
        try {
            // Database notifications are stored in the notifications table
            // and can be displayed in the user's notification center
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to send database notification: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get the notification recipient.
     */
    protected function getRecipient()
    {
        $modelClass = 'LaravelEcommerce\\Store\\Models\\' . ucfirst($this->notification->recipient_type);

        if (!class_exists($modelClass)) {
            Log::error("Invalid recipient type: {$this->notification->recipient_type}");
            return null;
        }

        return $modelClass::find($this->notification->recipient_id);
    }

    /**
     * Handle job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Notification job failed permanently: {$exception->getMessage()}", [
            'notification_id' => $this->notification->id,
            'attempts' => $this->attempts(),
        ]);

        $this->notification->update([
            'status' => 'failed',
            'error_message' => $exception->getMessage(),
            'failed_at' => now(),
        ]);
    }
}