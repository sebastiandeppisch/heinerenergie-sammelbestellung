@component('mail::message')
# Hallo {{$order->name}}
Du erhälst nochmal zur Kontrolle Deine Bestellung aufgeführt. 
Grund für die erneue Versendung der E-Mail: 

*{{$reason}}*

@include('emails.order')

@endcomponent