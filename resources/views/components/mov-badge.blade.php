@props(['tipo'])

@php
    $map = [
        'entrada' => ['Entrada', 'bg-green-100 text-green-700'],
        'salida'  => ['Salida',  'bg-red-100 text-red-600'],
        'ajuste'  => ['Ajuste',  'bg-amber-100 text-amber-700'],
    ];
    [$label, $classes] = $map[$tipo] ?? [$tipo, 'bg-gray-100 text-gray-600'];
@endphp

<span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium {{ $classes }}">{{ $label }}</span>
