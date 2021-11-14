<?php

namespace App\Notifications;

use App\Entry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewEntryNotification extends Notification
{
    use Queueable;
    private $entry;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Entry $entry)
    {
        $this->entry = $entry;
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
        $icone = "";
        $iconeColor = "#28a745";
        switch ($this->entry->type) {
            case "TYPE_FEED":
                $icone = "fa fa-sticky-note";
                $message = $this->entry->owner->fullName() . " a ajouté une nouvelle Publication";
                break;
            case "TYPE_EVENT":
                $icone = "fa fa-calendar";
                $message = $this->entry->owner->fullName() . " a ajouté un nouvel Evénement";
                break;
            case "TYPE_ARTICLE":
                $icone = "fa fa-newspaper-o";
                $message = $this->entry->owner->fullName() . " a ajouté un nouvel Article";
                break;
        }
        return [
            "icone" => $icone,
            "iconeColor" => $iconeColor,
            "entry_id" => $this->entry->id,
            "entry" => $this->entry->type,
            "message" => $message,
        ];
    }
}
