<script setup>
import { ref, onMounted, computed } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useAuthStore } from '@/stores/auth'
import api from '@/services/api'

// PrimeVue Components
import Button from 'primevue/button'
import Card from 'primevue/card'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import Divider from 'primevue/divider'
import ProgressSpinner from 'primevue/progressspinner'

// Chart.js
import { Line, Bar, Doughnut, Pie } from 'vue-chartjs'
import {
  Chart as ChartJS,
  Title,
  Tooltip,
  Legend,
  BarElement,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  ArcElement,
  Filler
} from 'chart.js'

// Registrar componentes de Chart.js
ChartJS.register(
  Title,
  Tooltip,
  Legend,
  BarElement,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  ArcElement,
  Filler
)

const toast = useToast()
const authStore = useAuthStore()

// Loading state
const loading = ref(true)
const lastUpdate = ref(null)

// Dashboard data
const dashboardData = ref({
  valor_inventario: 0,
  total_productos: 0,
  productos_stock_bajo: 0,
  movimientos_mes: 0,
  requisiciones_pendientes: 0,
  ordenes_por_recibir: 0,
  consumo_mes_actual: 0,
  consumo_mes_anterior: 0,
  variacion_consumo: 0
})

// Estadisticas adicionales
const eppsStats = ref({
  vigentes: 0,
  por_vencer: 0,
  vencidos: 0
})

const stockBajoProductos = ref([])

// Datos de graficos
const graficosData = ref({
  consumo_mensual: [],
  movimientos_por_tipo: {},
  top_productos: [],
  consumo_por_centro_costo: [],
  requisiciones_por_estado: {},
  stock_por_familia: []
})

// Stats cards para la primera fila
const statsCards = computed(() => [
  {
    title: 'Valor Inventario',
    value: formatCurrency(dashboardData.value.valor_inventario),
    icon: 'pi-warehouse',
    iconBg: 'bg-blue-100',
    iconColor: 'text-blue-600'
  },
  {
    title: 'Total Productos',
    value: dashboardData.value.total_productos,
    icon: 'pi-box',
    iconBg: 'bg-green-100',
    iconColor: 'text-green-600'
  },
  {
    title: 'Stock Bajo',
    value: dashboardData.value.productos_stock_bajo,
    icon: 'pi-exclamation-triangle',
    iconBg: 'bg-orange-100',
    iconColor: 'text-orange-600',
    alert: dashboardData.value.productos_stock_bajo > 0
  },
  {
    title: 'Movimientos del Mes',
    value: dashboardData.value.movimientos_mes,
    icon: 'pi-arrow-right-arrow-left',
    iconBg: 'bg-purple-100',
    iconColor: 'text-purple-600'
  }
])

// Stats cards segunda fila
const operationCards = computed(() => [
  {
    title: 'Requisiciones Pendientes',
    value: dashboardData.value.requisiciones_pendientes,
    icon: 'pi-file-edit',
    iconBg: 'bg-yellow-100',
    iconColor: 'text-yellow-600'
  },
  {
    title: 'Ordenes por Recibir',
    value: dashboardData.value.ordenes_por_recibir,
    icon: 'pi-truck',
    iconBg: 'bg-cyan-100',
    iconColor: 'text-cyan-600'
  },
  {
    title: 'Consumo del Mes',
    value: formatCurrency(dashboardData.value.consumo_mes_actual),
    icon: 'pi-chart-line',
    iconBg: 'bg-indigo-100',
    iconColor: 'text-indigo-600',
    trend: dashboardData.value.variacion_consumo,
    trendLabel: 'vs mes anterior'
  },
  {
    title: 'EPPs Por Vencer',
    value: eppsStats.value.por_vencer,
    icon: 'pi-shield',
    iconBg: 'bg-red-100',
    iconColor: 'text-red-600',
    alert: eppsStats.value.por_vencer > 0
  }
])

const almaceneroScopeLabel = computed(() => {
  const almacenId = authStore.user?.almacen_id
  if (!almacenId) return null
  const almacenNombre = authStore.user?.almacen?.nombre || `ID ${almacenId}`
  return `Vista filtrada por almacén: ${almacenNombre}`
})

// Configuracion de graficos
const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'bottom',
      labels: {
        usePointStyle: true,
        padding: 15,
        font: { size: 11 }
      }
    }
  }
}

