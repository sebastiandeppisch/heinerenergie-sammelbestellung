@component('mail::message')
# Hallo,

über diese Links kannst du deine Einträge auf der Karte verwalten: ändern, was öffentlich angezeigt wird, deine Einwilligung widerrufen oder den Eintrag löschen.

@foreach ($links as $link)
- [{{ $link['title'] }}]({{ $link['url'] }})
@endforeach

Die Links sind {{ config('forms.map_point_edit.expires_days') }} Tage gültig. Ältere Links funktionieren nicht mehr.

Falls du keinen Link angefordert hast, kannst du diese E-Mail einfach ignorieren.

Viele Grüße,<br>
das {{ app_name() }} Team
@endcomponent
