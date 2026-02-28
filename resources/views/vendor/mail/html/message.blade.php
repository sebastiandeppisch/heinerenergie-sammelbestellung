@component('mail::layout')
{{-- Header --}}
@slot('header')
@component('mail::header', ['url' => app_url()])
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
{{ app_url() }}
@endcomponent
@endslot
@endcomponent
