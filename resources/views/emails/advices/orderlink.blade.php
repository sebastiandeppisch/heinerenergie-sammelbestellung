@component('mail::message')
# Hallo {{$advice->name}}
Du kannst unter folgendem Link an der Sammelbestellung teilnehmen: 

@component('mail::button', ['url' => $url])
Bestellformular öffnen
@endcomponent

Das Passwort ist: **{{$password}}**

@endcomponent