<?php

namespace App\Modules\EPPs\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\EPPs\Models\TipoEpp;
use App\Modules\EPPs\Models\AsignacionEpp;
use App\Modules\Inventario\Models\Movimiento;
use App\Modules\Inventario\Models\MovimientoDetalle;
use App\Modules\Inventario\Models\StockAlmacen;
use App\Modules\Inventario\Models\Kardex;
use App\Modules\Inventario\Models\Producto;
use App\Modules\Inventario\Models\Familia;
use App\Modules\Administracion\Models\Trabajador;
use App\Modules\Administracion\Models\Usuario;
use App\Shared\Traits\ApiResponse;
use App\Shared\Traits\FiltrosPorRol;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EppController extends Controller
{
    use ApiResponse, FiltrosPorRol;

    // ==================== TIPOS DE EPP ====================

    public function tiposIndex(Request $request): JsonResponse
    {
        // El scope global PerteneceAEmpresa ya filtra por empresa_id automáticamente
        $query = TipoEpp::with(['producto:id,codigo,nombre'])
            ->withCount([
                'asignaciones as vigentes_count' => fn($q) => $q->where('estado', 'VIGENTE'),
                'asignaciones as por_vencer_count' => fn($q) => $q->where('estado', 'POR_VENCER'),
            ]);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                  ->orWhere('codigo', 'like', "%{$search}%");
            });
        }

        if ($request->filled('categoria')) {
            $query->where('categoria', $request->categoria);
        }

        if ($request->has('activo') && $request->activo !== null && $request->activo !== '') {
            $activo = filter_var($request->activo, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($activo !== null) {
                $query->where('activo', $activo);
            }
        }

        $query->orderBy('nombre');

        $perPage = $this->resolvePerPage($request, 15, 100);
        $tipos = $query->paginate($perPage);

        return $this->paginated($tipos);
    }

    public function tiposStore(Request $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id ?? $request->empresa_id;

        $request->validate([
            'codigo' => 'required|string|max:20|unique:tipos_epp,codigo',
            'nombre' => 'required|string|max:100',
            'categoria' => 'required|string|in:CABEZA,OJOS,OIDOS,RESPIRATORIO,MANOS,PIES,CUERPO,ALTURA',
            'vida_util_dias' => 'required|integer|min:1',
            'dias_alerta_vencimiento' => 'required|integer|min:1',
            'requiere_talla' => 'boolean',
            'tallas_disponibles' => 'nullable|string',
            'producto_id' => 'required|exists:productos,id',
        ], [
            'producto_id.required' => 'Debe vincular un producto del inventario',
            'producto_id.exists' => 'El producto seleccionado no existe',
        ]);

        $tipo = TipoEpp::create([
            'empresa_id' => $empresaId,
            'codigo' => $request->codigo,
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
            'categoria' => $request->categoria,
            'vida_util_dias' => $request->vida_util_dias,
            'dias_alerta_vencimiento' => $request->dias_alerta_vencimiento,
            'requiere_talla' => $request->requiere_talla ?? false,
            'tallas_disponibles' => $request->tallas_disponibles,
            'producto_id' => $request->producto_id,
            'activo' => true,
        ]);

        return $this->created($tipo, 'Tipo de EPP creado exitosamente');
    }

    public function tiposShow(TipoEpp $tipoEpp): JsonResponse
    {
        $tipoEpp->loadCount([
            'asignaciones as vigentes_count' => fn($q) => $q->where('estado', 'VIGENTE'),
            'asignaciones as por_vencer_count' => fn($q) => $q->where('estado', 'POR_VENCER'),
            'asignaciones as vencidos_count' => fn($q) => $q->where('estado', 'VENCIDO'),
        ]);

        return $this->success($tipoEpp);
    }

    public function tiposUpdate(Request $request, TipoEpp $tipoEpp): JsonResponse
    {
        $request->validate([
            'codigo' => 'sometimes|string|max:20|unique:tipos_epp,codigo,' . $tipoEpp->id,
            'nombre' => 'sometimes|string|max:100',
            'categoria' => 'sometimes|string|in:CABEZA,OJOS,OIDOS,RESPIRATORIO,MANOS,PIES,CUERPO,ALTURA',
            'vida_util_dias' => 'sometimes|integer|min:1',
            'dias_alerta_vencimiento' => 'sometimes|integer|min:1',
        ]);

        $tipoEpp->update($request->only([
            'codigo', 'nombre', 'descripcion', 'categoria',
            'vida_util_dias', 'dias_alerta_vencimiento',
            'requiere_talla', 'tallas_disponibles', 'producto_id', 'activo'
        ]));

        return $this->success($tipoEpp, 'Tipo de EPP actualizado');
    }

    public function tiposDestroy(TipoEpp $tipoEpp): JsonResponse
    {
        if ($tipoEpp->asignaciones()->whereIn('estado', ['VIGENTE', 'POR_VENCER'])->exists()) {
            return $this->error('No se puede eliminar, tiene asignaciones activas', 422);
        }

        $tipoEpp->delete();
        return $this->success(null, 'Tipo de EPP eliminado');
    }

    // ==================== ASIGNACIONES ====================

    public function asignacionesIndex(Request $request): JsonResponse
    {
        $query = AsignacionEpp::with([
            'tipoEpp:id,codigo,nombre,categoria',
            'producto:id,codigo,nombre,unidad_medida,vida_util_dias',
            'producto.familia:id,nombre,categoria_epp',
            'trabajador:id,nombre,dni',           // Para tipo_receptor = 'usuario'
            'trabajadorPersona:id,nombre,dni,cargo', // Para tipo_receptor = 'trabajador'
            'entregadoPor:id,nombre'
        ]);

        if ($request->user()->empresa_id) {
            $query->where('empresa_id', $request->user()->empresa_id);
        }

        $almacenAsignado = $this->getAlmacenAsignado($request);
        if ($almacenAsignado) {
            $query->where('almacen_id', $almacenAsignado);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('trabajador', fn($q) => $q->where('nombre', 'like', "%{$search}%")->orWhere('dni', 'like', "%{$search}%"))
                  ->orWhereHas('trabajadorPersona', fn($q) => $q->where('nombre', 'like', "%{$search}%")->orWhere('dni', 'like', "%{$search}%"))
                  ->orWhereHas('tipoEpp', fn($q) => $q->where('nombre', 'like', "%{$search}%"))
                  ->orWhereHas('producto', fn($q) => $q->where('nombre', 'like', "%{$search}%")->orWhere('codigo', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('tipo_epp_id')) {
            $query->where('tipo_epp_id', $request->tipo_epp_id);
        }

        if ($request->filled('producto_id')) {
            $query->where('producto_id', $request->producto_id);
        }

        if ($request->filled('trabajador_id')) {
            $query->where('trabajador_id', $request->trabajador_id);
        }

        if ($request->filled('almacen_id')) {
            $query->where('almacen_id', $request->almacen_id);
        }

        // Filtro por categoría EPP (de la familia del producto)
        if ($request->filled('categoria')) {
            $query->where(function ($q) use ($request) {
                $q->whereHas('tipoEpp', fn($q) => $q->where('categoria', $request->categoria))
                  ->orWhereHas('producto.familia', fn($q) => $q->where('categoria_epp', $request->categoria));
            });
        }

        $sortField = $request->get('sort_field', 'fecha_entrega');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortField, $sortOrder);

        $perPage = $this->resolvePerPage($request, 15, 100);
        $asignaciones = $query->paginate($perPage);

        return $this->paginated($asignaciones);
    }

    public function asignacionesStore(Request $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id ?? $request->empresa_id;
        $almacenAsignado = $this->getAlmacenAsignado($request);

        // Determinar tipo de receptor (trabajador de tabla trabajadores o usuario del sistema)
        $tipoReceptor = $request->get('tipo_receptor', 'usuario');
        $tablaValidacion = $tipoReceptor === 'trabajador' ? 'trabajadores' : 'usuarios';

        // Ahora producto_id es requerido, tipo_epp_id es opcional (retrocompatibilidad)
        $request->validate([
            'producto_id' => 'required|exists:productos,id',
            'tipo_epp_id' => 'nullable|exists:tipos_epp,id',
            'trabajador_id' => "required|exists:{$tablaValidacion},id",
            'tipo_receptor' => 'nullable|in:trabajador,usuario',
            'fecha_entrega' => 'required|date',
            'cantidad' => 'required|integer|min:1',
            'talla' => 'nullable|string|max:10',
            'numero_serie' => 'nullable|string|max:50',
            'observaciones' => 'nullable|string',
            'almacen_id' => 'required|exists:almacenes,id',
        ], [
            'producto_id.required' => 'Seleccione un producto EPP',
            'producto_id.exists' => 'El producto seleccionado no existe',
            'trabajador_id.required' => 'Seleccione un trabajador o usuario',
            'trabajador_id.exists' => 'El trabajador/usuario seleccionado no existe',
            'almacen_id.required' => 'Seleccione un almacén',
        ]);

        if ($almacenAsignado && (int) $request->almacen_id !== (int) $almacenAsignado) {
            return $this->error('Solo puede entregar EPP desde su almacén asignado', 403);
        }

        if ($tipoReceptor === 'trabajador') {
            $trabajadorValido = Trabajador::where('empresa_id', $empresaId)
                ->where('id', $request->trabajador_id)
                ->exists();
            if (!$trabajadorValido) {
                return $this->error('El trabajador seleccionado no pertenece a la empresa', 422);
            }
        } else {
            $usuarioValido = Usuario::where('empresa_id', $empresaId)
                ->where('id', $request->trabajador_id)
                ->exists();
            if (!$usuarioValido) {
                return $this->error('El usuario seleccionado no pertenece a la empresa', 422);
            }
        }

        // Obtener producto con familia
        $producto = Producto::with('familia')->findOrFail($request->producto_id);

        // Verificar que el producto pertenece a una familia EPP
        if (!$producto->familia || !$producto->familia->es_epp) {
            return $this->error('El producto seleccionado no pertenece a una familia EPP', 422);
        }

        $fechaEntrega = \Carbon\Carbon::parse($request->fecha_entrega);

        // Calcular fecha de vencimiento usando vida_util_dias del producto
        $vidaUtilDias = $producto->vida_util_dias ?? 365; // Default 1 año si no está definido
        $fechaVencimiento = $fechaEntrega->copy()->addDays($vidaUtilDias);

        DB::beginTransaction();
        try {
            // Verificar y descontar stock
            $stockAlmacen = StockAlmacen::where('empresa_id', $empresaId)
                ->where('producto_id', $producto->id)
                ->where('almacen_id', $request->almacen_id)
                ->first();

            if (!$stockAlmacen || $stockAlmacen->stock_actual < $request->cantidad) {
                return $this->error('Stock insuficiente para el EPP seleccionado', 422);
            }

            // Crear movimiento de salida
            $numero = $this->generarNumeroMovimiento($empresaId, 'SALIDA');
            $movimiento = Movimiento::create([
                'empresa_id' => $empresaId,
                'numero' => $numero,
                'tipo' => Movimiento::TIPO_SALIDA,
                'subtipo' => 'ENTREGA_EPP',
                'almacen_origen_id' => $request->almacen_id,
                'usuario_id' => $request->user()->id,
                'fecha' => $fechaEntrega,
                'observaciones' => "Entrega de EPP: {$producto->nombre}",
                'estado' => Movimiento::ESTADO_COMPLETADO,
            ]);

            // Crear detalle del movimiento
            $costoUnitario = $stockAlmacen->costo_promedio ?? 0;
            MovimientoDetalle::create([
                'movimiento_id' => $movimiento->id,
                'producto_id' => $producto->id,
                'cantidad' => $request->cantidad,
                'costo_unitario' => $costoUnitario,
                'costo_total' => $request->cantidad * $costoUnitario,
            ]);

            // Descontar stock
            $nuevoStock = $stockAlmacen->stock_actual - $request->cantidad;
            $stockAlmacen->update(['stock_actual' => $nuevoStock]);

            // Registrar en Kardex
            Kardex::create([
                'empresa_id' => $empresaId,
                'producto_id' => $producto->id,
                'almacen_id' => $request->almacen_id,
                'movimiento_id' => $movimiento->id,
                'fecha' => $fechaEntrega,
                'tipo_operacion' => Kardex::TIPO_SALIDA,
                'documento_referencia' => $numero,
                'descripcion' => "Entrega EPP: {$producto->nombre}",
                'cantidad' => $request->cantidad,
                'costo_unitario' => $costoUnitario,
                'costo_total' => $request->cantidad * $costoUnitario,
                'saldo_cantidad' => $nuevoStock,
                'saldo_costo_unitario' => $stockAlmacen->costo_promedio ?? 0,
                'saldo_costo_total' => $nuevoStock * ($stockAlmacen->costo_promedio ?? 0),
            ]);

            // Crear asignación con producto_id directamente
            $asignacion = AsignacionEpp::create([
                'empresa_id' => $empresaId,
                'producto_id' => $producto->id,
                'tipo_epp_id' => $request->tipo_epp_id, // Opcional para retrocompatibilidad
                'trabajador_id' => $request->trabajador_id,
                'tipo_receptor' => $tipoReceptor,
                'entregado_por' => $request->user()->id,
                'numero_serie' => $request->numero_serie,
                'talla' => $request->talla,
                'cantidad' => $request->cantidad,
                'fecha_entrega' => $fechaEntrega,
                'fecha_vencimiento' => $fechaVencimiento,
                'estado' => AsignacionEpp::ESTADO_VIGENTE,
                'observaciones' => $request->observaciones,
                'almacen_id' => $request->almacen_id,
                'movimiento_id' => $movimiento->id,
            ]);

            DB::commit();

            // Cargar relación correcta según tipo de receptor
            $relaciones = ['producto.familia'];
            if ($tipoReceptor === 'trabajador') {
                $relaciones[] = 'trabajadorPersona';
            } else {
                $relaciones[] = 'trabajador';
            }

            return $this->created(
                $asignacion->load($relaciones),
                'EPP asignado exitosamente y stock descontado'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al asignar EPP', [
                'empresa_id' => $empresaId,
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);
            return $this->serverError('Error interno al asignar EPP');
        }
    }

    /**
     * Generar número de movimiento para EPPs.
     */
    private function generarNumeroMovimiento(int $empresaId, string $tipo): string
    {
        $prefijo = 'EPP';
        $año = date('Y');
        $mes = date('m');
        $base = "{$prefijo}-{$año}{$mes}-";

        $ultimoNumero = Movimiento::where('empresa_id', $empresaId)
            ->where('subtipo', 'ENTREGA_EPP')
            ->where('numero', 'like', $base . '%')
            ->orderBy('numero', 'desc')
            ->lockForUpdate()
            ->value('numero');

        $secuencia = 1;
        if ($ultimoNumero) {
            $partes = explode('-', $ultimoNumero);
            $secuencia = (int) end($partes) + 1;
        }

        $numero = $base . str_pad($secuencia, 6, '0', STR_PAD_LEFT);
        while (Movimiento::where('empresa_id', $empresaId)->where('numero', $numero)->exists()) {
            $secuencia++;
            $numero = $base . str_pad($secuencia, 6, '0', STR_PAD_LEFT);
        }

        return $numero;
    }

    public function asignacionesShow(AsignacionEpp $asignacion): JsonResponse
    {
        if ($asignacion->empresa_id !== auth()->user()?->empresa_id) {
            return $this->error('No autorizado', 403);
        }

        $almacenAsignado = $this->getAlmacenAsignado(request());
        if ($almacenAsignado && (int) $asignacion->almacen_id !== (int) $almacenAsignado) {
            return $this->error('No autorizado', 403);
        }

        $asignacion->load(['tipoEpp', 'producto.familia', 'trabajador', 'entregadoPor', 'almacen']);
        return $this->success($asignacion);
    }

    public function confirmarRecepcion(Request $request, AsignacionEpp $asignacion): JsonResponse
    {
        if ($asignacion->empresa_id !== $request->user()->empresa_id) {
            return $this->error('No autorizado', 403);
        }

        $almacenAsignado = $this->getAlmacenAsignado($request);
        if ($almacenAsignado && (int) $asignacion->almacen_id !== (int) $almacenAsignado) {
            return $this->error('No autorizado', 403);
        }

        $asignacion->confirmarRecepcion();

        return $this->success($asignacion, 'Recepción confirmada por almacén');
    }

    public function registrarDevolucion(Request $request, AsignacionEpp $asignacion): JsonResponse
    {
        if ($asignacion->empresa_id !== $request->user()->empresa_id) {
            return $this->error('No autorizado', 403);
        }

        $almacenAsignado = $this->getAlmacenAsignado($request);
        if ($almacenAsignado && (int) $asignacion->almacen_id !== (int) $almacenAsignado) {
            return $this->error('No autorizado', 403);
        }

        if (!in_array($asignacion->estado, ['VIGENTE', 'POR_VENCER', 'VENCIDO'])) {
            return $this->error('Esta asignación no puede ser devuelta', 422);
        }

        $asignacion->registrarDevolucion($request->observaciones);

        return $this->success($asignacion, 'Devolución registrada');
    }

    public function cambiarEstado(Request $request, AsignacionEpp $asignacion): JsonResponse
    {
        if ($asignacion->empresa_id !== $request->user()->empresa_id) {
            return $this->error('No autorizado', 403);
        }

        $almacenAsignado = $this->getAlmacenAsignado($request);
        if ($almacenAsignado && (int) $asignacion->almacen_id !== (int) $almacenAsignado) {
            return $this->error('No autorizado', 403);
        }

        $request->validate([
            'estado' => 'required|in:EXTRAVIADO,DAÑADO',
            'observaciones' => 'nullable|string',
        ]);

        if ($request->estado === 'EXTRAVIADO') {
            $asignacion->marcarExtraviado($request->observaciones);
        } else {
            $asignacion->marcarDañado($request->observaciones);
        }

        return $this->success($asignacion, 'Estado actualizado');
    }

    public function renovar(Request $request, AsignacionEpp $asignacion): JsonResponse
    {
        if ($asignacion->empresa_id !== $request->user()->empresa_id) {
            return $this->error('No autorizado', 403);
        }

        $almacenAsignado = $this->getAlmacenAsignado($request);
        if ($almacenAsignado && (int) $asignacion->almacen_id !== (int) $almacenAsignado) {
            return $this->error('No autorizado', 403);
        }

        $request->validate([
            'motivo' => 'required|in:VENCIMIENTO,DETERIORO,EXTRAVIO,CAMBIO_TALLA,OTRO',
            'nueva_talla' => 'nullable|string|max:10',
            'observaciones' => 'nullable|string',
            'almacen_id' => 'nullable|exists:almacenes,id',
        ]);

        DB::beginTransaction();
        try {
            $fechaEntrega = now();
            $empresaId = $asignacion->empresa_id;
            $almacenId = $request->almacen_id ?? $asignacion->almacen_id;

            // Obtener producto (de la asignación o del tipoEpp para retrocompatibilidad)
            $productoId = $asignacion->producto_id;
            $producto = null;
            $vidaUtilDias = 365; // Default

            if ($productoId) {
                $producto = Producto::find($productoId);
                if ($producto && $producto->vida_util_dias) {
                    $vidaUtilDias = $producto->vida_util_dias;
                }
            } elseif ($asignacion->tipoEpp) {
                // Retrocompatibilidad con tipoEpp
                $productoId = $asignacion->tipoEpp->producto_id;
                $vidaUtilDias = $asignacion->tipoEpp->vida_util_dias;
                if ($productoId) {
                    $producto = Producto::find($productoId);
                }
            }

            $nombreEpp = $producto?->nombre ?? $asignacion->tipoEpp?->nombre ?? 'EPP';

            // Verificar y descontar stock
            $movimiento = null;
            if ($productoId && $almacenId) {
                $stockAlmacen = StockAlmacen::where('empresa_id', $empresaId)
                    ->where('producto_id', $productoId)
                    ->where('almacen_id', $almacenId)
                    ->first();

                if (!$stockAlmacen || $stockAlmacen->stock_actual < $asignacion->cantidad) {
                    return $this->error('Stock insuficiente para renovar el EPP', 422);
                }

                // Crear movimiento de salida
                $numero = $this->generarNumeroMovimiento($empresaId, 'SALIDA');
                $movimiento = Movimiento::create([
                    'empresa_id' => $empresaId,
                    'numero' => $numero,
                    'tipo' => Movimiento::TIPO_SALIDA,
                    'subtipo' => 'RENOVACION_EPP',
                    'almacen_origen_id' => $almacenId,
                    'usuario_id' => $request->user()->id,
                    'fecha' => $fechaEntrega,
                    'observaciones' => "Renovación de EPP: {$nombreEpp} - Motivo: {$request->motivo}",
                    'estado' => Movimiento::ESTADO_COMPLETADO,
                ]);

                // Crear detalle del movimiento
                $costoUnitario = $stockAlmacen->costo_promedio ?? 0;
                MovimientoDetalle::create([
                    'movimiento_id' => $movimiento->id,
                    'producto_id' => $productoId,
                    'cantidad' => $asignacion->cantidad,
                    'costo_unitario' => $costoUnitario,
                    'costo_total' => $asignacion->cantidad * $costoUnitario,
                ]);

                // Descontar stock
                $nuevoStock = $stockAlmacen->stock_actual - $asignacion->cantidad;
                $stockAlmacen->update(['stock_actual' => $nuevoStock]);

                // Registrar en Kardex
                Kardex::create([
                    'empresa_id' => $empresaId,
                    'producto_id' => $productoId,
                    'almacen_id' => $almacenId,
                    'movimiento_id' => $movimiento->id,
                    'fecha' => $fechaEntrega,
                    'tipo_operacion' => Kardex::TIPO_SALIDA,
                    'documento_referencia' => $numero,
                    'descripcion' => "Renovación EPP: {$nombreEpp}",
                    'cantidad' => $asignacion->cantidad,
                    'costo_unitario' => $costoUnitario,
                    'costo_total' => $asignacion->cantidad * $costoUnitario,
                    'saldo_cantidad' => $nuevoStock,
                    'saldo_costo_unitario' => $stockAlmacen->costo_promedio ?? 0,
                    'saldo_costo_total' => $nuevoStock * ($stockAlmacen->costo_promedio ?? 0),
                ]);
            }

            // Cerrar asignación anterior
            $asignacion->update([
                'estado' => AsignacionEpp::ESTADO_DEVUELTO,
                'fecha_devolucion' => now(),
            ]);

            // Crear nueva asignación (mantiene el mismo receptor)
            $nuevaAsignacion = AsignacionEpp::create([
                'empresa_id' => $empresaId,
                'producto_id' => $productoId,
                'tipo_epp_id' => $asignacion->tipo_epp_id,
                'trabajador_id' => $asignacion->trabajador_id,
                'tipo_receptor' => $asignacion->tipo_receptor ?? 'usuario',
                'entregado_por' => $request->user()->id,
                'talla' => $request->nueva_talla ?? $asignacion->talla,
                'cantidad' => $asignacion->cantidad,
                'fecha_entrega' => $fechaEntrega,
                'fecha_vencimiento' => $fechaEntrega->copy()->addDays($vidaUtilDias),
                'estado' => AsignacionEpp::ESTADO_VIGENTE,
                'observaciones' => $request->observaciones,
                'almacen_id' => $almacenId,
                'movimiento_id' => $movimiento?->id,
            ]);

            // Registrar renovación
            DB::table('renovaciones_epp')->insert([
                'asignacion_anterior_id' => $asignacion->id,
                'asignacion_nueva_id' => $nuevaAsignacion->id,
                'motivo' => $request->motivo,
                'observaciones' => $request->observaciones,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();

            return $this->success(
                $nuevaAsignacion->load(['producto.familia', 'trabajador']),
                'EPP renovado exitosamente' . ($movimiento ? ' y stock descontado' : '')
            );
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al renovar EPP', [
                'empresa_id' => $asignacion->empresa_id,
                'user_id' => $request->user()->id,
                'asignacion_id' => $asignacion->id,
                'error' => $e->getMessage(),
            ]);
            return $this->error('Error interno al renovar EPP', 500);
        }
    }

    // ==================== HISTORIAL POR TRABAJADOR ====================

    public function historialTrabajador(Request $request, $trabajadorId): JsonResponse
    {
        $tipoReceptor = $request->get('tipo_receptor', 'usuario');

        $query = AsignacionEpp::with([
                'tipoEpp:id,codigo,nombre,categoria',
                'producto:id,codigo,nombre,vida_util_dias',
                'producto.familia:id,nombre,categoria_epp',
                'entregadoPor:id,nombre'
            ])
            ->where('trabajador_id', $trabajadorId);

        if ($request->user()->empresa_id) {
            $query->where('empresa_id', $request->user()->empresa_id);
        }

        $almacenAsignado = $this->getAlmacenAsignado($request);
        if ($almacenAsignado) {
            $query->where('almacen_id', $almacenAsignado);
        }

        // Filtrar por tipo de receptor si se especifica
        if ($tipoReceptor) {
            $query->where('tipo_receptor', $tipoReceptor);
        }

        $asignaciones = $query->orderBy('fecha_entrega', 'desc')->get();

        return $this->success($asignaciones);
    }

    // ==================== ESTADÍSTICAS ====================

    public function estadisticas(Request $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;
        $almacenAsignado = $this->getAlmacenAsignado($request);

        $baseAsignaciones = AsignacionEpp::where('empresa_id', $empresaId);
        if ($almacenAsignado) {
            $baseAsignaciones->where('almacen_id', $almacenAsignado);
        }

        $stats = [
            'total_asignaciones' => (clone $baseAsignaciones)
                ->whereIn('estado', ['VIGENTE', 'POR_VENCER'])
                ->count(),
            'vigentes' => (clone $baseAsignaciones)
                ->where('estado', 'VIGENTE')
                ->count(),
            'por_vencer' => (clone $baseAsignaciones)
                ->where('estado', 'POR_VENCER')
                ->count(),
            'vencidos' => (clone $baseAsignaciones)
                ->where('estado', 'VENCIDO')
                ->count(),
            'tipos_epp' => TipoEpp::where('empresa_id', $empresaId)
                ->where('activo', true)
                ->count(),
        ];

        // Por vencer en los próximos 30 días
        $stats['proximos_vencer'] = (clone $baseAsignaciones)
            ->where('estado', 'VIGENTE')
            ->where('fecha_vencimiento', '<=', now()->addDays(30))
            ->where('fecha_vencimiento', '>', now())
            ->count();

        // Por categoría
        $queryPorCategoria = AsignacionEpp::where('asignaciones_epp.empresa_id', $empresaId)
            ->whereIn('estado', ['VIGENTE', 'POR_VENCER'])
            ->join('tipos_epp', 'asignaciones_epp.tipo_epp_id', '=', 'tipos_epp.id')
            ->selectRaw('tipos_epp.categoria, count(*) as total')
            ->groupBy('tipos_epp.categoria');
        if ($almacenAsignado) {
            $queryPorCategoria->where('asignaciones_epp.almacen_id', $almacenAsignado);
        }
        $stats['por_categoria'] = $queryPorCategoria->get();

        return $this->success($stats);
    }

    // ==================== ALERTAS DE VENCIMIENTO ====================

    public function alertasVencimiento(Request $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;
        $dias = $request->get('dias', 30);
        $almacenAsignado = $this->getAlmacenAsignado($request);

        $query = AsignacionEpp::with([
                'tipoEpp:id,codigo,nombre',
                'producto:id,codigo,nombre',
                'producto.familia:id,nombre,categoria_epp',
                'trabajador:id,nombre,dni'
            ])
            ->where('empresa_id', $empresaId)
            ->where('estado', 'VIGENTE')
            ->where('fecha_vencimiento', '<=', now()->addDays($dias))
            ->orderBy('fecha_vencimiento');

        if ($almacenAsignado) {
            $query->where('almacen_id', $almacenAsignado);
        }

        $alertas = $query->get();

        return $this->success($alertas);
    }

    // ==================== ACTUALIZAR ESTADOS MASIVAMENTE ====================

    public function actualizarEstados(): JsonResponse
    {
        $actualizados = 0;

        // Marcar como POR_VENCER
        $porVencer = AsignacionEpp::where('estado', 'VIGENTE')
            ->whereRaw('DATEDIFF(fecha_vencimiento, NOW()) <= (SELECT dias_alerta_vencimiento FROM tipos_epp WHERE tipos_epp.id = asignaciones_epp.tipo_epp_id)')
            ->where('fecha_vencimiento', '>', now())
            ->update(['estado' => 'POR_VENCER']);

        // Marcar como VENCIDO
        $vencidos = AsignacionEpp::whereIn('estado', ['VIGENTE', 'POR_VENCER'])
            ->where('fecha_vencimiento', '<', now())
            ->update(['estado' => 'VENCIDO']);

        return $this->success([
            'por_vencer' => $porVencer,
            'vencidos' => $vencidos,
        ], 'Estados actualizados');
    }

    // ==================== CATEGORÍAS ====================

    public function categorias(): JsonResponse
    {
        return $this->success(TipoEpp::getCategorias());
    }

    // ==================== PRODUCTOS EPP (para vincular) ====================

    /**
     * Obtener familias marcadas como EPP.
     */
    public function familiasEpp(Request $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;

        $familias = Familia::where('empresa_id', $empresaId)
            ->where('es_epp', true)
            ->where('activo', true)
            ->orderBy('nombre')
            ->get(['id', 'codigo', 'nombre', 'categoria_epp']);

        return $this->success($familias);
    }

    /**
     * Obtener productos de familias EPP (para vincular a TipoEpp).
     */
    public function productosEpp(Request $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;
        $almacenId = $request->filled('almacen_id') ? (int) $request->almacen_id : null;
        $almacenAsignado = $this->getAlmacenAsignado($request);
        if ($almacenAsignado) {
            $almacenId = $almacenAsignado;
        }
        $soloConStock = $request->boolean('solo_con_stock', true);

        // Obtener IDs de familias marcadas como EPP
        $familiasEppIds = Familia::where('empresa_id', $empresaId)
            ->where('es_epp', true)
            ->pluck('id');

        $query = Producto::with([
                'familia:id,nombre,categoria_epp',
                'stockAlmacenes' => function ($q) use ($almacenId) {
                    if ($almacenId) {
                        $q->where('almacen_id', $almacenId);
                    }
                }
            ])
            ->where('empresa_id', $empresaId)
            ->whereIn('familia_id', $familiasEppIds)
            ->where('activo', true);

        if ($almacenId) {
            $query->whereHas('stockAlmacenes', function ($q) use ($almacenId, $soloConStock) {
                $q->where('almacen_id', $almacenId);
                if ($soloConStock) {
                    $q->where('stock_actual', '>', 0);
                }
            });
        } elseif ($soloConStock) {
            $query->whereHas('stockAlmacenes', function ($q) {
                $q->where('stock_actual', '>', 0);
            });
        }

        // Filtro por categoría EPP (de la familia)
        if ($request->filled('categoria')) {
            $query->whereHas('familia', function ($q) use ($request) {
                $q->where('categoria_epp', $request->categoria);
            });
        }

        // Búsqueda
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                  ->orWhere('codigo', 'like', "%{$search}%");
            });
        }

        $productos = $query->orderBy('nombre')->get();

        // Transformar para incluir stock total y campos EPP
        $resultado = $productos->map(function ($producto) {
            $stockTotal = $producto->stockAlmacenes->sum('stock_actual');

            return [
                'id' => $producto->id,
                'codigo' => $producto->codigo,
                'nombre' => $producto->nombre,
                'unidad_medida' => $producto->unidad_medida,
                'familia' => $producto->familia ? [
                    'id' => $producto->familia->id,
                    'nombre' => $producto->familia->nombre,
                    'categoria_epp' => $producto->familia->categoria_epp,
                ] : null,
                'categoria_epp' => $producto->familia?->categoria_epp,
                'stock_total' => $stockTotal,
                // Campos EPP del producto
                'vida_util_dias' => $producto->vida_util_dias,
                'dias_alerta_vencimiento' => $producto->dias_alerta_vencimiento,
                'requiere_talla' => $producto->requiere_talla,
                'tallas_disponibles' => $producto->tallas_disponibles,
            ];
        });

        return $this->success($resultado);
    }

    /**
     * Obtener categorías de EPP disponibles (de familias).
     */
    public function categoriasEpp(): JsonResponse
    {
        return $this->success(Familia::getCategoriasEpp());
    }

    // ==================== PERSONAL PARA ASIGNACIÓN ====================

    /**
     * Obtener personal (trabajadores y usuarios) para asignación de EPPs.
     * Combina trabajadores (sin login) y usuarios (con login) activos.
     * El almacenero puede ver todo el personal de la empresa (sin restricción por centro de costo).
     */
    public function personalParaEpp(Request $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id;
        $centroCostoId = $request->get('centro_costo_id');
        $almacenId = $request->get('almacen_id');
        $search = $request->get('search');

        $centroCostoAsignado = $this->getCentroCostoAsignado($request);
        if ($centroCostoAsignado) {
            $centroCostoId = $centroCostoAsignado;
        }

        $almacenAsignado = $this->getAlmacenAsignado($request);
        if ($almacenAsignado) {
            $almacenId = $almacenAsignado;
        }

        if (!$centroCostoId && $almacenId) {
            $centroCostoId = \App\Modules\Administracion\Models\Almacen::where('empresa_id', $empresaId)
                ->where('id', $almacenId)
                ->value('centro_costo_id');
        }

        $personal = collect();

        // 1. Cargar trabajadores (personal sin acceso al sistema)
        $trabajadoresQuery = Trabajador::with('centroCosto:id,codigo,nombre')
            ->where('empresa_id', $empresaId)
            ->where('activo', true);

        if ($centroCostoId) {
            $trabajadoresQuery->where('centro_costo_id', $centroCostoId);
        }

        if ($search) {
            $trabajadoresQuery->where(function ($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                  ->orWhere('dni', 'like', "%{$search}%")
                  ->orWhere('cargo', 'like', "%{$search}%");
            });
        }

        $trabajadores = $trabajadoresQuery->orderBy('nombre')->get();

        foreach ($trabajadores as $trab) {
            $personal->push([
                'id' => $trab->id,
                'tipo' => 'trabajador',
                'nombre' => $trab->nombre,
                'dni' => $trab->dni,
                'cargo' => $trab->cargo,
                'centro_costo_id' => $trab->centro_costo_id,
                'centro_costo' => $trab->centroCosto ? [
                    'id' => $trab->centroCosto->id,
                    'codigo' => $trab->centroCosto->codigo,
                    'nombre' => $trab->centroCosto->nombre,
                ] : null,
                'display_name' => $trab->nombre . ($trab->cargo ? " ({$trab->cargo})" : ''),
            ]);
        }

        // 2. Cargar usuarios activos (personal con acceso al sistema)
        $usuariosQuery = Usuario::with('centroCosto:id,codigo,nombre')
            ->where('empresa_id', $empresaId)
            ->where('activo', true);

        if ($centroCostoId) {
            $usuariosQuery->where('centro_costo_id', $centroCostoId);
        }

        if ($almacenId) {
            $usuariosQuery->where('almacen_id', $almacenId);
        }

        if ($search) {
            $usuariosQuery->where(function ($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                  ->orWhere('dni', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $usuarios = $usuariosQuery->orderBy('nombre')->get();

        foreach ($usuarios as $user) {
            $personal->push([
                'id' => $user->id,
                'tipo' => 'usuario',
                'nombre' => $user->nombre,
                'dni' => $user->dni,
                'cargo' => null,
                'centro_costo_id' => $user->centro_costo_id,
                'centro_costo' => $user->centroCosto ? [
                    'id' => $user->centroCosto->id,
                    'codigo' => $user->centroCosto->codigo,
                    'nombre' => $user->centroCosto->nombre,
                ] : null,
                'display_name' => $user->nombre . ' [Usuario]',
            ]);
        }

        // Ordenar por nombre
        $personal = $personal->sortBy('nombre')->values();

        return $this->success($personal);
    }
}
