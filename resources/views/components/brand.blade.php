@props(['class' => ''])

{{-- Logotipo tipográfico Sweet·Go (reemplazable por el PNG/SVG oficial en public/img/logo.png) --}}
<span {{ $attributes->merge(['class' => 'inline-flex items-baseline font-serif tracking-tight select-none '.$class]) }}>
    <span class="text-sweetgo-pink font-bold">Sweet</span>
    <span class="mx-1 text-sweetgo-turquoise">&#10022;</span>
    <span class="text-sweetgo-pink font-bold">Go</span>
</span>
