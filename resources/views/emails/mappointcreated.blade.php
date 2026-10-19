@component('mail::message')
# Hallo,

dein Eintrag „{{ $mapPoint->title }}“ ist angelegt. Auf der Karte erscheint er erst, wenn wir ihn freigegeben haben, und nur mit den Angaben, die du erlaubt hast.

Über den folgenden Link kannst du ändern, was öffentlich angezeigt wird, deine Einwilligung widerrufen oder den Eintrag löschen:

@component('mail::button', ['url' => $manageUrl])
Eintrag verwalten
@endcomponent

Der Link ist bis zum {{ $mapPoint->edit_token_expires_at?->timezone(config('app.timezone'))->format('d.m.Y') }} gültig. Danach kannst du jederzeit einen neuen anfordern: {{ route('map-point.manage.request') }}

Viele Grüße,<br>
das {{ app_name() }} Team
@endcomponent
