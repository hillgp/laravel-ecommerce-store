<?php

namespace LaravelEcommerce\Store\Mail;

use LaravelEcommerce\Store\Models\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class GenericNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public Notification $notification;

    /**
     * Create a new message instance.
     */
    public function __construct(Notification $notification)
    {
        $this->notification = $notification;
        $this->subject($this->notification->title ?? 'Notificação da Loja');
    }

    /**
     * Build the message.
     */
    public function build(): self
    {
        return $this->view('store::emails.notification')
            ->with([
                'notification' => $this->notification,
                'recipient' => $this->getRecipient(),
            ]);
    }

    /**
     * Get the notification recipient.
     */
    protected function getRecipient()
    {
        $modelClass = 'LaravelEcommerce\\Store\\Models\\' . ucfirst($this->notification->recipient_type);

        if (!class_exists($modelClass)) {
            return null;
        }

        return $modelClass::find($this->notification->recipient_id);
    }
}