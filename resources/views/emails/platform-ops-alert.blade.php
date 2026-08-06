<x-mail::message>
# {{ $headline }}

**Type:** {{ $alertType }}

@foreach ($context as $key => $value)
@if ($key !== 'url' && ! is_array($value))
- **{{ ucfirst(str_replace('_', ' ', $key)) }}:** {{ $value }}
@endif
@endforeach

<x-mail::button :url="$url">
Open Super Admin
</x-mail::button>
</x-mail::message>