const lineChartOptions = {
  ...chartOptions,
  scales: {
    y: {
      beginAtZero: true,
      ticks: {
        callback: (value) => 'S/ ' + value.toLocaleString()
      }
    }
  },
  plugins: {
    ...chartOptions.plugins,
    tooltip: {
      callbacks: {
        label: (context) => 'S/ ' + context.parsed.y.toLocaleString()
      }
    }
  }
}

const barChartOptions = {
  ...chartOptions,
  indexAxis: 'y',
  scales: {
    x: {
      beginAtZero: true
    }
  }
}

// Datos para grafico de consumo mensual
const consumoMensualChartData = computed(() => ({
  labels: graficosData.value.consumo_mensual.map(d => d.mes_corto),
  datasets: [{
    label: 'Consumo Mensual',
    data: graficosData.value.consumo_mensual.map(d => d.valor),
    borderColor: '#6366f1',
    backgroundColor: 'rgba(99, 102, 241, 0.1)',
    fill: true,
    tension: 0.4,
    pointRadius: 4,
    pointHoverRadius: 6
  }]
}))

// Datos para grafico de top productos
const topProductosChartData = computed(() => ({
  labels: graficosData.value.top_productos.map(p =>
    p.nombre.length > 25 ? p.nombre.substring(0, 25) + '...' : p.nombre
  ),
  datasets: [{
    label: 'Cantidad Consumida',
    data: graficosData.value.top_productos.map(p => p.cantidad),
    backgroundColor: [
      '#6366f1', '#22c55e', '#f59e0b', '#ef4444', '#8b5cf6',
      '#06b6d4', '#ec4899', '#84cc16', '#f97316', '#14b8a6'
    ],
    borderRadius: 4
  }]
}))

// Datos para grafico de stock por familia
const stockFamiliaChartData = computed(() => ({
  labels: graficosData.value.stock_por_familia.map(f => f.nombre),
  datasets: [{
    data: graficosData.value.stock_por_familia.map(f => f.valor),
    backgroundColor: [
      '#6366f1', '#22c55e', '#f59e0b', '#ef4444', '#8b5cf6',
      '#06b6d4', '#ec4899', '#14b8a6'
    ],
    borderWidth: 0
  }]
}))

// Datos para grafico de consumo por centro de costo
const consumoCentroCostoChartData = computed(() => ({
  labels: graficosData.value.consumo_por_centro_costo.map(c => c.nombre),
  datasets: [{
    data: graficosData.value.consumo_por_centro_costo.map(c => c.valor),
    backgroundColor: [
      '#22c55e', '#6366f1', '#f59e0b', '#ef4444', '#8b5cf6',
      '#06b6d4', '#ec4899', '#14b8a6'
    ],
    borderWidth: 0
  }]
}))

// Formatters
const formatCurrency = (value) => {
  if (!value) return 'S/ 0.00'
  return 'S/ ' + Number(value).toLocaleString('es-PE', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  })
}

const formatDate = (date) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('es-PE', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
  })
}

const formatTime = (date) => {
  if (!date) return ''
  return new Date(date).toLocaleTimeString('es-PE', {
    hour: '2-digit',
    minute: '2-digit'
  })
}

// Cargar datos del dashboard
const cargarDashboard = async () => {
  loading.value = true
  try {
    const [dashRes, stockBajoRes, eppsRes, graficosRes] = await Promise.all([
      api.get('/reportes/dashboard'),
      api.get('/reportes/stock-bajo'),
      api.get('/epps/estadisticas').catch(() => ({ data: { data: {} } })),
      api.get('/reportes/dashboard/graficos').catch(() => ({ data: { data: {} } }))
    ])

    dashboardData.value = dashRes.data.data

    // Stock bajo - top 5
    const stockBajo = stockBajoRes.data.data
    stockBajoProductos.value = (stockBajo.productos || []).slice(0, 5)

    // EPPs stats
    if (eppsRes.data.data) {
      eppsStats.value = {
        vigentes: eppsRes.data.data.vigentes || 0,
        por_vencer: eppsRes.data.data.por_vencer || 0,
        vencidos: eppsRes.data.data.vencidos || 0
      }
    }

    // Datos de graficos
    if (graficosRes.data.data) {
      graficosData.value = graficosRes.data.data
    }

    lastUpdate.value = new Date()
  } catch (error) {
    console.error('Error cargando dashboard:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'No se pudieron cargar los datos del dashboard',
      life: 3000
    })
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  cargarDashboard()
})
</script>

