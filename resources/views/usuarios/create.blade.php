@extends('layouts.admin')

@section('title', 'Nuevo usuario')

@section('content')
    <div class="mb-6">
        <a href="{{ route('usuarios.index') }}" class="text-sm text-sweetgo-turquoise hover:underline">← Volver a usuarios</a>
        <h2 class="text-xl font-semibold text-gray-800 mt-1">Nuevo usuario</h2>
    </div>

    <form method="POST" action="{{ route('usuarios.store') }}">
        @csrf
        @include('usuarios._form')
    </form>
@endsection
