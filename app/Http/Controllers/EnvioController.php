<?php

namespace App\Http\Controllers;

use App\Models\Bitacora;
use App\Models\Cotizacion;
use App\Models\Envio;
use App\Models\ZonaEnvio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

/**
 * Envíos por cotización. Solo se puede configurar cuando la cotización esté "pagada".
 * Admin y el vendedor dueño pueden crear/editar; marcar entregado es solo admin.
 */
class EnvioController extends Controller
{
    private function autorizarCotizacion(Cotizacion $cotizacion): void
    {
        $u = Auth::user();
        if (! $u->hasRole('admin') && $cotizacion->user_id !== $u->id) {
            abort(403, 'No tienes acceso a esta cotización.');
        }
    }

    /** Crea (o reemplaza) el envío de una cotización pagada. */
    public function store(Request $request, Cotizacion $cotizacion)
    {
        $this->autorizarCotizacion($cotizacion);
        abort_if($cotizacion->estado !== 'pagada', 422, 'Solo se puede configurar envío para cotizaciones pagadas.');

        $data = $this->validated($request);
        $data['cotizacion_id'] = $cotizacion->id;
        $data['costo'] = $this->calcularCosto($data);
        $data['flete_asumido_sweetgo'] = $request->boolean('flete_asumido_sweetgo');

        // hasOne + unique DB → si ya existe, actualizamos; si no, creamos.
        $envio = $cotizacion->envio()->firstOrNew([]);

        // Guía como archivo (foto o PDF): sube al disco público y reemplaza la anterior si aplica.
        if ($request->hasFile('guia_archivo')) {
            if ($envio->guia_archivo) {
                Storage::disk('public')->delete($envio->guia_archivo);
            }
            $data['guia_archivo'] = $request->file('guia_archivo')->store('envios/guias', 'public');
        }

        $envio->fill($data);
        $envio->save();

        Bitacora::registrar('creó', "Configuró envío para {$cotizacion->numero}", 'Envio', $envio->id);

        return redirect()->route('cotizaciones.show', $cotizacion)
            ->with('success', 'Envío configurado.');
    }

    /** Cambia estado del envío + permite subir/reemplazar la guía (solo admin). */
    public function estado(Request $request, Cotizacion $cotizacion, Envio $envio)
    {
        abort_unless(Auth::user()->hasRole('admin'), 403);
        abort_if($envio->cotizacion_id !== $cotizacion->id, 404);

        $data = $request->validate([
            'estado' => ['required', 'in:'.implode(',', Envio::ESTADOS)],
            'transportador' => ['nullable', 'string', 'max:120'],
            'guia_numero' => ['nullable', 'string', 'max:60'],
            'guia_archivo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:8192'],
        ]);

        // Si suben nueva guía, reemplaza la anterior en el disco público.
        if ($request->hasFile('guia_archivo')) {
            if ($envio->guia_archivo) {
                Storage::disk('public')->delete($envio->guia_archivo);
            }
            $data['guia_archivo'] = $request->file('guia_archivo')->store('envios/guias', 'public');
        }

        // Solo setea entregado_at cuando pasa a "entregado" (o lo limpia si vuelve a otro estado).
        $data['entregado_at'] = $data['estado'] === 'entregado' ? now() : null;
        $envio->update($data);

        Bitacora::registrar('actualizó', "Envío de {$cotizacion->numero} → {$envio->estadoLabel()}", 'Envio', $envio->id);

        return back()->with('success', 'Estado del envío actualizado.');
    }

    private function calcularCosto(array $data): float
    {
        // Si envían un costo manual, respetamos ese valor. Si no, calculamos por zona + peso.
        if (isset($data['costo']) && $data['costo'] !== null && $data['costo'] !== '') {
            return (float) $data['costo'];
        }
        if (! empty($data['zona_envio_id']) && isset($data['peso_kg'])) {
            $zona = ZonaEnvio::find($data['zona_envio_id']);
            $c = $zona?->calcularCosto((float) $data['peso_kg']);
            return $c ?? 0.0;
        }
        return 0.0;
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'zona_envio_id' => ['nullable', 'exists:zonas_envio,id'],
            'cliente_sucursal_id' => ['nullable', 'exists:cliente_sucursales,id'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'ciudad' => ['nullable', 'string', 'max:100'],
            'contacto' => ['nullable', 'string', 'max:120'],
            'telefono' => ['nullable', 'string', 'max:50'],
            'peso_kg' => ['nullable', 'numeric', 'min:0'],
            'costo' => ['nullable', 'numeric', 'min:0'],
            'transportador' => ['nullable', 'string', 'max:120'],
            'guia_numero' => ['nullable', 'string', 'max:60'],
            'guia_archivo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:8192'],
            'fecha_estimada' => ['nullable', 'date'],
            'notas' => ['nullable', 'string'],
        ]);
    }
}
