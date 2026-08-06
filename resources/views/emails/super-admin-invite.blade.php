<x-mail::message>
# You're invited to Ledrix Super Admin

Hi {{ $name }},

You have been invited as **{{ $role }}** on the Ledrix platform control panel.

<x-mail::button :url="$url">
Accept invite
</x-mail::button>

This link expires in 48 hours. If you did not expect this email, you can ignore it.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
