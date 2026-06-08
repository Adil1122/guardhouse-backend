<x-mail::message>
# Welcome!

Your account has been created successfully.

Please click the button below to activate your account and set your password.

<x-mail::button :url="$url">
Activate Account
</x-mail::button>

If you did not request this, you can ignore this email.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
