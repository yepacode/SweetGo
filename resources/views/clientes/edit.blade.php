@extends('layouts.admin')

@section('title', 'Editar cliente')

@section('content')
    <div class="mb-6">
        <a href="{{ route('clientes.show', $cliente) }}" class="text-sm text-sweetgo-turquoise hover:underline">← Volver al cliente</a>
        <h2 class="text-xl font-semibold text-gray-800 mt-1">Editar: {{ $cliente->nombre }}</h2>
    </div>

    <form method="POST" action="{{ route('clientes.update', $cliente) }}">
        @csrf @method('PUT')
        @include('clientes._form')
    </form>
@endsection
