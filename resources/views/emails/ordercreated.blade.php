@component('mail::message')
# Hallo {{$order->name}}
Wir haben Deine Bestellung erhalten und melden uns bei Dir, sobald wir sie an unseren Lieferanten weiter geleitet haben. 
Bei Fragen kannst Du auf diese E-Mail antworten, oder Dich an Deine\*n Berater\*in {{$order->advisor->name}} wenden.

@include('emails.order')

@endcomponent
@endcomponent