@extends('layouts.admin')

@section('title', 'Editar cotización')

@section('content')
    <div class="mb-6">
        <a href="{{ route('cotizaciones.show', $cotizacion) }}" class="text-sm text-sweetgo-turquoise hover:underline">← Volver a la cotización</a>
        <h2 class="text-xl font-semibold text-gray-800 mt-1">Editar {{ $cotizacion->numero }}</h2>
    </div>

    <form method="POST" action="{{ route('cotizaciones.update', $cotizacion) }}">
        @csrf @method('PUT')
        @include('cotizaciones._form')
    </form>
@endsection
