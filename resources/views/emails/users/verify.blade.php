@component('mail::message')
# Hello, {{$user->name}}

You are receiving this email because we received a email verification request for your account.

Your one time password to verify your email is:
# {{$otp}}

This otp will expire in 30 minutes.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
