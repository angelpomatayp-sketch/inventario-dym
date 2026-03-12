<?php

namespace App\Modules\Requisiciones\Services;

use App\Modules\Requisiciones\Models\Requisicion;
use App\Modules\Requisiciones\Models\RequisicionDetalle;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RequisicionService
{
    /**
     * Crear un nuevo requerimiento con sus detalles.
     */
    public function crear(array $data, int $empresaId, int $almaceneroId, bool $enviarAprobacion): Requisicion
    {
        DB::beginTransaction();
        try {
            $numero = $this->generarNumero(
                $empresaId,
                $data['centro_costo_id'],
                $data['almacen_id'] ?? null
            );

            $estado = $enviarAprobacion
                ? Requisicion::ESTADO_PENDIENTE
                : Requisicion::ESTADO_BORRADOR;

            $requerimiento = Requisicion::create([
                'empresa_id'      => $empresaId,
                'numero'          => $numero,
                'almacenero_id'   => $almaceneroId,
                'centro_costo_id' => $data['centro_costo_id'],
                'almacen_id'      => $data['almacen_id'] ?? null,
                'fecha_solicitud' => now()->toDateString(),
                'fecha_requerida' => $data['fecha_requerida'],
                'prioridad'       => $data['prioridad'],
                'estado'          => $estado,
                'motivo'          => $data['motivo'],
                'observaciones'   => $data['observaciones'] ?? null,
            ]);

            foreach ($data['detalles'] as $detalle) {
                RequisicionDetalle::create([
                    'requisicion_id'      => $requerimiento->id,
                    'producto_id'         => $detalle['producto_id'],
                    'cantidad_solicitada' => $detalle['cantidad_solicitada'],
                    'especificaciones'    => $detalle['especificaciones'] ?? null,
                ]);
            }

            DB::commit();

            return $requerimiento->load(['detalles.producto', 'almacenero', 'centroCosto', 'almacen']);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('RequisicionService::crear', [
                'empresa_id'   => $empresaId,
                'almacenero_id'=> $almaceneroId,
                'error'        => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Actualizar un requerimiento existente (solo en BORRADOR o RECHAZADA).
     */
    public function actualizar(Requisicion $requisicion, array $data, bool $enviarAprobacion): Requisicion
    {
        DB::beginTransaction();
        try {
            $estado = $enviarAprobacion
                ? Requisicion::ESTADO_PENDIENTE
                : Requisicion::ESTADO_BORRADOR;

            $requisicion->update([
                'centro_costo_id'       => $data['centro_costo_id'],
                'almacen_id'            => $data['almacen_id'] ?? null,
                'fecha_requerida'       => $data['fecha_requerida'],
                'prioridad'             => $data['prioridad'],
                'estado'                => $estado,
                'motivo'                => $data['motivo'],
                'observaciones'         => $data['observaciones'] ?? null,
                'aprobado_por'          => null,
                'fecha_aprobacion'      => null,
                'comentario_aprobacion' => null,
            ]);

            $requisicion->detalles()->delete();

            foreach ($data['detalles'] as $detalle) {
                RequisicionDetalle::create([
                    'requisicion_id'      => $requisicion->id,
                    'producto_id'         => $detalle['producto_id'],
                    'cantidad_solicitada' => $detalle['cantidad_solicitada'],
                    'especificaciones'    => $detalle['especificaciones'] ?? null,
                ]);
            }

            DB::commit();

            return $requisicion->load(['detalles.producto', 'almacenero', 'centroCosto', 'almacen']);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('RequisicionService::actualizar', [
                'requisicion_id' => $requisicion->id,
                'error'          => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Aprobar requerimiento, opcionalmente ajustando cantidades aprobadas.
     */
    public function aprobar(Requisicion $requisicion, int $aprobadorId, ?string $comentario, ?array $cantidadesAprobadas): Requisicion
    {
        DB::beginTransaction();
        try {
            if ($cantidadesAprobadas) {
                foreach ($cantidadesAprobadas as $item) {
                    RequisicionDetalle::where('id', $item['detalle_id'])
                        ->where('requisicion_id', $requisicion->id)
                        ->update(['cantidad_aprobada' => $item['cantidad']]);
                }
            } else {
                $requisicion->detalles()->update([
                    'cantidad_aprobada' => DB::raw('cantidad_solicitada'),
                ]);
            }

            $requisicion->aprobar($aprobadorId, $comentario);

            DB::commit();

            return $requisicion->fresh();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('RequisicionService::aprobar', [
                'requisicion_id' => $requisicion->id,
                'error'          => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Generar número correlativo por unidad/almacén.
     * Formato: KOLPA-001, LIMA-002, etc.
     * Usa advisory lock de MySQL para evitar race conditions en inserts concurrentes.
     */
    public function generarNumero(int $empresaId, ?int $centroCostoId, ?int $almacenId): string
    {
        $nombreBase = null;
        if ($almacenId) {
            $nombreBase = DB::table('almacenes')->where('id', $almacenId)->value('nombre');
        }
        if (!$nombreBase && $centroCostoId) {
            $nombreBase = DB::table('centros_costos')->where('id', $centroCostoId)->value('nombre');
        }

        $prefijoBruto = 'RQ';
        if ($nombreBase) {
            $partes = array_values(array_filter(
                preg_split('/[\s\-\/]+/', trim($nombreBase))
            ));
            foreach (array_reverse($partes) as $parte) {
                $limpio = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $parte));
                if ($limpio !== '') {
                    $prefijoBruto = substr($limpio, 0, 10);
                    break;
                }
            }
        }

        $prefijo = $prefijoBruto . '-';
        $lockKey = 'rq_num_' . md5($empresaId . '_' . $prefijo);

        DB::select("SELECT GET_LOCK(?, 10) AS locked", [$lockKey]);

        try {
            $ultimoNumero = Requisicion::where('empresa_id', $empresaId)
                ->where('numero', 'like', $prefijo . '%')
                ->orderByRaw('CAST(SUBSTRING_INDEX(numero, "-", -1) AS UNSIGNED) DESC')
                ->value('numero');

            $secuencia = 1;
            if ($ultimoNumero) {
                $partesFinal = explode('-', $ultimoNumero);
                $secuencia   = (int) end($partesFinal) + 1;
            }

            $numero = $prefijo . str_pad($secuencia, 3, '0', STR_PAD_LEFT);
            while (Requisicion::where('empresa_id', $empresaId)->where('numero', $numero)->exists()) {
                $secuencia++;
                $numero = $prefijo . str_pad($secuencia, 3, '0', STR_PAD_LEFT);
            }
        } finally {
            DB::select("SELECT RELEASE_LOCK(?)", [$lockKey]);
        }

        return $numero;
    }
}
