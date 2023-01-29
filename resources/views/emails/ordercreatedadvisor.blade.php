@component('mail::message')
# Hallo {{$order->advisor->name}}
{{$order->name}} hat eine Bestellung eingetragen.

## Daten der Bestellung: 

@include('emails.order')

@endcomponent