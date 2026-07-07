@extends('layouts.admin')

@section('title', 'Nuevo producto')

@section('content')
    <div class="mb-6">
        <a href="{{ route('productos.index') }}" class="text-sm text-sweetgo-turquoise hover:underline">← Volver a productos</a>
        <h2 class="text-xl font-semibold text-gray-800 mt-1">Nuevo producto</h2>
    </div>

    <form method="POST" action="{{ route('productos.store') }}" enctype="multipart/form-data">
        @csrf
        @include('productos._form')
    </form>
@endsection
