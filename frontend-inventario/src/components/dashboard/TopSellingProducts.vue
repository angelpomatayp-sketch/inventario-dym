<script setup>
import { ref } from 'vue'
import Dropdown from 'primevue/dropdown'

const selectedPeriod = ref({ name: 'Este mes', value: 'month' })
const periods = ref([
  { name: 'Hoy', value: 'today' },
  { name: 'Esta semana', value: 'week' },
  { name: 'Este mes', value: 'month' },
  { name: 'Este año', value: 'year' }
])

const topProducts = ref([
  {
    id: 1,
    name: 'Casco de Seguridad MSA',
    image: null,
    quantity: 25,
    category: 'EPPs'
  },
  {
    id: 2,
    name: 'Guantes de Nitrilo',
    image: null,
    quantity: 19,
    category: 'EPPs'
  },
  {
    id: 3,
    name: 'Zapatos Punta Acero',
    image: null,
    quantity: 14,
    category: 'EPPs'
  },
  {
    id: 4,
    name: 'Rodamiento SKF 6205',
    image: null,
    quantity: 12,
    category: 'Repuestos'
  },
  {
    id: 5,
    name: 'Aceite Hidráulico Shell',
    image: null,
    quantity: 10,
    category: 'Lubricantes'
  }
])
</script>

<template>
  <div class="bg-white rounded-xl p-6 shadow-sm">
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-lg font-semibold text-gray-800">Productos Más Solicitados</h3>
      <Dropdown
        v-model="selectedPeriod"
        :options="periods"
        optionLabel="name"
        class="w-36 text-sm"
      />
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-4">
      <div
        v-for="product in topProducts"
        :key="product.id"
        class="text-center p-4 rounded-lg border border-gray-100 hover:border-gray-300 hover:shadow-sm transition-all cursor-pointer"
      >
        <!-- Imagen o icono placeholder -->
        <div class="w-16 h-16 mx-auto mb-3 bg-gray-100 rounded-lg flex items-center justify-center">
          <i class="pi pi-box text-2xl text-gray-400"></i>
        </div>

        <!-- Nombre del producto -->
        <p class="text-sm font-medium text-gray-700 truncate" :title="product.name">
          {{ product.name }}
        </p>

        <!-- Cantidad -->
        <p class="text-lg font-bold text-gray-700 mt-1">
          {{ product.quantity }}
          <span class="text-xs font-normal text-gray-500">Qty</span>
        </p>

        <!-- Categoría -->
        <span class="inline-block mt-2 px-2 py-0.5 text-xs bg-gray-100 text-gray-600 rounded-full">
          {{ product.category }}
        </span>
      </div>
    </div>
  </div>
</template>
