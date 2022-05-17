<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

class UserRegistered extends Mailable
{
    use Queueable, SerializesModels;

    public $user;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($user)
    {
        $this->queue = 'email';
        $this->user = $user;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $token = Str::random();
        Redis::set('verify:' . $this->user->email, $token);
        Redis::expire('verify:' . $this->user->email, 86400);
        $url = config('app.url') . "/auth/verify-email?token=$token" . "&email=" . $this->user->email;

        return $this
            ->markdown('emails.users.registered', [
                'url' => $url
            ])
            ->subject('Welcome to ' . config('app.name'));
    }
}
