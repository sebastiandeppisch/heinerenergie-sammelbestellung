@component('mail::message')
# Bitte bestätige deine Kontaktadresse

Für deinen Eintrag „{{ $mapPoint->title }}“ auf der Karte von **{{ $initiative }}** wurde diese E-Mail-Adresse als Kontakt hinterlegt.
Besucher:innen der Karte können dir dann über ein Formular schreiben. Deine Adresse wird dabei nie angezeigt.

@component('mail::button', ['url' => $verificationUrl])
Kontaktadresse bestätigen
@endcomponent

Solange du nicht bestätigst, erreichen dich keine Kontaktanfragen. Falls du das nicht warst, kannst du diese E-Mail ignorieren.
@endcomponent
