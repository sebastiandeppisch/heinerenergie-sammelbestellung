@component('mail::message')
# Hallo,

dein Eintrag „{{ $mapPoint->title }}“ ist angelegt. Auf der Karte erscheint er erst, wenn wir ihn freigegeben haben, und nur mit den Angaben, die du erlaubt hast.

Über den folgenden Link kannst du ändern, was öffentlich angezeigt wird, deine Einwilligung widerrufen oder den Eintrag löschen:
@if ($mapPoint->contact_enabled)

Du möchtest über die Karte kontaktiert werden. Damit Anfragen bei dir ankommen, schalte sie auf der Seite einmal mit „Kontaktanfragen einschalten“ frei. So wissen wir, dass die Adresse dir gehört.
@endif

@component('mail::button', ['url' => $manageUrl])
Eintrag verwalten
@endcomponent

Der Link ist bis zum {{ $mapPoint->edit_token_expires_at?->timezone(config('app.timezone'))->format('d.m.Y') }} gültig. Danach kannst du jederzeit einen neuen anfordern: {{ route('map-point.manage.request') }}

Viele Grüße,<br>
das {{ app_name() }} Team
@endcomponent
