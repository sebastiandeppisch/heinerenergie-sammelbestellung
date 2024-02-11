@component('mail::message')
# Hallo {{$advice->firstName}}
Wir haben Deinen Beratungswunsch erhalten und werden uns bald bei Dir melden.

**Name**: {{$advice->firstName}} {{$advice->lastName}}

**Kontakt**: {{$advice->email}} / {{$advice->phone}}

**Adresse**: {{$advice->street}} {{$advice->streetNumber}}, {{$advice->zip}} {{$advice->city}}

**Beratungswunsch**: @if($advice->type == 0)Bei Dir Zuhause @elseif($advice->type == 1) Virtuell @else Nur Teilnahme an der Sammelbestellung @endif

@endcomponent