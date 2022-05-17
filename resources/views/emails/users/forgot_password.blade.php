{{--@component('mail::message')--}}
{{--    # Hello, {{$user->name}}--}}

{{--    You are receiving this email because we received a password reset request for your account.--}}

{{--    This password reset link will expire in 60 minutes.--}}

{{--    @component('mail::button', ['url' => $url])--}}
{{--        Reset Password--}}
{{--    @endcomponent--}}

{{--    If you did not request a password reset, no further action is required.--}}

{{--    Thanks,<br>--}}
{{--    {{ config('app.name') }}--}}
{{--@endcomponent--}}

@component('mail::message')
# Hello, {{$user->name}}

You are receiving this email because we received a email verification request for your account.

You one time password to verify your email is:
# {{$otp}}

This otp will expire in 30 minutes.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
