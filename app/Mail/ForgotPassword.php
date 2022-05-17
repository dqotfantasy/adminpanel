<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Redis;

class ForgotPassword extends Mailable
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
//        $token = Str::random();
        $otp = mt_rand(100000, 999999);
        Redis::set('reset_password:' . $this->user->email, $otp);
        Redis::expire('reset_password:', 3600);

//        $url = config('app.url') . "/auth/reset?token=$token" . "&email=" . $this->user->email;

        return $this
            ->markdown('emails.users.forgot_password', [
                'otp' => $otp
            ])
            ->subject('Reset Password Notification');
    }
}
