<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderStatusChanged extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Order $order,
        public string $oldStatus,
        public string $newStatus,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Order #{$this->order->order_number} status changed")
            ->greeting("Hello {$notifiable->name},")
            ->line("Order **#{$this->order->order_number}** status has been updated.")
            ->line("Changed from **{$this->oldStatus}** to **{$this->newStatus}**.")
            ->action('View Order', url("/admin/orders/{$this->order->id}"))
            ->line('Thank you for using KM Decor!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
            'url' => "/admin/orders/{$this->order->id}",
        ];
    }
}
