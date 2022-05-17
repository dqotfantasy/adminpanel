<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Redis;

class VerifyEmail extends Mailable
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
        $otp = mt_rand(100000, 999999);
        Redis::set('verify:' . $this->user->email, $otp);
        Redis::expire('verify:' . $this->user->email, 1800);

        return $this
            ->markdown('emails.users.verify', [
                'otp' => $otp
            ])
            ->subject('Verify email');
    }
}
