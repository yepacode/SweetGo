@extends('layouts.admin')

@section('title', 'Nueva cotización')

@section('content')
    <div class="mb-6">
        <a href="{{ route('cotizaciones.index') }}" class="text-sm text-sweetgo-turquoise hover:underline">← Volver a cotizaciones</a>
        <h2 class="text-xl font-semibold text-gray-800 mt-1">Nueva cotización</h2>
    </div>

    <form method="POST" action="{{ route('cotizaciones.store') }}">
        @csrf
        @include('cotizaciones._form')
    </form>
@endsection
