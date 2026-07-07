@extends('layouts.admin')

@section('title', 'Garantías')

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div>
            <h2 class="text-xl font-semibold text-gray-800">Garantías</h2>
            <p class="text-sm text-gray-500">Flujo: Recibido → En gestión → Resuelto → Cerrado</p>
        </div>
        <a href="{{ route('garantias.create') }}"
           class="px-4 py-2 rounded-lg bg-sweetgo-pink text-white text-sm font-medium shadow-sm hover:opacity-90 transition">
            + Registrar garantía
        </a>
    </div>

    {{-- Conteos por estado --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
        @foreach (\App\Models\Garantia::ESTADOS as $key => $label)
            <a href="{{ route('garantias.index', ['estado' => $key]) }}"
               class="bg-white rounded-xl border p-4 transition hover:shadow-sm {{ request('estado') === $key ? 'border-sweetgo-pink' : 'border-sweetgo-pink-light' }}">
                <p class="text-sm text-gray-500">{{ $label }}</p>
                <p class="mt-1 text-2xl font-bold text-gray-800">{{ $conteos[$key] }}</p>
            </a>
        @endforeach
    </div>

    <form method="GET" class="flex flex-wrap gap-3 mb-5">
        <input type="text" name="buscar" value="{{ request('buscar') }}" placeholder="Buscar número o cliente…"
               class="rounded-lg border-gray-200 text-sm focus:border-sweetgo-pink focus:ring-sweetgo-pink">
        @if (request('estado'))<input type="hidden" name="estado" value="{{ request('estado') }}">@endif
        <button class="px-4 py-2 rounded-lg bg-gray-800 text-white text-sm hover:bg-gray-700">Buscar</button>
        @if (request('estado') || request('buscar'))
            <a href="{{ route('garantias.index') }}" class="px-4 py-2 rounded-lg border border-gray-200 text-sm text-gray-500 hover:bg-gray-50">Limpiar</a>
        @endif
    </form>

    <div class="bg-white rounded-xl border border-sweetgo-pink-light overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-sweetgo-pink-light/60 text-gray-600 text-left">
                    <tr>
                        <th class="px-4 py-3 font-medium">Número</th>
                        <th class="px-4 py-3 font-medium">Cliente</th>
                        <th class="px-4 py-3 font-medium">Producto</th>
                        <th class="px-4 py-3 font-medium">Recibido</th>
                        <th class="px-4 py-3 font-medium text-center">Estado</th>
                        <th class="px-4 py-3 font-medium text-right"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($garantias as $g)
                        <tr class="hover:bg-sweetgo-pink-light/30">
                            <td class="px-4 py-3"><a href="{{ route('garantias.show', $g) }}" class="font-medium text-sweetgo-pink hover:underline">{{ $g->numero }}</a></td>
                            <td class="px-4 py-3 text-gray-700">{{ $g->cliente?->nombre ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $g->producto_display }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $g->fecha_recibido->format('d/m/Y') }}</td>
                            <td class="px-4 py-3 text-center"><span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium {{ $g->estadoBadge() }}">{{ $g->estado_label }}</span></td>
                            <td class="px-4 py-3 text-right"><a href="{{ route('garantias.show', $g) }}" class="text-xs text-sweetgo-turquoise hover:underline">Ver</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-10 text-center text-gray-400">No hay garantías registradas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $garantias->links() }}</div>
@endsection
