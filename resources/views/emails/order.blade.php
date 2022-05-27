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

**Gesamtpreis**: {{(new \NumberFormatter( 'de_DE', NumberFormatter::CURRENCY ))->formatCurrency($order->price, 'EUR')}}