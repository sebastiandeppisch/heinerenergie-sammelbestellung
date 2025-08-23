@component('mail::message')
# Hallo {{$advice->first_name}}
Wir haben Deinen Beratungswunsch erhalten und werden uns bald bei Dir melden.

**Name**: {{$advice->first_name}} {{$advice->last_name}}

**Kontakt**: {{$advice->email}} / {{$advice->phone}}

**Adresse**: {{$advice->street}} {{$advice->street_number}}, {{$advice->zip}} {{$advice->city}}

**Beratungswunsch**: @if($advice->isHome())Bei Dir Zuhause @elseif($advice->isVirtual()) Virtuell @else Nur Teilnahme an der Sammelbestellung @endif


**Hinweis**:
{!! $adviceInfo !!}

@endcomponent