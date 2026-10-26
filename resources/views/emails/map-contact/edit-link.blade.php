@component('mail::message')
# Kontaktanfragen verwalten

Über den folgenden Link kannst du Kontaktanfragen zu deinem Eintrag „{{ $mapPoint->title }}“ auf der Karte von **{{ $initiative }}** abschalten oder wieder einschalten.

@component('mail::button', ['url' => $manageUrl])
Kontaktanfragen verwalten
@endcomponent

Der Link ist {{ config('map_contact.edit_token_expires_days') }} Tage gültig. Ein neu angeforderter Link macht diesen ungültig.

Falls du den Link nicht angefordert hast, kannst du diese E-Mail ignorieren.
@endcomponent
