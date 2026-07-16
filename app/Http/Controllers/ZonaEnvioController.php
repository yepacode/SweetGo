<?php

namespace App\Http\Controllers;

use App\Models\Bitacora;
use App\Models\ZonaEnvio;
use Illuminate\Http\Request;

/**
 * CRUD de zonas de envío (solo admin). Reglas de tarifa por zona:
 *   costo_base + max(0, peso - peso_base_kg) * costo_kg_adicional
 * peso_maximo_kg opcional (null = sin límite).
 */
class ZonaEnvioController extends Controller
{
    public function index()
    {
        $zonas = ZonaEnvio::orderByDesc('activo')->orderBy('nombre')->get();

        return view('zonas-envio.index', compact('zonas'));
    }

    public function store(Request $request)
    {
        $zona = ZonaEnvio::create($this->validated($request));
        Bitacora::registrar('creó', "Zona de envío «{$zona->nombre}» creada", 'ZonaEnvio', $zona->id);

        return back()->with('success', 'Zona de envío creada.');
    }

    public function update(Request $request, ZonaEnvio $zonaEnvio)
    {
        $antes = $zonaEnvio->only(['nombre', 'costo_base', 'costo_kg_adicional', 'peso_base_kg', 'peso_maximo_kg']);
        $zonaEnvio->update($this->validated($request));
        Bitacora::registrar('actualizó', "Zona de envío «{$zonaEnvio->nombre}» actualizada", 'ZonaEnvio', $zonaEnvio->id, [
            'antes' => $antes,
            'despues' => $zonaEnvio->only(['nombre', 'costo_base', 'costo_kg_adicional', 'peso_base_kg', 'peso_maximo_kg']),
        ]);

        return back()->with('success', "Zona «{$zonaEnvio->nombre}» actualizada.");
    }

    public function toggle(ZonaEnvio $zonaEnvio)
    {
        $zonaEnvio->update(['activo' => ! $zonaEnvio->activo]);
        Bitacora::registrar('actualizó', "Zona «{$zonaEnvio->nombre}» " . ($zonaEnvio->activo ? 'activada' : 'desactivada'), 'ZonaEnvio', $zonaEnvio->id);

        return back()->with('success', $zonaEnvio->activo ? 'Zona activada.' : 'Zona desactivada.');
    }

    public function destroy(ZonaEnvio $zonaEnvio)
    {
        if ($zonaEnvio->envios()->exists()) {
            return back()->with('error', 'No se puede eliminar una zona con envíos asociados. Desactívala.');
        }
        $nombre = $zonaEnvio->nombre;
        $id = $zonaEnvio->id;
        $zonaEnvio->delete();
        Bitacora::registrar('eliminó', "Zona de envío «{$nombre}» eliminada", 'ZonaEnvio', $id);

        return back()->with('success', "Zona «{$nombre}» eliminada.");
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'nombre' => ['required', 'string', 'max:120'],
            'costo_base' => ['required', 'numeric', 'min:0'],
            'costo_kg_adicional' => ['required', 'numeric', 'min:0'],
            'peso_base_kg' => ['required', 'numeric', 'min:0.001'],
            'peso_maximo_kg' => ['nullable', 'numeric', 'min:0.001', 'gte:peso_base_kg'],
            'notas' => ['nullable', 'string'],
            'activo' => ['nullable', 'boolean'],
        ], [], [
            'peso_base_kg' => 'peso base',
            'peso_maximo_kg' => 'peso máximo',
            'costo_kg_adicional' => 'costo por kg adicional',
        ]);
    }
}
