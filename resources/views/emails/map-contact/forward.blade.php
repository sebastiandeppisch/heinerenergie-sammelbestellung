@component('mail::message')
# Neue Kontaktanfrage

Über die Karte von **{{ $initiative }}** hat dir jemand zu deinem Eintrag „{{ $mapPoint->title }}“ geschrieben.

**Name:** {{ $senderName }}

**Nachricht:**

<div style="white-space: pre-wrap;">{!! str_replace(["\r\n", "\r", "\n"], '<br>', e($senderMessage)) !!}</div>

Antworte direkt auf diese Mail. Deine Adresse sieht die Person erst, wenn du antwortest.

---

Du möchtest keine Kontaktanfragen mehr erhalten? Unter {{ $unsubscribeUrl }} kannst du sie abschalten.
@endcomponent
