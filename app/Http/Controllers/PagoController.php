<?php

namespace App\Http\Controllers;

use App\Models\Bitacora;
use App\Models\Cotizacion;
use App\Models\Notificacion;
use App\Models\Pago;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PagoController extends Controller
{
    /** Solo el dueño de la cotización (o admin) puede tocarla. */
    private function autorizarCotizacion(Cotizacion $cotizacion): void
    {
        $u = Auth::user();
        if (! $u->hasRole('admin') && $cotizacion->user_id !== $u->id) {
            abort(403, 'No tienes acceso a esta cotización.');
        }
    }

    /** Vendedor registra un pago sobre una cotización enviada/borrador/a crédito. */
    public function store(Request $request, Cotizacion $cotizacion)
    {
        $this->autorizarCotizacion($cotizacion);

        abort_if(in_array($cotizacion->estado, ['pagada', 'rechazada'], true), 422,
            'No se pueden registrar pagos sobre una cotización pagada o rechazada.');

        $data = $request->validate([
            'metodo' => ['required', 'in:'.implode(',', Pago::METODOS)],
            'monto' => ['required', 'numeric', 'min:0.01'],
            'referencia' => ['nullable', 'string', 'max:100'],
            'notas' => ['nullable', 'string'],
            'dias_credito' => ['nullable', 'integer', 'min:1', 'max:365'],
            // mimetypes: sniff real del archivo (evita renombrar .php a .jpg)
            'comprobante' => ['nullable', 'file', 'mimetypes:image/jpeg,image/png,image/webp,application/pdf', 'max:8192'],
        ]);

        // Comprobante obligatorio para transferencia/tarjeta.
        if (in_array($data['metodo'], ['transferencia', 'tarjeta'], true) && ! $request->hasFile('comprobante')) {
            return back()->withErrors(['comprobante' => 'Debes adjuntar comprobante para transferencia o tarjeta.'])->withInput();
        }

        // Si es crédito, calcular fecha de vencimiento a partir de los días (default 30).
        $fechaVencimiento = null;
        if ($data['metodo'] === 'credito') {
            $dias = (int) ($data['dias_credito'] ?? 30);
            $fechaVencimiento = now()->addDays($dias)->toDateString();
        }

        // Comprobante en disco PRIVADO (local): la descarga siempre pasa por el controller con ownership.
        $comprobante = $request->hasFile('comprobante')
            ? $request->file('comprobante')->store("pagos/{$cotizacion->id}", 'local')
            : null;

        // Sección crítica: lock + check de sobreabono + create + transición de estado en una transacción.
        $pagoCreado = null;
        try {
            DB::transaction(function () use ($cotizacion, $data, $comprobante, $fechaVencimiento, &$pagoCreado) {
                $locked = Cotizacion::whereKey($cotizacion->id)->lockForUpdate()->firstOrFail();

                $pagadoActual = (float) $locked->montoPagado();
                $resta = (float) $locked->total - $pagadoActual;
                if ((float) $data['monto'] > $resta + 0.01) {
                    throw new \RuntimeException('El monto excede lo que resta por pagar ('.number_format($resta, 0, ',', '.').').');
                }

                $pagoCreado = Pago::create([
                    'cotizacion_id' => $locked->id,
                    'user_id' => Auth::id(),
                    'metodo' => $data['metodo'],
                    'monto' => $data['monto'],
                    'referencia' => $data['referencia'] ?? null,
                    'comprobante' => $comprobante,
                    'notas' => $data['notas'] ?? null,
                    'fecha_vencimiento' => $fechaVencimiento,
                    'estado' => 'pendiente',
                ]);

                $locked->refresh();
                if ($locked->estaTotalmentePagada() && $locked->estado !== 'pagada') {
                    $locked->update(['estado' => 'pendiente_revision_pago']);
                }
            });
        } catch (\RuntimeException $e) {
            // Limpieza: si guardamos comprobante y falló el pago, lo borramos.
            if ($comprobante) {
                Storage::disk('local')->delete($comprobante);
            }
            return back()->withErrors(['monto' => $e->getMessage()])->withInput();
        }

        Bitacora::registrar('creó', "Registró pago en {$cotizacion->numero}", 'Pago', $cotizacion->id);

        // Notificar a los admins del pago pendiente (siempre, aunque quien lo registre sea admin,
        // para que quede la alerta en la campanita hasta que alguien lo apruebe/rechace).
        if ($pagoCreado) {
            $quien = Auth::user()->name;
            $metodoLabel = $pagoCreado->metodoLabel();
            $monto = '$' . number_format((float) $pagoCreado->monto, 0, ',', '.');
            $venceLinea = $fechaVencimiento
                ? "\n📅 Vence el " . \Carbon\Carbon::parse($fechaVencimiento)->format('d/m/Y')
                : '';
            $refLinea = ! empty($data['referencia']) ? "\nRef: {$data['referencia']}" : '';

            Notificacion::alertarAdmins(
                'pago_pendiente',
                "{$cotizacion->numero} · pago pendiente de aprobación",
                "💳 {$quien} registró un pago:\n{$metodoLabel} por {$monto}{$refLinea}{$venceLinea}\n\nRevisa y aprueba/rechaza en la cotización.",
                route('cotizaciones.show', $cotizacion)
            );
        }

        $mensajeExito = $data['metodo'] === 'credito'
            ? 'Crédito registrado. Queda pendiente de aprobación por el administrador.'
            : 'Pago registrado. Queda pendiente de revisión por el administrador.';

        return redirect()->route('cotizaciones.show', $cotizacion)
            ->with('success', $mensajeExito);
    }

    /** Admin aprueba un pago específico. */
    public function aprobar(Cotizacion $cotizacion, Pago $pago)
    {
        abort_unless(Auth::user()->hasRole('admin'), 403);
        abort_if($pago->cotizacion_id !== $cotizacion->id, 404);

        // Sección crítica: bloqueamos cotización + pago juntos.
        DB::transaction(function () use ($cotizacion, $pago) {
            $lockedCot = Cotizacion::whereKey($cotizacion->id)->lockForUpdate()->firstOrFail();
            $lockedPago = Pago::whereKey($pago->id)->lockForUpdate()->firstOrFail();

            // Chequeo del estado DENTRO del lock (evita double-click / carreras entre admins).
            if ($lockedPago->estado === 'aprobado') {
                throw new \RuntimeException('Este pago ya estaba aprobado.');
            }

            $lockedPago->update([
                'estado' => 'aprobado',
                'aprobado_por_id' => Auth::id(),
                'aprobado_at' => now(),
                'rechazo_motivo' => null,
            ]);

            $lockedCot->refresh();

            // Regla: si con solo pagos NO-crédito (dinero real) ya cubre el total → estado "pagada".
            // Sino, si TODOS los aprobados (incluidos créditos) cubren → estado "credito" (deuda vigente,
            // pero mercancía se despacha). En ambos casos se descuenta stock una sola vez.
            $cubreEfectivo = $lockedCot->pagosNoCreditoAprobadosCubrenTotal();
            $cubreConCredito = $lockedCot->pagosAprobadosCubrenTotal();

            if ($cubreEfectivo && $lockedCot->estado !== 'pagada') {
                if (! $lockedCot->stock_descontado) {
                    $lockedCot->aprobar();
                }
                $lockedCot->update(['estado' => 'pagada']);
            } elseif ($cubreConCredito && ! in_array($lockedCot->estado, ['pagada', 'credito'], true)) {
                if (! $lockedCot->stock_descontado) {
                    $lockedCot->aprobar();
                }
                $lockedCot->update(['estado' => 'credito']);
            }
        });

        Bitacora::registrar('aprobó', "Aprobó pago #{$pago->id} de {$cotizacion->numero}", 'Pago', $pago->id);

        return back()->with('success', 'Pago aprobado.');
    }

    /** Admin rechaza un pago (con motivo). */
    public function rechazar(Request $request, Cotizacion $cotizacion, Pago $pago)
    {
        abort_unless(Auth::user()->hasRole('admin'), 403);
        abort_if($pago->cotizacion_id !== $cotizacion->id, 404);
        abort_if($pago->estado === 'aprobado', 422, 'No se puede rechazar un pago ya aprobado. Contáctate con contabilidad.');

        $data = $request->validate(['motivo' => ['required', 'string', 'max:255']]);

        DB::transaction(function () use ($cotizacion, $pago, $data) {
            $pago->update([
                'estado' => 'rechazado',
                'aprobado_por_id' => Auth::id(),
                'aprobado_at' => now(),
                'rechazo_motivo' => $data['motivo'],
            ]);

            $cotizacion->refresh();
            if ($cotizacion->estado === 'pendiente_revision_pago' && ! $cotizacion->estaTotalmentePagada()) {
                $cotizacion->update(['estado' => 'enviada']);
            }
        });

        Bitacora::registrar('rechazó', "Rechazó pago #{$pago->id} de {$cotizacion->numero}: {$data['motivo']}", 'Pago', $pago->id);

        return back()->with('success', 'Pago rechazado. Se notificó al vendedor.');
    }

    /** Descarga el comprobante (disco privado + validación de ownership + auditoría). */
    public function comprobante(Cotizacion $cotizacion, Pago $pago)
    {
        $this->autorizarCotizacion($cotizacion);
        abort_if($pago->cotizacion_id !== $cotizacion->id, 404);
        abort_unless($pago->comprobante && Storage::disk('local')->exists($pago->comprobante), 404);

        Bitacora::registrar('descargó', "Descargó comprobante de pago #{$pago->id} de {$cotizacion->numero}", 'Pago', $pago->id);

        return Storage::disk('local')->download($pago->comprobante);
    }
}
