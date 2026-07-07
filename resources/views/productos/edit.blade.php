@extends('layouts.admin')

@section('title', 'Editar producto')

@section('content')
    <div class="mb-6">
        <a href="{{ route('productos.index') }}" class="text-sm text-sweetgo-turquoise hover:underline">← Volver a productos</a>
        <h2 class="text-xl font-semibold text-gray-800 mt-1">Editar: {{ $producto->nombre }}</h2>
    </div>

    <form method="POST" action="{{ route('productos.update', $producto) }}" enctype="multipart/form-data">
        @csrf @method('PUT')
        @include('productos._form')
    </form>
@endsection
