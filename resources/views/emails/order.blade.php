**Adresse**:

{{$order->street}} {{$order->streetNumber}} <br>
{{$order->zip}} {{$order->city}}

**Telefonnummer**: {{$order->phone}}

@component('mail::table')
| Artikel | Anzahl |
| -- |--:|
@foreach($order->orderItems as $orderItem)
| {{$orderItem->product->name}} | {{$orderItem->quantity}} |
@endforeach
@endcomponent

**Gesamtpreis**: {{$order->formattedPrice}} €
---

@if($order->commentary != null)
**Kommentar**:

{!! nl2br(e($order->commentary), false) !!}
@else
**Kommentar**: *Kein Kommentar*
@endif