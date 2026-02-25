<script setup>
import { ref } from 'vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'

const recentOrders = ref([
  {
    id: 'OC-2024-001',
    proveedor: 'Ferretería Industrial SAC',
    fecha: '2024-01-15',
    items: 12,
    total: 4520.00,
    estado: 'completado'
  },
  {
    id: 'OC-2024-002',
    proveedor: 'Suministros Mineros EIRL',
    fecha: '2024-01-14',
    items: 8,
    total: 2890.50,
    estado: 'pendiente'
  },
  {
    id: 'OC-2024-003',
    proveedor: 'EPP Solutions Peru',
    fecha: '2024-01-13',
    items: 25,
    total: 8750.00,
    estado: 'en_proceso'
  },
  {
    id: 'OC-2024-004',
    proveedor: 'Herramientas del Norte',
    fecha: '2024-01-12',
    items: 5,
    total: 1230.00,
    estado: 'completado'
  },
  {
    id: 'OC-2024-005',
    proveedor: 'Distribuidora Técnica SAC',
    fecha: '2024-01-11',
    items: 15,
    total: 5680.75,
    estado: 'cancelado'
  }
])

const getEstadoSeverity = (estado) => {
  const severities = {
    pendiente: 'warn',
    en_proceso: 'info',
    completado: 'success',
    cancelado: 'danger'
  }
  return severities[estado] || 'secondary'
}

const getEstadoLabel = (estado) => {
  const labels = {
    pendiente: 'Pendiente',
    en_proceso: 'En Proceso',
    completado: 'Completado',
    cancelado: 'Cancelado'
  }
  return labels[estado] || estado
}

const formatCurrency = (value) => {
  return `S/ ${value.toLocaleString('es-PE', { minimumFractionDigits: 2 })}`
}

const formatDate = (dateStr) => {
  const date = new Date(dateStr)
  return date.toLocaleDateString('es-PE', { day: '2-digit', month: 'short', year: 'numeric' })
}
</script>

<template>
  <div class="bg-white rounded-xl p-6 shadow-sm">
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-lg font-semibold text-gray-800">Órdenes Recientes</h3>
      <button class="text-sm text-blue-600 hover:text-blue-700 font-medium">
        Ver todas
      </button>
    </div>

    <DataTable
      :value="recentOrders"
      :rows="5"
      stripedRows
      class="text-sm"
    >
      <Column field="id" header="Código" style="width: 15%">
        <template #body="{ data }">
          <span class="font-medium text-gray-800">{{ data.id }}</span>
        </template>
      </Column>

      <Column field="proveedor" header="Proveedor" style="width: 30%">
        <template #body="{ data }">
          <span class="text-gray-600">{{ data.proveedor }}</span>
        </template>
      </Column>

      <Column field="fecha" header="Fecha" style="width: 15%">
        <template #body="{ data }">
          <span class="text-gray-500">{{ formatDate(data.fecha) }}</span>
        </template>
      </Column>

      <Column field="items" header="Items" style="width: 10%" class="text-center">
        <template #body="{ data }">
          <span class="text-gray-600">{{ data.items }}</span>
        </template>
      </Column>

      <Column field="total" header="Total" style="width: 15%">
        <template #body="{ data }">
          <span class="font-medium text-gray-800">{{ formatCurrency(data.total) }}</span>
        </template>
      </Column>

      <Column field="estado" header="Estado" style="width: 15%">
        <template #body="{ data }">
          <Tag
            :value="getEstadoLabel(data.estado)"
            :severity="getEstadoSeverity(data.estado)"
          />
        </template>
      </Column>
    </DataTable>
  </div>
</template>
