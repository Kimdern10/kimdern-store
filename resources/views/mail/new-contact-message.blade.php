<x-mail::message>
# {{ $contactMessage->subjectLabel() }}

**From:** {{ $contactMessage->name }} ({{ $contactMessage->email }}){{ $contactMessage->phone ? ', '.$contactMessage->phone : '' }}
@if ($contactMessage->order_number)
<br>**Order:** {{ $contactMessage->order_number }}
@endif

<x-mail::panel>
{{ $contactMessage->message }}
</x-mail::panel>

Reply to this email to answer {{ \Illuminate\Support\Str::of($contactMessage->name)->before(' ') }} directly.

<x-mail::button :url="route('admin.messages')">
Open messages
</x-mail::button>
</x-mail::message>
