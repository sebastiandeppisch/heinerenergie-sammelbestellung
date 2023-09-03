@component('mail::message')
# Hallo {{$order->name}}
Wir haben Deinen Bestellwunsch erhalten und melden uns bei Dir, sobald wir sie an unseren Lieferanten weiter geleitet haben. 
Bei Fragen kannst Du auf diese E-Mail antworten, oder Dich an Deine\*n Berater\*in {{$order->advisor->name}} wenden.

Zur Kontrolle ist Dein Bestellwunsch nochmal aufgeführt:

@include('emails.order')

@endcomponent