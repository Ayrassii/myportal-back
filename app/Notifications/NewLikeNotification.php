<?php

namespace App\Notifications;

use App\Like;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewLikeNotification extends Notification
{
    use Queueable;
    private $like;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Like $like)
    {
        $this->like = $like;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        $message = "";
        switch ($this->like->entry->type) {
            case "TYPE_FEED":
                $message = $this->like->owner->fullName() . " aime votre Publication";
                break;
            case "TYPE_EVENT":
                $message = $this->like->owner->fullName() . " aime votre Evenement";
                break;
            case "TYPE_Article":
                $message = $this->like->owner->fullName() . " aime votre Article";
                break;
        }
        return [
            "icone" => "fa fa-thumbs-up",
            "iconeColor" => "#17a2b8",
            "entry_id" => $this->like->entry->id,
            "entry" => $this->like->entry->type,
            "message" => $message,
        ];
    }
}
