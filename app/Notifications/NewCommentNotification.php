<?php

namespace App\Notifications;

use App\Comment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewCommentNotification extends Notification
{
    use Queueable;
    private $comment;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Comment $comment)
    {
        $this->comment = $comment;
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
        switch ($this->comment->entry->type) {
            case "TYPE_FEED":
                $message = $this->comment->owner->fullName() . " a commenté votre Publication";
                break;
            case "TYPE_EVENT":
                $message = $this->comment->owner->fullName() . " a commenté votre Evenement";
                break;
            case "TYPE_Article":
                $message = $this->comment->owner->fullName() . " a commenté votre Article";
                break;
        }
        return [
            "icone" => "fa fa-comment",
            "iconeColor" => "#ffc107",
            "entry_id" => $this->comment->entry->id,
            "entry" => $this->comment->entry->type,
            "message" => $message,
        ];
    }
}
