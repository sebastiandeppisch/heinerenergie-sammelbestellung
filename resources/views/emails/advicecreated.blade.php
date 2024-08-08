@component('mail::message')
# Hallo {{$advice->firstName}}
Wir haben Deinen Beratungswunsch erhalten und werden uns bald bei Dir melden.

**Name**: {{$advice->firstName}} {{$advice->lastName}}

**Kontakt**: {{$advice->email}} / {{$advice->phone}}

**Adresse**: {{$advice->street}} {{$advice->streetNumber}}, {{$advice->zip}} {{$advice->city}}

**Beratungswunsch**: @if($advice->isHome())Bei Dir Zuhause @elseif($advice->isVirtual()) Virtuell @else Nur Teilnahme an der Sammelbestellung @endif


**Hinweis**:
{!! $adviceInfo !!}

@endcomponent