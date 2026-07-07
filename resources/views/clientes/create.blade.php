@extends('layouts.admin')

@section('title', 'Nuevo cliente')

@section('content')
    <div class="mb-6">
        <a href="{{ route('clientes.index') }}" class="text-sm text-sweetgo-turquoise hover:underline">← Volver a clientes</a>
        <h2 class="text-xl font-semibold text-gray-800 mt-1">Nuevo cliente</h2>
    </div>

    <form method="POST" action="{{ route('clientes.store') }}">
        @csrf
        @include('clientes._form')
    </form>
@endsection
