<script setup>
import { ref } from 'vue'

const summaryData = ref([
  { label: 'Cantidad en Stock', value: 654, color: 'text-gray-800' },
  { label: 'Por Recibir', value: 100, color: 'text-gray-600' }
])
</script>

<template>
  <div class="bg-white rounded-xl p-6 shadow-sm">
    <h3 class="text-lg font-semibold text-gray-800 mb-4">Resumen de Inventario</h3>

    <div class="space-y-4">
      <div
        v-for="item in summaryData"
        :key="item.label"
        class="flex items-center justify-between py-3 border-b border-gray-100 last:border-0"
      >
        <span class="text-gray-600">{{ item.label }}</span>
        <span class="text-xl font-bold" :class="item.color">{{ item.value }}</span>
      </div>
    </div>

    <!-- Alerta de stock bajo - único acento de color -->
    <div class="mt-4 p-3 bg-[#F0F2FA] border border-[#c5cbea] rounded-lg">
      <div class="flex items-center gap-2">
        <i class="pi pi-exclamation-triangle text-[#1A9A3B]"></i>
        <span class="text-sm text-[#1E2D72]">
          <strong>12 productos</strong> con stock bajo
        </span>
      </div>
    </div>
  </div>
</template>
