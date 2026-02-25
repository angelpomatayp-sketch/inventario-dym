<?php

namespace App\Modules\Inventario\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventario\Models\Familia;
use App\Modules\Inventario\Models\Movimiento;
use App\Modules\Inventario\Models\Producto;
use App\Modules\Inventario\Models\ProductoImagen;
use App\Shared\Traits\ApiResponse;
use App\Shared\Traits\FiltrosPorRol;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductoController extends Controller
{
    use ApiResponse, FiltrosPorRol;

    /**
     * Listar productos.
     */
    public function index(Request $request): JsonResponse
    {
        $almacenId = $request->filled('almacen_id') ? (int) $request->almacen_id : null;
        $soloConStock = $request->boolean('solo_con_stock');

        $query = Producto::with([
            'familia',
            'stockAlmacenes' => function ($q) use ($almacenId) {
                if ($almacenId) {
                    $q->where('almacen_id', $almacenId);
                }
            },
            'stockAlmacenes.almacen'
        ]);

        // Filtro por empresa (multi-tenancy)
        if ($request->user()->empresa_id) {
            $query->where('empresa_id', $request->user()->empresa_id);
        }

        // NOTA: Los productos son de la EMPRESA, no del almacén
        // Todos los usuarios de la empresa pueden ver todos los productos
        // El filtro de almacén solo aplica al STOCK, no a los productos

        // Filtros
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                  ->orWhere('codigo', 'like', "%{$search}%")
                  ->orWhere('marca', 'like', "%{$search}%");
            });
        }

        if ($request->filled('familia_id')) {
            $query->where('familia_id', $request->familia_id);
        }

        if ($request->filled('activo')) {
            $query->where('activo', $request->boolean('activo'));
        }

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

        if ($request->boolean('stock_bajo')) {
            $query->whereHas('stockAlmacenes', function ($q) {
                $q->whereColumn('stock_actual', '<=', 'stock_minimo')
                  ->where('stock_minimo', '>', 0);
            });
        }

        // Ordenamiento
        $sortField = $request->get('sort_field', 'nombre');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortField, $sortOrder);

        // Paginación
        $perPage = $this->resolvePerPage($request, 15, 100);
        $productos = $query->paginate($perPage);

        // Transformar para incluir stock total
        $productos->getCollection()->transform(function ($producto) {
            $stockTotal = $producto->stockAlmacenes->sum('stock_actual');
            $costoPromedio = $stockTotal > 0
                ? $producto->stockAlmacenes->sum(fn($s) => $s->stock_actual * $s->costo_promedio) / $stockTotal
                : 0;

            return [
                'id' => $producto->id,
                'codigo' => $producto->codigo,
                'nombre' => $producto->nombre,
                'descripcion' => $producto->descripcion,
                'unidad_medida' => $producto->unidad_medida,
                'marca' => $producto->marca,
                'modelo' => $producto->modelo,
                'stock_minimo' => $producto->stock_minimo,
                'stock_maximo' => $producto->stock_maximo,
                'ubicacion_fisica' => $producto->ubicacion_fisica,
                'activo' => $producto->activo,
                'familia' => $producto->familia ? [
                    'id' => $producto->familia->id,
                    'nombre' => $producto->familia->nombre,
                ] : null,
                'stock_total' => $stockTotal,
                'costo_promedio' => round($costoPromedio, 4),
                'stock_por_almacen' => $producto->stockAlmacenes->map(fn($s) => [
                    'almacen_id' => $s->almacen_id,
                    'almacen_nombre' => $s->almacen->nombre ?? '',
                    'stock_actual' => $s->stock_actual,
                    'costo_promedio' => $s->costo_promedio,
                ]),
            ];
        });

        return $this->paginated($productos);
    }

    /**
     * Crear producto.
     */
    public function store(Request $request): JsonResponse
    {
        $empresaId = $request->user()->empresa_id ?? $request->empresa_id;

        $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'familia_id' => 'nullable|exists:familias,id',
            'unidad_medida' => 'required|string|max:10',
            'marca' => 'nullable|string|max:100',
            'modelo' => 'nullable|string|max:100',
            'stock_minimo' => 'integer|min:0',
            'stock_maximo' => 'integer|min:0',
            'ubicacion_fisica' => 'nullable|string|max:255',
            'requiere_lote' => 'boolean',
            'activo' => 'boolean',
            // Campos EPP (opcionales, solo si es familia EPP)
            'vida_util_dias' => 'nullable|integer|min:1',
            'dias_alerta_vencimiento' => 'nullable|integer|min:1',
            'requiere_talla' => 'boolean',
            'tallas_disponibles' => 'nullable|string|max:255',
        ], [
            'nombre.required' => 'El nombre es requerido',
            'unidad_medida.required' => 'La unidad de medida es requerida',
        ]);

        $producto = DB::transaction(function () use ($request, $empresaId) {
            return Producto::create([
                'empresa_id' => $empresaId,
                'codigo' => $this->generarCodigoProducto($empresaId, $request->familia_id),
                'nombre' => $request->nombre,
                'descripcion' => $request->descripcion,
                'familia_id' => $request->familia_id,
                'unidad_medida' => $request->unidad_medida,
                'marca' => $request->marca,
                'modelo' => $request->modelo,
                'stock_minimo' => $request->get('stock_minimo', 0),
                'stock_maximo' => $request->get('stock_maximo', 0),
                'ubicacion_fisica' => $request->ubicacion_fisica,
                'requiere_lote' => $request->get('requiere_lote', false),
                'activo' => $request->get('activo', true),
                // Campos EPP
                'vida_util_dias' => $request->vida_util_dias,
                'dias_alerta_vencimiento' => $request->dias_alerta_vencimiento,
                'requiere_talla' => $request->get('requiere_talla', false),
                'tallas_disponibles' => $request->tallas_disponibles,
            ]);
        });

        $producto->load('familia');

        return $this->created($producto, 'Producto creado exitosamente');
    }

    /**
     * Mostrar producto.
     */
    public function show(Producto $producto): JsonResponse
    {
        $producto->load(['familia', 'stockAlmacenes.almacen']);

        $stockTotal = $producto->stockAlmacenes->sum('stock_actual');
        $costoPromedio = $stockTotal > 0
            ? $producto->stockAlmacenes->sum(fn($s) => $s->stock_actual * $s->costo_promedio) / $stockTotal
            : 0;

        return $this->success([
            'id' => $producto->id,
            'codigo' => $producto->codigo,
            'nombre' => $producto->nombre,
            'descripcion' => $producto->descripcion,
            'unidad_medida' => $producto->unidad_medida,
            'marca' => $producto->marca,
            'modelo' => $producto->modelo,
            'stock_minimo' => $producto->stock_minimo,
            'stock_maximo' => $producto->stock_maximo,
            'ubicacion_fisica' => $producto->ubicacion_fisica,
            'requiere_lote' => $producto->requiere_lote,
            'activo' => $producto->activo,
            'familia' => $producto->familia,
            'stock_total' => $stockTotal,
            'costo_promedio' => round($costoPromedio, 4),
            'valor_inventario' => round($stockTotal * $costoPromedio, 2),
            'stock_por_almacen' => $producto->stockAlmacenes->map(fn($s) => [
                'almacen' => $s->almacen,
                'stock_actual' => $s->stock_actual,
                'stock_minimo' => $s->stock_minimo,
                'costo_promedio' => $s->costo_promedio,
                'valor_total' => round($s->stock_actual * $s->costo_promedio, 2),
            ]),
            'created_at' => $producto->created_at,
            'updated_at' => $producto->updated_at,
        ]);
    }

    /**
     * Actualizar producto.
     */
    public function update(Request $request, Producto $producto): JsonResponse
    {
        $request->validate([
            'codigo' => 'sometimes|string|max:30|unique:productos,codigo,' . $producto->id . ',id,empresa_id,' . $producto->empresa_id,
            'nombre' => 'sometimes|string|max:255',
            'descripcion' => 'nullable|string',
            'familia_id' => 'nullable|exists:familias,id',
            'unidad_medida' => 'sometimes|string|max:10',
            'marca' => 'nullable|string|max:100',
            'modelo' => 'nullable|string|max:100',
            'stock_minimo' => 'integer|min:0',
            'stock_maximo' => 'integer|min:0',
            'ubicacion_fisica' => 'nullable|string|max:255',
            'requiere_lote' => 'boolean',
            'activo' => 'boolean',
            // Campos EPP
            'vida_util_dias' => 'nullable|integer|min:1',
            'dias_alerta_vencimiento' => 'nullable|integer|min:1',
            'requiere_talla' => 'boolean',
            'tallas_disponibles' => 'nullable|string|max:255',
        ]);

        $producto->update($request->only([
            'codigo', 'nombre', 'descripcion', 'familia_id', 'unidad_medida',
            'marca', 'modelo', 'stock_minimo', 'stock_maximo', 'ubicacion_fisica',
            'requiere_lote', 'activo',
            'vida_util_dias', 'dias_alerta_vencimiento', 'requiere_talla', 'tallas_disponibles'
        ]));

        $producto->load('familia');

        return $this->success($producto, 'Producto actualizado exitosamente');
    }

    /**
     * Eliminar producto.
     */
    public function destroy(Producto $producto): JsonResponse
    {
        // Verificar si tiene stock
        $stockTotal = $producto->stockAlmacenes()->sum('stock_actual');
        if ($stockTotal > 0) {
            return $this->error('No se puede eliminar el producto porque tiene stock disponible', 422);
        }

        // Verificar si tiene movimientos
        if ($producto->movimientosDetalle()->count() > 0) {
            return $this->error('No se puede eliminar el producto porque tiene movimientos registrados', 422);
        }

        $producto->delete();

        return $this->success(null, 'Producto eliminado exitosamente');
    }

    /**
     * Obtener kardex del producto.
     */
    public function kardex(Request $request, Producto $producto): JsonResponse
    {
        $query = $producto->kardex()->with(['almacen', 'movimiento']);
        $incluirAnulados = $request->boolean('incluir_anulados', false);

        // Filtro por almacén según rol (almacenero solo ve su almacén)
        $almacenAsignado = $this->getAlmacenAsignado($request);
        if ($almacenAsignado) {
            $query->where('almacen_id', $almacenAsignado);
        } elseif ($request->filled('almacen_id')) {
            // Filtro manual por almacén (para usuarios con acceso total)
            $query->where('almacen_id', $request->almacen_id);
        }

        if ($request->filled('fecha_inicio') && $request->filled('fecha_fin')) {
            $query->whereBetween('fecha', [$request->fecha_inicio, $request->fecha_fin]);
        }

        if (!$incluirAnulados) {
            $query->where(function ($q) {
                $q->whereNull('movimiento_id')
                  ->orWhereHas('movimiento', function ($movQ) {
                      $movQ->where('estado', '!=', Movimiento::ESTADO_ANULADO);
                  });
            });
        }

        $query->orderBy('fecha', 'desc')->orderBy('id', 'desc');

        $perPage = $this->resolvePerPage($request, 20, 100);
        $kardex = $query->paginate($perPage);

        return $this->paginated($kardex);
    }

    /**
     * Obtener stock por almacenes.
     */
    public function stockAlmacenes(Request $request, Producto $producto): JsonResponse
    {
        $producto->load('stockAlmacenes.almacen');

        // Filtrar por almacén según rol
        $almacenAsignado = $this->getAlmacenAsignado($request);
        $stockAlmacenes = $producto->stockAlmacenes;

        if ($almacenAsignado) {
            $stockAlmacenes = $stockAlmacenes->where('almacen_id', $almacenAsignado);
        }

        return $this->success($stockAlmacenes->map(fn($s) => [
            'almacen' => $s->almacen,
            'stock_actual' => $s->stock_actual,
            'stock_minimo' => $s->stock_minimo,
            'stock_maximo' => $s->stock_maximo,
            'costo_promedio' => $s->costo_promedio,
            'valor_total' => round($s->stock_actual * $s->costo_promedio, 2),
            'tiene_stock_bajo' => $s->tieneStockBajo(),
        ]));
    }

    /**
     * Listar productos con stock bajo.
     */
    public function stockBajo(Request $request): JsonResponse
    {
        $query = Producto::with(['familia', 'stockAlmacenes.almacen'])
            ->whereHas('stockAlmacenes', function ($q) {
                $q->whereColumn('stock_actual', '<=', 'stock_minimo')
                  ->where('stock_minimo', '>', 0);
            });

        // Filtro por empresa
        if ($request->user()->empresa_id) {
            $query->where('empresa_id', $request->user()->empresa_id);
        }

        // Filtro por almacén según rol (almacenero solo ve su almacén)
        $almacenAsignado = $this->getAlmacenAsignado($request);
        if ($almacenAsignado) {
            $query->whereHas('stockAlmacenes', function ($q) use ($almacenAsignado) {
                $q->where('almacen_id', $almacenAsignado)
                  ->whereColumn('stock_actual', '<=', 'stock_minimo');
            });
        } elseif ($request->filled('almacen_id')) {
            // Filtro por almacén (para usuarios con acceso total)
            $query->whereHas('stockAlmacenes', function ($q) use ($request) {
                $q->where('almacen_id', $request->almacen_id)
                  ->whereColumn('stock_actual', '<=', 'stock_minimo');
            });
        }

        $productos = $query->get()->map(function ($producto) {
            $stockBajo = $producto->stockAlmacenes
                ->filter(fn($s) => $s->stock_actual <= $s->stock_minimo && $s->stock_minimo > 0);

            return [
                'id' => $producto->id,
                'codigo' => $producto->codigo,
                'nombre' => $producto->nombre,
                'familia' => $producto->familia?->nombre,
                'unidad_medida' => $producto->unidad_medida,
                'almacenes_con_stock_bajo' => $stockBajo->map(fn($s) => [
                    'almacen' => $s->almacen->nombre,
                    'stock_actual' => $s->stock_actual,
                    'stock_minimo' => $s->stock_minimo,
                    'faltante' => $s->stock_minimo - $s->stock_actual,
                ]),
            ];
        });

        return $this->success($productos);
    }

    /**
     * Genera código automático por prefijo de familia (ej: CAP-001).
     */
    private function generarCodigoProducto(int $empresaId, ?int $familiaId): string
    {
        $prefijo = 'PRD';

        if ($familiaId) {
            $familia = Familia::where('empresa_id', $empresaId)
                ->select('codigo', 'nombre')
                ->find($familiaId);

            if ($familia) {
                $candidato = strtoupper(preg_replace('/[^A-Z0-9]/', '', (string) $familia->codigo));

                if (strlen($candidato) < 3) {
                    $iniciales = collect(preg_split('/\s+/', (string) $familia->nombre))
                        ->filter()
                        ->map(fn($w) => strtoupper(substr($w, 0, 1)))
                        ->take(3)
                        ->implode('');
                    $candidato = $iniciales . strtoupper(substr(preg_replace('/[^A-Z0-9]/', '', (string) $familia->nombre), 0, 3));
                }

                $prefijo = substr($candidato ?: 'PRD', 0, 3);
            }
        }

        $base = $prefijo . '-';
        $ultimoCodigo = Producto::where('empresa_id', $empresaId)
            ->where('codigo', 'like', $base . '%')
            ->orderBy('codigo', 'desc')
            ->lockForUpdate()
            ->value('codigo');

        $secuencia = 1;
        if ($ultimoCodigo && preg_match('/-(\d+)$/', $ultimoCodigo, $matches)) {
            $secuencia = ((int) $matches[1]) + 1;
        }

        $codigo = $base . str_pad((string) $secuencia, 3, '0', STR_PAD_LEFT);
        while (Producto::where('empresa_id', $empresaId)->where('codigo', $codigo)->exists()) {
            $secuencia++;
            $codigo = $base . str_pad((string) $secuencia, 3, '0', STR_PAD_LEFT);
        }

        return $codigo;
    }

    // ==================== MÉTODOS PARA IMÁGENES ====================

    /**
     * Listar imágenes de un producto.
     */
    public function imagenes(Producto $producto): JsonResponse
    {
        $imagenes = $producto->imagenes()->get()->map(fn($img) => [
            'id' => $img->id,
            'nombre' => $img->nombre_original,
            'url' => $img->url,
            'tamano' => $img->tamano,
            'tamano_formateado' => $img->tamano_formateado,
            'orden' => $img->orden,
            'principal' => $img->principal,
        ]);

        return $this->success($imagenes);
    }

    /**
     * Subir imágenes a un producto.
     */
    public function subirImagenes(Request $request, Producto $producto): JsonResponse
    {
        $request->validate([
            'imagenes' => 'required|array|min:1|max:4',
            'imagenes.*' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120', // 5MB max
        ], [
            'imagenes.required' => 'Debe seleccionar al menos una imagen',
            'imagenes.max' => 'Máximo 4 imágenes permitidas',
            'imagenes.*.image' => 'El archivo debe ser una imagen',
            'imagenes.*.mimes' => 'Formato permitido: JPG, PNG, GIF, WEBP',
            'imagenes.*.max' => 'La imagen no debe superar 5MB',
        ]);

        // Verificar límite de imágenes
        $imagenesActuales = $producto->imagenes()->count();
        $nuevasImagenes = count($request->file('imagenes'));

        if (($imagenesActuales + $nuevasImagenes) > 4) {
            $disponibles = 4 - $imagenesActuales;
            return $this->error(
                "Solo puede agregar {$disponibles} imagen(es) más. El producto ya tiene {$imagenesActuales} imágenes.",
                422
            );
        }

        $imagenesGuardadas = [];
        $orden = $imagenesActuales;

        foreach ($request->file('imagenes') as $archivo) {
            // Generar nombre único
            $nombreArchivo = Str::uuid() . '.' . $archivo->getClientOriginalExtension();

            // Guardar archivo usando storeAs (método correcto para archivos subidos)
            $ruta = $archivo->storeAs("productos/{$producto->id}", $nombreArchivo, 'public');

            // Crear registro
            $imagen = ProductoImagen::create([
                'producto_id' => $producto->id,
                'nombre_original' => $archivo->getClientOriginalName(),
                'nombre_archivo' => $nombreArchivo,
                'ruta' => $ruta,
                'mime_type' => $archivo->getMimeType(),
                'tamano' => $archivo->getSize(),
                'orden' => $orden++,
                'principal' => $imagenesActuales === 0 && $orden === 1,
            ]);

            $imagenesGuardadas[] = [
                'id' => $imagen->id,
                'nombre' => $imagen->nombre_original,
                'url' => $imagen->url,
                'tamano_formateado' => $imagen->tamano_formateado,
            ];
        }

        return $this->created($imagenesGuardadas, 'Imágenes subidas exitosamente');
    }

    /**
     * Eliminar imagen de un producto.
     */
    public function eliminarImagen(Producto $producto, ProductoImagen $imagen): JsonResponse
    {
        // Verificar que la imagen pertenece al producto
        if ($imagen->producto_id !== $producto->id) {
            return $this->error('La imagen no pertenece a este producto', 403);
        }

        // Si era la imagen principal, asignar otra como principal
        $eraPrincipal = $imagen->principal;

        // Eliminar registro
        $imagen->delete();

        // Reasignar principal si es necesario
        if ($eraPrincipal) {
            $nuevaPrincipal = $producto->imagenes()->orderBy('orden')->first();
            if ($nuevaPrincipal) {
                $nuevaPrincipal->update(['principal' => true]);
            }
        }

        return $this->success(null, 'Imagen eliminada exitosamente');
    }

    /**
     * Establecer imagen principal.
     */
    public function imagenPrincipal(Producto $producto, ProductoImagen $imagen): JsonResponse
    {
        if ($imagen->producto_id !== $producto->id) {
            return $this->error('La imagen no pertenece a este producto', 403);
        }

        // Quitar principal de todas
        $producto->imagenes()->update(['principal' => false]);

        // Establecer nueva principal
        $imagen->update(['principal' => true]);

        return $this->success(null, 'Imagen principal actualizada');
    }

    /**
     * Reordenar imágenes.
     */
    public function reordenarImagenes(Request $request, Producto $producto): JsonResponse
    {
        $request->validate([
            'orden' => 'required|array',
            'orden.*' => 'required|integer|exists:producto_imagenes,id',
        ]);

        foreach ($request->orden as $index => $imagenId) {
            ProductoImagen::where('id', $imagenId)
                ->where('producto_id', $producto->id)
                ->update(['orden' => $index]);
        }

        return $this->success(null, 'Orden actualizado');
    }
}
