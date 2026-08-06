<x-mail::message>
# Support update #{{ $ticket->id }}

{{ $message }}

<x-mail::button :url="$url">
View ticket
</x-mail::button>

Thanks,<br>
{{ config('app.name') }} Support
</x-mail::message>
