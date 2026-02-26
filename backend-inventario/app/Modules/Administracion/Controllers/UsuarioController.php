<?php

namespace App\Modules\Administracion\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Administracion\Models\Usuario;
use App\Shared\Traits\ApiResponse;
use App\Shared\Traits\FiltrosPorRol;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UsuarioController extends Controller
{
    use ApiResponse, FiltrosPorRol;

    /**
     * Listar usuarios.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Usuario::with(['empresa', 'centroCosto', 'almacen', 'roles']);

        // Filtro por empresa (multi-tenancy)
        if ($request->user()->empresa_id) {
            $query->where('empresa_id', $request->user()->empresa_id);
        }

        // Filtro por centro de costo según rol (asistente_admin solo ve usuarios de su centro)
        $centroCostoAsignado = $this->getCentroCostoAsignado($request);
        if ($centroCostoAsignado) {
            $query->where('centro_costo_id', $centroCostoAsignado);
        }

        // Filtros
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('dni', 'like', "%{$search}%");
            });
        }

        if ($request->filled('activo')) {
            $query->where('activo', $request->boolean('activo'));
        }

        if ($request->filled('centro_costo_id')) {
            $query->where('centro_costo_id', $request->centro_costo_id);
        }

        if ($request->filled('rol')) {
            $query->whereHas('roles', function ($q) use ($request) {
                $q->where('name', $request->rol);
            });
        }

        // Ordenamiento
        $sortField = $request->get('sort_field', 'nombre');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortField, $sortOrder);

        // Paginación
        $perPage = $request->get('per_page', 15);
        $usuarios = $query->paginate($perPage);

        // Transformar datos
        $usuarios->getCollection()->transform(function ($usuario) {
            return [
                'id' => $usuario->id,
                'nombre' => $usuario->nombre,
                'email' => $usuario->email,
                'dni' => $usuario->dni,
                'telefono' => $usuario->telefono,
                'activo' => $usuario->activo,
                'empresa_id' => $usuario->empresa_id,
                'centro_costo_id' => $usuario->centro_costo_id,
                'almacen_id' => $usuario->almacen_id,
                'empresa' => $usuario->empresa ? [
                    'id' => $usuario->empresa->id,
                    'razon_social' => $usuario->empresa->razon_social,
                ] : null,
                'centro_costo' => $usuario->centroCosto ? [
                    'id' => $usuario->centroCosto->id,
                    'codigo' => $usuario->centroCosto->codigo,
                    'nombre' => $usuario->centroCosto->nombre,
                ] : null,
                'almacen' => $usuario->almacen ? [
                    'id' => $usuario->almacen->id,
                    'codigo' => $usuario->almacen->codigo,
                    'nombre' => $usuario->almacen->nombre,
                ] : null,
                'roles' => $usuario->roles->map(fn($role) => ['name' => $role->name]),
                'created_at' => $usuario->created_at,
            ];
        });

        return $this->paginated($usuarios);
    }

    /**
     * Crear usuario.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'email' => 'required|email|unique:usuarios,email',
            'password' => 'required|string|min:8|confirmed',
            'dni' => 'nullable|string|max:15',
            'telefono' => 'nullable|string|max:20',
            'empresa_id' => 'required|exists:empresas,id',
            'centro_costo_id' => 'nullable|exists:centros_costos,id',
            'almacen_id' => 'nullable|exists:almacenes,id',
            'roles' => 'required|array|min:1',
            'roles.*' => 'string|exists:roles,name',
            'activo' => 'boolean',
        ], [
            'nombre.required' => 'El nombre es requerido',
            'email.required' => 'El email es requerido',
            'email.unique' => 'Este email ya está registrado',
            'password.required' => 'La contraseña es requerida',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres',
            'password.confirmed' => 'La confirmación de contraseña no coincide',
            'empresa_id.required' => 'La empresa es requerida',
            'roles.required' => 'Debe asignar al menos un rol',
        ]);

        // Si el usuario tiene centro de costo asignado, forzar ese centro
        $centroCostoAsignado = $this->getCentroCostoAsignado($request);
        $centroCostoId = $centroCostoAsignado ?? $request->centro_costo_id;

        $usuario = Usuario::create([
            'nombre' => $request->nombre,
            'email' => $request->email,
            'password' => $request->password,
            'dni' => $request->dni,
            'telefono' => $request->telefono,
            'empresa_id' => $request->empresa_id,
            'centro_costo_id' => $centroCostoId,
            'almacen_id' => $request->almacen_id,
            'activo' => $request->get('activo', true),
        ]);

        // Asignar roles
        $usuario->syncRoles($request->roles);

        $usuario->load(['empresa', 'centroCosto', 'roles']);

        return $this->created([
            'id' => $usuario->id,
            'nombre' => $usuario->nombre,
            'email' => $usuario->email,
            'roles' => $usuario->roles->pluck('name'),
        ], 'Usuario creado exitosamente');
    }

    /**
     * Mostrar usuario.
     */
    public function show(Request $request, Usuario $usuario): JsonResponse
    {
        // Verificar acceso por centro de costo
        if (!$this->puedeAccederUsuario($request, $usuario)) {
            return $this->error('No tiene permiso para ver este usuario', 403);
        }

        $usuario->load(['empresa', 'centroCosto', 'roles.permissions']);

        return $this->success([
            'id' => $usuario->id,
            'nombre' => $usuario->nombre,
            'email' => $usuario->email,
            'dni' => $usuario->dni,
            'telefono' => $usuario->telefono,
            'activo' => $usuario->activo,
            'empresa' => $usuario->empresa ? [
                'id' => $usuario->empresa->id,
                'razon_social' => $usuario->empresa->razon_social,
            ] : null,
            'centro_costo' => $usuario->centroCosto ? [
                'id' => $usuario->centroCosto->id,
                'nombre' => $usuario->centroCosto->nombre,
            ] : null,
            'roles' => $usuario->roles->pluck('name'),
            'permissions' => $usuario->getAllPermissions()->pluck('name'),
            'created_at' => $usuario->created_at,
            'updated_at' => $usuario->updated_at,
        ]);
    }

    /**
     * Actualizar usuario.
     */
    public function update(Request $request, Usuario $usuario): JsonResponse
    {
        // Verificar acceso por centro de costo
        if (!$this->puedeAccederUsuario($request, $usuario)) {
            return $this->error('No tiene permiso para editar este usuario', 403);
        }

        $request->validate([
            'nombre' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:usuarios,email,' . $usuario->id,
            'dni' => 'nullable|string|max:15',
            'telefono' => 'nullable|string|max:20',
            'centro_costo_id' => 'nullable|exists:centros_costos,id',
            'almacen_id' => 'nullable|exists:almacenes,id',
            'roles' => 'sometimes|array|min:1',
            'roles.*' => 'string|exists:roles,name',
            'activo' => 'boolean',
        ]);

        $usuario->update($request->only([
            'nombre', 'email', 'dni', 'telefono', 'centro_costo_id', 'almacen_id', 'activo'
        ]));

        // Actualizar roles si se proporcionan
        if ($request->has('roles')) {
            $usuario->syncRoles($request->roles);
        }

        $usuario->load(['empresa', 'centroCosto', 'roles']);

        return $this->success([
            'id' => $usuario->id,
            'nombre' => $usuario->nombre,
            'email' => $usuario->email,
            'roles' => $usuario->roles->pluck('name'),
        ], 'Usuario actualizado exitosamente');
    }

    /**
     * Eliminar usuario.
     */
    public function destroy(Request $request, Usuario $usuario): JsonResponse
    {
        // Verificar acceso por centro de costo
        if (!$this->puedeAccederUsuario($request, $usuario)) {
            return $this->error('No tiene permiso para eliminar este usuario', 403);
        }

        // No permitir eliminar el propio usuario
        if ($usuario->id === $request->user()->id) {
            return $this->error('No puedes eliminar tu propio usuario', 422);
        }

        $usuario->delete();

        return $this->success(null, 'Usuario eliminado exitosamente');
    }

    /**
     * Activar/Desactivar usuario.
     */
    public function toggleActivo(Request $request, Usuario $usuario): JsonResponse
    {
        // Verificar acceso por centro de costo
        if (!$this->puedeAccederUsuario($request, $usuario)) {
            return $this->error('No tiene permiso para modificar este usuario', 403);
        }

        // No permitir desactivar el propio usuario
        if ($usuario->id === $request->user()->id) {
            return $this->error('No puedes desactivar tu propio usuario', 422);
        }

        $usuario->update(['activo' => !$usuario->activo]);

        $estado = $usuario->activo ? 'activado' : 'desactivado';

        return $this->success([
            'id' => $usuario->id,
            'activo' => $usuario->activo,
        ], "Usuario {$estado} exitosamente");
    }

    /**
     * Cambiar contraseña de usuario (admin).
     */
    public function cambiarPassword(Request $request, Usuario $usuario): JsonResponse
    {
        // Verificar acceso por centro de costo
        if (!$this->puedeAccederUsuario($request, $usuario)) {
            return $this->error('No tiene permiso para modificar este usuario', 403);
        }

        $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ], [
            'password.required' => 'La contraseña es requerida',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres',
            'password.confirmed' => 'La confirmación de contraseña no coincide',
        ]);

        $usuario->update([
            'password' => $request->password,
        ]);

        return $this->success(null, 'Contraseña actualizada exitosamente');
    }

    /**
     * Subir kardex físico escaneado en PDF.
     */
    public function subirKardexPdf(Request $request, Usuario $usuario): JsonResponse
    {
        $request->validate([
            'kardex_pdf' => ['required', 'file', 'mimes:pdf', 'max:20480'],
        ], [
            'kardex_pdf.required' => 'Seleccione un archivo PDF.',
            'kardex_pdf.mimes'    => 'El archivo debe ser PDF.',
            'kardex_pdf.max'      => 'El PDF no debe superar 20 MB.',
        ]);

        if ($usuario->kardex_pdf_ruta && Storage::disk('public')->exists($usuario->kardex_pdf_ruta)) {
            Storage::disk('public')->delete($usuario->kardex_pdf_ruta);
        }

        $archivo = $request->file('kardex_pdf');
        $nombreArchivo = Str::uuid() . '.pdf';
        $directorio = "kardex/usuarios/{$usuario->id}";

        Storage::disk('public')->putFileAs($directorio, $archivo, $nombreArchivo);

        $usuario->update([
            'kardex_pdf_ruta'           => "{$directorio}/{$nombreArchivo}",
            'kardex_pdf_nombre_original' => $archivo->getClientOriginalName(),
            'kardex_pdf_tamano'          => $archivo->getSize(),
            'kardex_pdf_subido_en'       => now(),
        ]);

        return $this->success(
            $usuario->only(['tiene_kardex', 'kardex_pdf_nombre_original', 'kardex_pdf_tamano', 'kardex_pdf_subido_en']),
            'Kardex PDF subido exitosamente.'
        );
    }

    /**
     * Descargar / visualizar kardex PDF de usuario.
     */
    public function descargarKardexPdf(Usuario $usuario): Response
    {
        if (!$usuario->kardex_pdf_ruta) {
            abort(404, 'Este usuario no tiene kardex PDF.');
        }

        $path = Storage::disk('public')->path($usuario->kardex_pdf_ruta);

        if (!file_exists($path)) {
            abort(404, 'Archivo no encontrado en el servidor.');
        }

        return response()->file($path, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $usuario->kardex_pdf_nombre_original . '"',
        ]);
    }

    /**
     * Eliminar kardex PDF de usuario.
     */
    public function eliminarKardexPdf(Usuario $usuario): JsonResponse
    {
        if (!$usuario->kardex_pdf_ruta) {
            return $this->error('No hay kardex PDF para eliminar.', 404);
        }

        if (Storage::disk('public')->exists($usuario->kardex_pdf_ruta)) {
            Storage::disk('public')->delete($usuario->kardex_pdf_ruta);
        }

        $directorio = "kardex/usuarios/{$usuario->id}";
        if (empty(Storage::disk('public')->files($directorio))) {
            Storage::disk('public')->deleteDirectory($directorio);
        }

        $usuario->update([
            'kardex_pdf_ruta'           => null,
            'kardex_pdf_nombre_original' => null,
            'kardex_pdf_tamano'          => null,
            'kardex_pdf_subido_en'       => null,
        ]);

        return $this->success(null, 'Kardex PDF eliminado.');
    }

    /**
     * Verifica si el usuario actual puede acceder a un usuario específico.
     * Super admin y jefe logística pueden acceder a todos.
     * Otros roles solo pueden acceder a usuarios de su centro de costo.
     */
    protected function puedeAccederUsuario(Request $request, Usuario $usuario): bool
    {
        // Si tiene acceso total, puede ver cualquier usuario
        if ($this->tieneAccesoTotal($request)) {
            return true;
        }

        $centroCostoAsignado = $request->user()->centro_costo_id;

        // Si no tiene centro de costo asignado, puede acceder a todos
        if (!$centroCostoAsignado) {
            return true;
        }

        // Solo puede acceder a usuarios del mismo centro de costo
        return $usuario->centro_costo_id === $centroCostoAsignado;
    }
}
