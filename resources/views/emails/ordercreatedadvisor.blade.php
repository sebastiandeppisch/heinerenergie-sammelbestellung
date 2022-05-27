@component('mail::message')
# Hallo {{$order->advisor->name}}
{{$order->name}} hat eine Bestellung eingetragen: 

@include('emails.order')

@endcomponent
@endcomponent