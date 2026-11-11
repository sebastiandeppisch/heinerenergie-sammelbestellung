@component('mail::message')
# Neuer Trittstein

In der Kampagne „{{ $point->campaign }}“ wurde ein Trittstein angemeldet.

@component('mail::panel')
**{{ $point->title }}**@if ($point->category && $point->category !== $point->title) ({{ $point->category }})@endif<br>
Maßnahmen: {{ $point->measures === [] ? 'keine' : implode(', ', $point->measures) }}<br>
Einwilligung: {{ $point->consent }}<br>
{{ $point->published ? 'Ist schon auf der Karte zu sehen.' : 'Wartet auf Freigabe.' }}
@endcomponent

@foreach ($actions as $label => $url)
@component('mail::button', ['url' => $url, 'color' => $loop->last ? 'error' : 'primary'])
{{ $label }}
@endcomponent
@endforeach

Die Buttons ändern noch nichts, du bestätigst auf der nächsten Seite. Sie gelten {{ \App\HexNet\Services\HexNetModeration::LINK_DAYS }} Tage. Zurückgezogene Trittsteine zählen weiter, sind aber nicht mehr auf der Karte zu sehen.

[Trittstein im Backend öffnen]({{ $point->backend_url }})

Viele Grüße,<br>
{{ app_name() }}
@endcomponent
