<script setup>
import { ref } from 'vue'
import Dropdown from 'primevue/dropdown'

const selectedPeriod = ref({ name: 'Este mes', value: 'month' })
const periods = ref([
  { name: 'Esta semana', value: 'week' },
  { name: 'Este mes', value: 'month' },
  { name: 'Este año', value: 'year' }
])

const orderData = ref({
  quantity: 250,
  totalCost: 12574.00
})
</script>

<template>
  <div class="bg-white rounded-xl p-6 shadow-sm">
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-lg font-semibold text-gray-800">Órdenes de Compra</h3>
      <Dropdown
        v-model="selectedPeriod"
        :options="periods"
        optionLabel="name"
        class="w-32 text-sm"
      />
    </div>

    <div class="space-y-6">
      <!-- Cantidad ordenada -->
      <div class="text-center py-4 border-b border-gray-100">
        <p class="text-sm text-gray-500 mb-1">Cantidad Ordenada</p>
        <p class="text-3xl font-bold text-gray-800">{{ orderData.quantity }}</p>
      </div>

      <!-- Costo total -->
      <div class="text-center py-4">
        <p class="text-sm text-gray-500 mb-1">Costo Total</p>
        <p class="text-3xl font-bold text-gray-700">
          S/ {{ orderData.totalCost.toLocaleString('es-PE', { minimumFractionDigits: 2 }) }}
        </p>
      </div>

      <!-- Indicadores adicionales -->
      <div class="grid grid-cols-2 gap-4 pt-4 border-t border-gray-100">
        <div class="text-center">
          <p class="text-2xl font-bold text-amber-600">8</p>
          <p class="text-xs text-gray-500">Pendientes</p>
        </div>
        <div class="text-center">
          <p class="text-2xl font-bold text-gray-600">15</p>
          <p class="text-xs text-gray-500">Completadas</p>
        </div>
      </div>
    </div>
  </div>
</template>
