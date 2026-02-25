<script setup>
import { ref, computed } from 'vue'
import { Doughnut } from 'vue-chartjs'
import { Chart as ChartJS, ArcElement, Tooltip, Legend } from 'chart.js'

ChartJS.register(ArcElement, Tooltip, Legend)

const productStats = ref([
  { label: 'Stock Bajo', value: 30, color: '#d97706' },
  { label: 'Total Items', value: 130, color: '#4b5563' },
  { label: 'Total Variantes', value: 200, color: '#6b7280' }
])

const chartData = computed(() => ({
  labels: ['Activos', 'Inactivos'],
  datasets: [
    {
      data: [45, 55],
      backgroundColor: ['#4b5563', '#e5e7eb'],
      borderWidth: 0,
      cutout: '75%'
    }
  ]
}))

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      display: false
    },
    tooltip: {
      enabled: true
    }
  }
}
</script>

<template>
  <div class="bg-white rounded-xl p-6 shadow-sm">
    <h3 class="text-lg font-semibold text-gray-800 mb-4">Detalles de Productos</h3>

    <div class="flex items-center gap-8">
      <!-- Lista de stats -->
      <div class="flex-1 space-y-3">
        <div
          v-for="stat in productStats"
          :key="stat.label"
          class="flex items-center justify-between"
        >
          <span class="text-gray-600 text-sm">{{ stat.label }}</span>
          <span class="font-bold" :style="{ color: stat.color }">{{ stat.value }}</span>
        </div>
      </div>

      <!-- Gráfico circular -->
      <div class="w-32 h-32 relative">
        <Doughnut :data="chartData" :options="chartOptions" />
        <div class="absolute inset-0 flex items-center justify-center">
          <div class="text-center">
            <span class="text-2xl font-bold text-gray-700">45</span>
            <p class="text-xs text-gray-500">Activos</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Variantes label -->
    <div class="mt-4 flex items-center gap-2 text-sm text-gray-500">
      <div class="w-3 h-3 rounded-full bg-gray-500"></div>
      <span>Variantes Activas</span>
    </div>
  </div>
</template>
