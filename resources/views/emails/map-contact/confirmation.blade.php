@component('mail::message')
# Bitte bestätige deine Kontaktanfrage

Du möchtest über die Karte von **{{ $initiative }}** Kontakt zum Eintrag „{{ app(\App\Services\PublicMapPointPresenter::class)->title($mapPoint) }}“ aufnehmen.
Erst wenn du deine E-Mail-Adresse bestätigst, leiten wir deine Nachricht weiter.

@component('mail::button', ['url' => $confirmationUrl])
Kontaktanfrage bestätigen
@endcomponent

Der Link ist gültig bis {{ $expiresAt->timezone(config('app.timezone'))->format('d.m.Y, H:i') }} Uhr.

Falls du diese Anfrage nicht gestellt hast, kannst du diese E-Mail ignorieren. Dann wird nichts weitergeleitet.
@endcomponent
