<?php

namespace App\Core\Authentication\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Administracion\Models\Usuario;
use App\Shared\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    use ApiResponse;

    /**
     * Iniciar sesión.
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ], [
            'email.required' => 'El correo electrónico es requerido',
            'email.email' => 'El correo electrónico no es válido',
            'password.required' => 'La contraseña es requerida',
        ]);

        $usuario = Usuario::where('email', $request->email)->first();

        if (!$usuario || !Hash::check($request->password, $usuario->password)) {
            return $this->unauthorized('Credenciales incorrectas');
        }

        if (!$usuario->activo) {
            return $this->forbidden('Tu cuenta está desactivada. Contacta al administrador.');
        }

        if ($usuario->empresa && !$usuario->empresa->activo) {
            return $this->forbidden('La empresa asociada está desactivada.');
        }

        // Revocar tokens anteriores (opcional, para single session)
        // $usuario->tokens()->delete();

        // Crear nuevo token
        $token = $usuario->createToken('auth-token')->plainTextToken;

        // Cargar relaciones necesarias
        $usuario->load(['empresa', 'centroCosto', 'almacen']);

        return $this->success([
            'token' => $token,
            'user' => [
                'id' => $usuario->id,
                'nombre' => $usuario->nombre,
                'email' => $usuario->email,
                'dni' => $usuario->dni,
                'telefono' => $usuario->telefono,
                'empresa_id' => $usuario->empresa_id,
                'empresa' => $usuario->empresa ? [
                    'id' => $usuario->empresa->id,
                    'razon_social' => $usuario->empresa->razon_social,
                    'ruc' => $usuario->empresa->ruc,
                ] : null,
                'centro_costo_id' => $usuario->centro_costo_id,
                'centro_costo' => $usuario->centroCosto ? [
                    'id' => $usuario->centroCosto->id,
                    'codigo' => $usuario->centroCosto->codigo,
                    'nombre' => $usuario->centroCosto->nombre,
                ] : null,
                'almacen_id' => $usuario->almacen_id,
                'almacen' => $usuario->almacen ? [
                    'id' => $usuario->almacen->id,
                    'codigo' => $usuario->almacen->codigo,
                    'nombre' => $usuario->almacen->nombre,
                ] : null,
                'roles' => $usuario->getRoleNames(),
                'permissions' => $usuario->getAllPermissions()->pluck('name'),
            ],
        ], 'Inicio de sesión exitoso');
    }

    /**
     * Cerrar sesión.
     */
    public function logout(Request $request): JsonResponse
    {
        // Revocar el token actual
        $request->user()->currentAccessToken()->delete();

        return $this->success(null, 'Sesión cerrada exitosamente');
    }

    /**
     * Cerrar todas las sesiones.
     */
    public function logoutAll(Request $request): JsonResponse
    {
        // Revocar todos los tokens del usuario
        $request->user()->tokens()->delete();

        return $this->success(null, 'Todas las sesiones han sido cerradas');
    }

    /**
     * Obtener usuario autenticado.
     */
    public function me(Request $request): JsonResponse
    {
        $usuario = $request->user();
        $usuario->load(['empresa', 'centroCosto', 'almacen']);

        return $this->success([
            'id' => $usuario->id,
            'nombre' => $usuario->nombre,
            'email' => $usuario->email,
            'dni' => $usuario->dni,
            'telefono' => $usuario->telefono,
            'empresa_id' => $usuario->empresa_id,
            'empresa' => $usuario->empresa ? [
                'id' => $usuario->empresa->id,
                'razon_social' => $usuario->empresa->razon_social,
                'ruc' => $usuario->empresa->ruc,
            ] : null,
            'centro_costo_id' => $usuario->centro_costo_id,
            'centro_costo' => $usuario->centroCosto ? [
                'id' => $usuario->centroCosto->id,
                'codigo' => $usuario->centroCosto->codigo,
                'nombre' => $usuario->centroCosto->nombre,
            ] : null,
            'almacen_id' => $usuario->almacen_id,
            'almacen' => $usuario->almacen ? [
                'id' => $usuario->almacen->id,
                'codigo' => $usuario->almacen->codigo,
                'nombre' => $usuario->almacen->nombre,
            ] : null,
            'roles' => $usuario->getRoleNames(),
            'permissions' => $usuario->getAllPermissions()->pluck('name'),
        ]);
    }

    /**
     * Cambiar contraseña.
     */
    public function changePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ], [
            'current_password.required' => 'La contraseña actual es requerida',
            'password.required' => 'La nueva contraseña es requerida',
            'password.min' => 'La nueva contraseña debe tener al menos 8 caracteres',
            'password.confirmed' => 'La confirmación de contraseña no coincide',
        ]);

        $usuario = $request->user();

        if (!Hash::check($request->current_password, $usuario->password)) {
            return $this->validationError(
                ['current_password' => ['La contraseña actual es incorrecta']],
                'Error de validación'
            );
        }

        $usuario->update([
            'password' => $request->password,
        ]);

        return $this->success(null, 'Contraseña actualizada exitosamente');
    }

    /**
     * Verificar contraseña (para confirmación de acciones sensibles).
     */
    public function verifyPassword(Request $request): JsonResponse
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        $usuario = $request->user();

        if (!Hash::check($request->password, $usuario->password)) {
            return $this->error('Contraseña incorrecta', 400);
        }

        return $this->success(null, 'Contraseña verificada');
    }

    /**
     * Actualizar perfil del usuario.
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $usuario = $request->user();

        $request->validate([
            'nombre' => 'sometimes|string|max:255',
            'telefono' => 'nullable|string|max:20',
        ], [
            'nombre.max' => 'El nombre no puede exceder 255 caracteres',
            'telefono.max' => 'El teléfono no puede exceder 20 caracteres',
        ]);

        $usuario->update($request->only(['nombre', 'telefono']));

        return $this->success([
            'id' => $usuario->id,
            'nombre' => $usuario->nombre,
            'email' => $usuario->email,
            'telefono' => $usuario->telefono,
        ], 'Perfil actualizado exitosamente');
    }

    /**
     * Refrescar información del usuario.
     */
    public function refresh(Request $request): JsonResponse
    {
        $usuario = $request->user();
        $usuario->load(['empresa', 'centroCosto']);

        // Refrescar permisos por si cambiaron
        $usuario->load('roles.permissions');

        return $this->success([
            'user' => [
                'id' => $usuario->id,
                'nombre' => $usuario->nombre,
                'email' => $usuario->email,
                'roles' => $usuario->getRoleNames(),
                'permissions' => $usuario->getAllPermissions()->pluck('name'),
            ],
        ]);
    }
}
