@component('mail::message')
# Hallo,

vielen Dank für deine Einsendung „{{ $submission->form_name }}“. Bitte bestätige noch deine E-Mail-Adresse, damit wir deine Angaben bearbeiten können.

@component('mail::button', ['url' => $confirmationUrl])
E-Mail-Adresse bestätigen
@endcomponent

Der Link ist bis zum {{ $submission->confirmation_expires_at?->timezone(config('app.timezone'))->format('d.m.Y') }} gültig.

Falls du das Formular nicht ausgefüllt hast, kannst du diese E-Mail einfach ignorieren.

Viele Grüße,<br>
das {{ app_name() }} Team
@endcomponent
