@component('mail::layout')
{{-- Header --}}
@slot('header')
@component('mail::header', ['url' => group_url()])
{{ config('app.name') }}
@endcomponent
@endslot

{{-- Body --}}
{{ $slot }}

{{-- Subcopy --}}
@isset($subcopy)
@slot('subcopy')
@component('mail::subcopy')
{{ $subcopy }}
@endcomponent
@endslot
@endisset

{{-- Footer --}}
@slot('footer')
@component('mail::footer')
{{ group_url() }}
@endcomponent
@endslot
@endcomponent
