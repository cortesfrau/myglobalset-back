<x-mail::message>
    # Reset your password

    Click on the button below to change your password.

    <x-mail::button :url="config('app.frontend_url').'/response-password-reset?token='.$token.'&email='.$email">
        Reset Password
    </x-mail::button>

    Thanks,<br>
    {{ config('app.name') }}
</x-mail::message>
