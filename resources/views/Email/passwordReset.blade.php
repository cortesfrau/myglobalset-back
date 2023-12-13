<x-mail::message>
# Change password request

Click on the button below to change your password.

<x-mail::button :url="'http://localhost:4200/response-password-reset?token='.$token.'&email='.$email">
Reset Password
</x-mail::button>

{{-- <x-mail::button :url="'http://localhost:4200/response-password-reset?token=asdfsfniwuergniueg&email=email@email.com'">
Reset Password
</x-mail::button> --}}


Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
