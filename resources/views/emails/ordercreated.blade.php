@component('mail::message')
# Hallo {{$order->firstName}} {{$order->lastName}}
Wir haben Deine Bestellung erhalten und melden uns bei Dir, sobald wir sie an unseren Lieferanten weiter geleitet haben. 
Bei Fragen kannst Du auf diese E-Mail antworten, oder Dich an Deine\*n Berater\*in wenden.

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


@endcomponent
@endcomponent