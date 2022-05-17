<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class ResetPassword extends \Illuminate\Auth\Notifications\ResetPassword implements ShouldQueue
{
    use Queueable;

    private $url;

    public function __construct($token, $url = '')
    {
        $this->url = $url;
        parent::__construct($token);
    }

    public function toMail($notifiable)
    {
        $email = $notifiable->getEmailForPasswordReset();
        $url = $this->url . "$this->token?email = $email";
        return $this->buildMailMessage($url);
    }
}
