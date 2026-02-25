<?php

namespace App\Shared\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Trait para aplicar filtros de datos según el rol del usuario.
 *
 * Roles con acceso total (sin restricciones):
 * - super_admin (Gerencia/TICs)
 * - jefe_logistica
 *
 * Roles con restricciones:
 * - almacenero: Solo ve su almacén asignado
 * - asistente_admin: Solo ve su centro de costo asignado
 * - residente: Solo ve su centro de costo asignado
 * - solicitante: Solo ve su centro de costo asignado
 */
trait FiltrosPorRol
{
    /**
     * Roles que tienen acceso a todos los datos (sin restricciones).
     */
    protected array $rolesAccesoTotal = ['super_admin', 'jefe_logistica'];

    /**
     * Verifica si el usuario tiene acceso total (ve todos los datos).
     */
    protected function tieneAccesoTotal(Request $request): bool
    {
        return $request->user()->hasAnyRole($this->rolesAccesoTotal);
    }

    /**
     * Obtiene el almacén asignado del usuario (null si tiene acceso total).
     */
    protected function getAlmacenAsignado(Request $request): ?int
    {
        if ($this->tieneAccesoTotal($request)) {
            return null;
        }

        return $request->user()->almacen_id;
    }

    /**
     * Obtiene el centro de costo asignado del usuario (null si tiene acceso total).
     */
    protected function getCentroCostoAsignado(Request $request): ?int
    {
        if ($this->tieneAccesoTotal($request)) {
            return null;
        }

        return $request->user()->centro_costo_id;
    }

    /**
     * Aplica filtro por almacén al query.
     * Para almaceneros: solo su almacén asignado.
     * Para otros roles restringidos: todos los almacenes de la empresa.
     */
    protected function aplicarFiltroAlmacen(Builder $query, Request $request, string $columna = 'almacen_id'): Builder
    {
        $almacenId = $this->getAlmacenAsignado($request);

        if ($almacenId) {
            $query->where($columna, $almacenId);
        }

        return $query;
    }

    /**
     * Aplica filtro por almacén en relación stockAlmacenes.
     */
    protected function aplicarFiltroAlmacenEnStock(Builder $query, Request $request): Builder
    {
        $almacenId = $this->getAlmacenAsignado($request);

        if ($almacenId) {
            $query->whereHas('stockAlmacenes', function ($q) use ($almacenId) {
                $q->where('almacen_id', $almacenId);
            });
        }

        return $query;
    }

    /**
     * Aplica filtro por centro de costo al query.
     * Para asistentes/residentes/solicitantes: solo su centro de costo asignado.
     */
    protected function aplicarFiltroCentroCosto(Builder $query, Request $request, string $columna = 'centro_costo_id'): Builder
    {
        $centroCostoId = $this->getCentroCostoAsignado($request);

        if ($centroCostoId) {
            $query->where($columna, $centroCostoId);
        }

        return $query;
    }

    /**
     * Verifica si el usuario puede acceder a un almacén específico.
     */
    protected function puedeAccederAlmacen(Request $request, int $almacenId): bool
    {
        if ($this->tieneAccesoTotal($request)) {
            return true;
        }

        $almacenAsignado = $request->user()->almacen_id;

        // Si no tiene almacén asignado, puede acceder a todos (ej: asistente_admin)
        if (!$almacenAsignado) {
            return true;
        }

        return $almacenAsignado === $almacenId;
    }

    /**
     * Verifica si el usuario puede acceder a un centro de costo específico.
     */
    protected function puedeAccederCentroCosto(Request $request, int $centroCostoId): bool
    {
        if ($this->tieneAccesoTotal($request)) {
            return true;
        }

        $centroCostoAsignado = $request->user()->centro_costo_id;

        // Si no tiene centro de costo asignado, puede acceder a todos (ej: almacenero)
        if (!$centroCostoAsignado) {
            return true;
        }

        return $centroCostoAsignado === $centroCostoId;
    }

    /**
     * Obtiene los almacenes disponibles para el usuario.
     * Útil para llenar selects en el frontend.
     */
    protected function getAlmacenesDisponibles(Request $request): array
    {
        $query = \App\Modules\Administracion\Models\Almacen::query()
            ->where('empresa_id', $request->user()->empresa_id)
            ->where('activo', true);

        $almacenId = $this->getAlmacenAsignado($request);
        if ($almacenId) {
            $query->where('id', $almacenId);
        }

        return $query->get(['id', 'codigo', 'nombre'])->toArray();
    }

    /**
     * Obtiene los centros de costo disponibles para el usuario.
     * Útil para llenar selects en el frontend.
     */
    protected function getCentrosCostoDisponibles(Request $request): array
    {
        $query = \App\Modules\Administracion\Models\CentroCosto::query()
            ->where('empresa_id', $request->user()->empresa_id)
            ->where('activo', true);

        $centroCostoId = $this->getCentroCostoAsignado($request);
        if ($centroCostoId) {
            $query->where('id', $centroCostoId);
        }

        return $query->get(['id', 'codigo', 'nombre'])->toArray();
    }
}