<template>
  <div class="dashboard-view p-4">
    <!-- Header -->
    <div class="flex justify-content-between align-items-center mb-4">
      <div>
        <h1 class="text-2xl font-bold text-800 m-0">Dashboard</h1>
        <p class="text-500 mt-1">Resumen general del sistema de inventario</p>
        <p v-if="almaceneroScopeLabel" class="text-sm text-blue-700 mt-2 font-medium">
          {{ almaceneroScopeLabel }}
        </p>
      </div>
      <div class="flex align-items-center gap-3">
        <span class="text-sm text-500" v-if="lastUpdate">
          Actualizado: {{ formatDate(lastUpdate) }} {{ formatTime(lastUpdate) }}
        </span>
        <Button
          icon="pi pi-refresh"
          rounded
          text
          @click="cargarDashboard"
          :loading="loading"
          v-tooltip="'Actualizar datos'"
        />
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex justify-content-center align-items-center" style="min-height: 400px">
      <ProgressSpinner />
    </div>

    <div v-else class="grid">
      <!-- Primera fila: Stats principales -->
      <div class="col-12">
        <div class="grid">
          <div v-for="stat in statsCards" :key="stat.title" class="col-12 md:col-6 lg:col-3">
            <Card class="h-full" :class="{ 'border-orange-500 border-2': stat.alert }">
              <template #content>
                <div class="flex align-items-center justify-content-between">
                  <div>
                    <p class="text-500 text-sm mb-2">{{ stat.title }}</p>
                    <p class="text-2xl font-bold text-800 m-0">{{ stat.value }}</p>
                  </div>
                  <div :class="[stat.iconBg, 'p-3 border-round-xl']">
                    <i :class="['pi', stat.icon, stat.iconColor, 'text-2xl']"></i>
                  </div>
                </div>
              </template>
            </Card>
          </div>
        </div>
      </div>

      <!-- Segunda fila: Stats operacionales -->
      <div class="col-12">
        <div class="grid">
          <div v-for="stat in operationCards" :key="stat.title" class="col-12 md:col-6 lg:col-3">
            <Card class="h-full" :class="{ 'border-red-500 border-2': stat.alert }">
              <template #content>
                <div class="flex align-items-center justify-content-between">
                  <div>
                    <p class="text-500 text-sm mb-2">{{ stat.title }}</p>
                    <p class="text-2xl font-bold text-800 m-0">{{ stat.value }}</p>
                    <div v-if="stat.trend !== undefined" class="flex align-items-center gap-1 mt-1">
                      <i :class="['pi', stat.trend >= 0 ? 'pi-arrow-up text-red-500' : 'pi-arrow-down text-green-500', 'text-xs']"></i>
                      <span :class="[stat.trend >= 0 ? 'text-red-500' : 'text-green-500', 'text-xs']">
                        {{ Math.abs(stat.trend) }}%
                      </span>
                      <span class="text-400 text-xs">{{ stat.trendLabel }}</span>
                    </div>
                  </div>
                  <div :class="[stat.iconBg, 'p-3 border-round-xl']">
                    <i :class="['pi', stat.icon, stat.iconColor, 'text-2xl']"></i>
                  </div>
                </div>
              </template>
            </Card>
          </div>
        </div>
      </div>

      <!-- Graficos fila 1: Consumo Mensual y Top Productos -->
      <div class="col-12 lg:col-6">
        <Card>
          <template #title>
            <div class="flex align-items-center gap-2">
              <i class="pi pi-chart-line text-indigo-500"></i>
              <span>Consumo Mensual</span>
            </div>
          </template>
          <template #subtitle>
            <span class="text-sm">Ultimos 6 meses</span>
          </template>
          <template #content>
            <div style="height: 280px">
              <Line
                v-if="graficosData.consumo_mensual.length > 0"
                :data="consumoMensualChartData"
                :options="lineChartOptions"
              />
              <div v-else class="flex align-items-center justify-content-center h-full text-500">
                Sin datos disponibles
              </div>
            </div>
          </template>
        </Card>
      </div>

      <div class="col-12 lg:col-6">
        <Card>
          <template #title>
            <div class="flex align-items-center gap-2">
              <i class="pi pi-chart-bar text-green-500"></i>
              <span>Top 10 Productos Consumidos</span>
            </div>
          </template>
          <template #subtitle>
            <span class="text-sm">Mes actual</span>
          </template>
          <template #content>
            <div style="height: 280px">
              <Bar
                v-if="graficosData.top_productos.length > 0"
                :data="topProductosChartData"
                :options="barChartOptions"
              />
              <div v-else class="flex align-items-center justify-content-center h-full text-500">
                Sin datos disponibles
              </div>
            </div>
          </template>
        </Card>
      </div>

      <!-- Graficos fila 2: Stock por Familia y Consumo por Centro de Costo -->
      <div class="col-12 lg:col-6">
        <Card>
          <template #title>
            <div class="flex align-items-center gap-2">
              <i class="pi pi-chart-pie text-purple-500"></i>
              <span>Valor Inventario por Familia</span>
            </div>
          </template>
          <template #content>
            <div style="height: 280px" class="flex align-items-center justify-content-center">
              <Doughnut
                v-if="graficosData.stock_por_familia.length > 0"
                :data="stockFamiliaChartData"
                :options="chartOptions"
              />
              <div v-else class="text-500">
                Sin datos disponibles
              </div>
            </div>
          </template>
        </Card>
      </div>

      <div class="col-12 lg:col-6">
        <Card>
          <template #title>
            <div class="flex align-items-center gap-2">
              <i class="pi pi-sitemap text-cyan-500"></i>
              <span>Consumo por Centro de Costo</span>
            </div>
          </template>
          <template #subtitle>
            <span class="text-sm">Mes actual</span>
          </template>
          <template #content>
            <div style="height: 280px" class="flex align-items-center justify-content-center">
              <Pie
                v-if="graficosData.consumo_por_centro_costo.length > 0"
                :data="consumoCentroCostoChartData"
                :options="chartOptions"
              />
              <div v-else class="text-500">
                Sin datos disponibles
              </div>
            </div>
          </template>
        </Card>
      </div>

      <!-- Productos con stock bajo y EPPs -->
      <div class="col-12 lg:col-6">
        <Card>
          <template #title>
            <div class="flex align-items-center gap-2">
              <i class="pi pi-exclamation-triangle text-orange-500"></i>
              <span>Productos con Stock Bajo</span>
            </div>
          </template>
          <template #content>
            <DataTable
              :value="stockBajoProductos"
              size="small"
              stripedRows
              emptyMessage="No hay productos con stock bajo"
            >
              <Column field="codigo" header="Codigo" style="width: 100px" />
              <Column field="nombre" header="Producto" />
              <Column field="stock_actual" header="Stock" style="width: 80px; text-align: center">
                <template #body="{ data }">
                  <Tag :severity="data.stock_actual <= 0 ? 'danger' : 'warn'" :value="data.stock_actual" />
                </template>
              </Column>
              <Column field="stock_minimo" header="Minimo" style="width: 80px; text-align: center" />
              <Column field="diferencia" header="Falta" style="width: 80px; text-align: center">
                <template #body="{ data }">
                  <span class="text-red-600 font-bold">{{ data.diferencia }}</span>
                </template>
              </Column>
            </DataTable>
            <div class="flex justify-content-end mt-3" v-if="dashboardData.productos_stock_bajo > 5">
              <router-link to="/reportes" class="text-primary no-underline text-sm">
                Ver todos ({{ dashboardData.productos_stock_bajo }}) <i class="pi pi-arrow-right text-xs"></i>
              </router-link>
            </div>
          </template>
        </Card>
      </div>

      <div class="col-12 lg:col-6">
        <Card>
          <template #title>
            <div class="flex align-items-center gap-2">
              <i class="pi pi-shield text-blue-500"></i>
              <span>Estado de EPPs</span>
            </div>
          </template>
          <template #content>
            <div class="grid">
              <div class="col-4">
                <div class="text-center p-3 bg-green-50 border-round">
                  <div class="text-3xl font-bold text-green-600">{{ eppsStats.vigentes }}</div>
                  <div class="text-sm text-500 mt-1">Vigentes</div>
                </div>
              </div>
              <div class="col-4">
                <div class="text-center p-3 bg-yellow-50 border-round">
                  <div class="text-3xl font-bold text-yellow-600">{{ eppsStats.por_vencer }}</div>
                  <div class="text-sm text-500 mt-1">Por Vencer</div>
                </div>
              </div>
              <div class="col-4">
                <div class="text-center p-3 bg-red-50 border-round">
                  <div class="text-3xl font-bold text-red-600">{{ eppsStats.vencidos }}</div>
                  <div class="text-sm text-500 mt-1">Vencidos</div>
                </div>
              </div>
            </div>
            <Divider />
            <div class="flex justify-content-between align-items-center">
              <div>
                <span class="text-500">Total asignaciones activas:</span>
                <span class="font-bold ml-2">{{ eppsStats.vigentes + eppsStats.por_vencer }}</span>
              </div>
              <router-link to="/epps" class="text-primary no-underline text-sm">
                Gestionar EPPs <i class="pi pi-arrow-right text-xs"></i>
              </router-link>
            </div>
          </template>
        </Card>
      </div>

      <!-- Accesos rapidos -->
      <div class="col-12">
        <Card>
          <template #title>
            <div class="flex align-items-center gap-2">
              <i class="pi pi-th-large text-gray-500"></i>
              <span>Accesos Rapidos</span>
            </div>
          </template>
          <template #content>
            <div class="grid">
              <div class="col-6 md:col-3 lg:col-2">
                <router-link to="/inventario/productos" class="no-underline">
                  <div class="p-3 bg-blue-50 border-round text-center hover:bg-blue-100 cursor-pointer transition-colors transition-duration-200">
                    <i class="pi pi-box text-3xl text-blue-500 mb-2"></i>
                    <div class="text-700 font-medium">Productos</div>
                  </div>
                </router-link>
              </div>
              <div class="col-6 md:col-3 lg:col-2">
                <router-link to="/inventario/movimientos" class="no-underline">
                  <div class="p-3 bg-green-50 border-round text-center hover:bg-green-100 cursor-pointer transition-colors transition-duration-200">
                    <i class="pi pi-arrow-right-arrow-left text-3xl text-green-500 mb-2"></i>
                    <div class="text-700 font-medium">Movimientos</div>
                  </div>
                </router-link>
              </div>
              <div class="col-6 md:col-3 lg:col-2">
                <router-link to="/requisiciones" class="no-underline">
                  <div class="p-3 bg-yellow-50 border-round text-center hover:bg-yellow-100 cursor-pointer transition-colors transition-duration-200">
                    <i class="pi pi-file-edit text-3xl text-yellow-500 mb-2"></i>
                    <div class="text-700 font-medium">Requisiciones</div>
                  </div>
                </router-link>
              </div>
              <div class="col-6 md:col-3 lg:col-2">
                <router-link to="/compras/ordenes" class="no-underline">
                  <div class="p-3 bg-purple-50 border-round text-center hover:bg-purple-100 cursor-pointer transition-colors transition-duration-200">
                    <i class="pi pi-shopping-cart text-3xl text-purple-500 mb-2"></i>
                    <div class="text-700 font-medium">Ordenes Compra</div>
                  </div>
                </router-link>
              </div>
              <div class="col-6 md:col-3 lg:col-2">
                <router-link to="/epps" class="no-underline">
                  <div class="p-3 bg-cyan-50 border-round text-center hover:bg-cyan-100 cursor-pointer transition-colors transition-duration-200">
                    <i class="pi pi-shield text-3xl text-cyan-500 mb-2"></i>
                    <div class="text-700 font-medium">EPPs</div>
                  </div>
                </router-link>
              </div>
              <div class="col-6 md:col-3 lg:col-2">
                <router-link to="/reportes" class="no-underline">
                  <div class="p-3 bg-pink-50 border-round text-center hover:bg-pink-100 cursor-pointer transition-colors transition-duration-200">
                    <i class="pi pi-chart-bar text-3xl text-pink-500 mb-2"></i>
                    <div class="text-700 font-medium">Reportes</div>
                  </div>
                </router-link>
              </div>
            </div>
          </template>
        </Card>
      </div>
    </div>
  </div>
</template>

<style scoped>
.dashboard-view {
  max-width: 1600px;
  margin: 0 auto;
}

:deep(.p-card) {
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  border: 1px solid var(--surface-border);
}

:deep(.p-card .p-card-title) {
  font-size: 1rem;
  font-weight: 600;
}
</style>
