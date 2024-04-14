@component('mail::message')
# Hallo {{$advice->firstName}}
Wir haben Deinen Beratungswunsch erhalten und werden uns bald bei Dir melden.

**Name**: {{$advice->firstName}} {{$advice->lastName}}

**Kontakt**: {{$advice->email}} / {{$advice->phone}}

**Adresse**: {{$advice->street}} {{$advice->streetNumber}}, {{$advice->zip}} {{$advice->city}}

**Beratungswunsch**: @if($advice->type == \App\AdviceType::Home)Bei Dir Zuhause @elseif($advice->type == \App\AdviceType::Virtual) Virtuell @else Nur Teilnahme an der Sammelbestellung @endif

**Hinweis**: Wir melden uns zwischen Anfang April und Anfang Mai bei dir für ein Beratungsgespräch. Wir führen dieses Jahr unsere Beratungen in festgelegten Zeiträumen durch. Am Ende jedes Zeitraums werden wir Sammelbestellungen durchführen. Die Bestellfrist für die nächste Sammelbestellung ist am 5. Mai 2024. Die Ausgabe für die bestellten Module (und Halterungen) wird voraussichtlich am 11. oder 18. Mai 2024 stattfinden.

@endcomponent