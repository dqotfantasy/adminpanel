@component('mail::message')
# Hello, {{$user->name}}

Welcome to {{config('app.name')}} - where every ball, every run & every wickets matters!

Click the button below to instantly verify your email id.
@component('mail::button', ['url' => $url])
Verify email
@endcomponent

This verification link will expire in 24 hours.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
