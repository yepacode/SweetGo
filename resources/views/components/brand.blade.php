@props(['class' => 'h-8'])

{{-- Logotipo oficial Sweet Go (imagen en public/img/sweetgo-logo.png) --}}
<img src="{{ asset('img/sweetgo-logo.png') }}"
     alt="Sweet Go — Beauty Experts"
     {{ $attributes->merge(['class' => 'w-auto select-none '.$class]) }}>
