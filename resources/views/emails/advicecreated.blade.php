@component('mail::message')
# Hallo {{$advice->firstName}}
Wir haben Deinen Beratungswunsch erhalten und werden uns bald bei Dir melden.

**Name**: {{$advice->firstName}} {{$advice->lastName}}

**Kontakt**: {{$advice->email}} / {{$advice->phone}}

**Adresse**: {{$advice->street}} {{$advice->streetNumber}}, {{$advice->zip}} {{$advice->city}}

**Beratungswunsch**: @if($advice->type == \App\AdviceType::Home)Bei Dir Zuhause @elseif($advice->type == \App\AdviceType::Virtual) Virtuell @else Nur Teilnahme an der Sammelbestellung @endif

@endcomponent