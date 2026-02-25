<script setup>
import { ref, computed, onMounted } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useAuthStore } from '@/stores/auth'
import api from '@/services/api'

// PrimeVue Components
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import Select from 'primevue/select'
import InputText from 'primevue/inputtext'
import AutoComplete from 'primevue/autocomplete'
import DatePicker from 'primevue/datepicker'
import Card from 'primevue/card'
import Toast from 'primevue/toast'
import Dialog from 'primevue/dialog'
import Tag from 'primevue/tag'
import Checkbox from 'primevue/checkbox'
import Tabs from 'primevue/tabs'
import TabList from 'primevue/tablist'
import Tab from 'primevue/tab'
import TabPanels from 'primevue/tabpanels'
import TabPanel from 'primevue/tabpanel'
import ProgressSpinner from 'primevue/progressspinner'

const toast = useToast()
const authStore = useAuthStore()

// Almacen asignado del usuario (para usuarios restringidos como almacenero/residente)
const almacenAsignado = computed(() => authStore.user?.almacen_id || null)
const nombreAlmacenAsignado = computed(() => {
  if (authStore.user?.almacen) {
    return authStore.user.almacen.nombre || 'Almacen asignado'
  }
  if (!almacenAsignado.value || almacenes.value.length === 0) return null
  const alm = almacenes.value.find(a => a.value === almacenAsignado.value)
  return alm?.label || null
})

// Estado
const loading = ref(false)
const kardexRecords = ref([])
const totalRecords = ref(0)

// Filtros
const selectedProducto = ref(null)
const productoSuggestions = ref([])
const selectedAlmacen = ref(null)
const almacenes = ref([])
const selectedTipo = ref(null)
const fechaInicio = ref(null)
const fechaFin = ref(null)
const incluirAnulados = ref(false)

// Paginacion
const currentPage = ref(1)
const rowsPerPage = ref(20)

// Reporte
const reporteDialogVisible = ref(false)
const reporteLoading = ref(false)
const reporteData = ref(null)

// Tipos de operacion
const tiposOperacion = ref([
  { label: 'Todos', value: null },
  { label: 'Entrada', value: 'ENTRADA' },
  { label: 'Salida', value: 'SALIDA' },
  { label: 'Ajuste Positivo', value: 'AJUSTE_POSITIVO' },
  { label: 'Ajuste Negativo', value: 'AJUSTE_NEGATIVO' },
  { label: 'Saldo Inicial', value: 'SALDO_INICIAL' }
])

// Inicializar fechas (ultimo mes)
const initFechas = () => {
  const hoy = new Date()
  const inicioMes = new Date(hoy.getFullYear(), hoy.getMonth(), 1)
  fechaInicio.value = inicioMes
  fechaFin.value = hoy
}

// Cargar datos
const loadKardex = async () => {
  loading.value = true
  try {
    const params = {
      page: currentPage.value,
      per_page: rowsPerPage.value
    }

    if (selectedProducto.value?.id) {
      params.producto_id = selectedProducto.value.id
    }
    if (selectedAlmacen.value) {
      params.almacen_id = selectedAlmacen.value
    }
    if (selectedTipo.value) {
      params.tipo_operacion = selectedTipo.value
    }
    if (fechaInicio.value) {
      params.fecha_inicio = formatDate(fechaInicio.value)
    }
    if (fechaFin.value) {
      params.fecha_fin = formatDate(fechaFin.value)
    }
    if (incluirAnulados.value) {
      params.incluir_anulados = 1
    }

    const response = await api.get('/inventario/kardex', { params })
    if (response.data.success) {
      kardexRecords.value = response.data.data || []
      totalRecords.value = response.data.meta?.total || kardexRecords.value.length
    }
  } catch (err) {
    console.error('Error al cargar kardex:', err)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'No se pudo cargar el kardex',
      life: 5000
    })
  } finally {
    loading.value = false
  }
}

const loadAlmacenes = async () => {
  try {
    const response = await api.get('/administracion/almacenes', { params: { all: true } })
    if (response.data.success && response.data.data) {
      // Si el usuario tiene almacén asignado, no mostrar "Todos"
      if (almacenAsignado.value) {
        almacenes.value = response.data.data.map(a => ({
          label: a.nombre,
          value: a.id
        }))
        // Auto-seleccionar el almacén asignado
        selectedAlmacen.value = almacenAsignado.value
      } else {
        almacenes.value = [
          { label: 'Todos los almacenes', value: null },
          ...response.data.data.map(a => ({
            label: a.nombre,
            value: a.id
          }))
        ]
      }
    }
  } catch (err) {
    console.error('Error al cargar almacenes:', err)
  }
}

// Busqueda de productos
const searchProductos = async (event) => {
  try {
    const response = await api.get('/inventario/productos', {
      params: { search: event.query, per_page: 10 }
    })
    if (response.data.success) {
      productoSuggestions.value = response.data.data.map(p => ({
        id: p.id,
        codigo: p.codigo,
        nombre: p.nombre,
        label: `${p.codigo} - ${p.nombre}`
      }))
    }
  } catch (err) {
    console.error('Error en busqueda de productos:', err)
    productoSuggestions.value = []
  }
}

// Generar reporte valorizado
const generarReporte = async () => {
  if (!fechaInicio.value || !fechaFin.value) {
    toast.add({
      severity: 'warn',
      summary: 'Fechas requeridas',
      detail: 'Seleccione el rango de fechas para el reporte',
      life: 4000
    })
    return
  }

  reporteLoading.value = true
  reporteDialogVisible.value = true
  reporteData.value = null

  try {
    const params = {
      fecha_inicio: formatDate(fechaInicio.value),
      fecha_fin: formatDate(fechaFin.value)
    }

    if (selectedProducto.value?.id) {
      params.producto_id = selectedProducto.value.id
    }
    if (selectedAlmacen.value) {
      params.almacen_id = selectedAlmacen.value
    }
    if (incluirAnulados.value) {
      params.incluir_anulados = 1
    }

    const response = await api.get('/inventario/kardex/reporte', { params })
    if (response.data.success) {
      reporteData.value = response.data.data
    }
  } catch (err) {
    console.error('Error al generar reporte:', err)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: err.response?.data?.message || 'No se pudo generar el reporte',
      life: 5000
    })
    reporteDialogVisible.value = false
  } finally {
    reporteLoading.value = false
  }
}

// Helpers
const formatDate = (date) => {
  if (!date) return null
  const d = new Date(date)
  return d.toISOString().split('T')[0]
}

const formatCurrency = (value) => {
  if (value === null || value === undefined) return 'S/ 0.00'
  return new Intl.NumberFormat('es-PE', {
    style: 'currency',
    currency: 'PEN'
  }).format(value)
}

const formatNumber = (value, decimals = 2) => {
  if (value === null || value === undefined) return '0'
  return new Intl.NumberFormat('es-PE', {
    minimumFractionDigits: decimals,
    maximumFractionDigits: decimals
  }).format(value)
}

const formatDateDisplay = (dateStr) => {
  if (!dateStr) return '-'
  const date = new Date(dateStr)
  return date.toLocaleDateString('es-PE', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
  })
}

const getTipoSeverity = (tipo) => {
  const severities = {
    'ENTRADA': 'success',
    'SALIDA': 'danger',
    'AJUSTE_POSITIVO': 'info',
    'AJUSTE_NEGATIVO': 'warn',
    'SALDO_INICIAL': 'secondary'
  }
  return severities[tipo] || 'secondary'
}

const getTipoLabel = (tipo) => {
  const labels = {
    'ENTRADA': 'Entrada',
    'SALIDA': 'Salida',
    'AJUSTE_POSITIVO': 'Ajuste +',
    'AJUSTE_NEGATIVO': 'Ajuste -',
    'SALDO_INICIAL': 'Saldo Inicial'
  }
  return labels[tipo] || tipo
}

const isEntrada = (tipo) => {
  return ['ENTRADA', 'AJUSTE_POSITIVO', 'SALDO_INICIAL'].includes(tipo)
}

// Paginacion
const onPageChange = (event) => {
  currentPage.value = event.page + 1
  rowsPerPage.value = event.rows
  loadKardex()
}

// Aplicar filtros
const aplicarFiltros = () => {
  currentPage.value = 1
  loadKardex()
}

// Limpiar filtros
const limpiarFiltros = () => {
  selectedProducto.value = null
  // Respetar almacén asignado
  selectedAlmacen.value = almacenAsignado.value || null
  selectedTipo.value = null
  incluirAnulados.value = false
  initFechas()
  currentPage.value = 1
  loadKardex()
}

// Exportar (placeholder)
const exportarKardex = async (formato) => {
  toast.add({
    severity: 'info',
    summary: 'Exportacion',
    detail: `Exportacion a ${formato.toUpperCase()} en desarrollo`,
    life: 3000
  })
}

// Computed
const hasFilters = computed(() => {
  return selectedProducto.value || selectedAlmacen.value || selectedTipo.value || incluirAnulados.value
})

onMounted(() => {
  initFechas()
  loadAlmacenes()
  loadKardex()
})
</script>

<template>
  <div class="space-y-4 kardex-page">
    <Toast />

    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Kardex Valorizado</h1>
        <p class="text-gray-600 text-sm mt-1">Registro de movimientos valorizados del inventario</p>
      </div>
      <div class="flex gap-2">
        <Button
          label="Reporte"
          icon="pi pi-file-pdf"
          severity="info"
          @click="generarReporte"
        />
        <Button
          label="Excel"
          icon="pi pi-file-excel"
          severity="success"
          outlined
          @click="exportarKardex('excel')"
        />
      </div>
    </div>

    <!-- Filtros -->
    <Card>
      <template #content>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
          <!-- Producto -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Producto</label>
            <AutoComplete
              v-model="selectedProducto"
              :suggestions="productoSuggestions"
              optionLabel="label"
              placeholder="Buscar producto..."
              class="w-full"
              @complete="searchProductos"
              dropdown
            />
          </div>

          <!-- Almacen -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Almacen</label>
            <!-- Si el usuario tiene almacen asignado, mostrar bloqueado -->
            <template v-if="almacenAsignado">
              <InputText
                :modelValue="nombreAlmacenAsignado || 'Almacen asignado'"
                class="w-full"
                disabled
              />
              <p class="text-xs text-gray-500 mt-1">Asignado a tu almacen</p>
            </template>
            <Select
              v-else
              v-model="selectedAlmacen"
              :options="almacenes"
              optionLabel="label"
              optionValue="value"
              placeholder="Todos"
              class="w-full"
            >
              <template #value="slotProps">
                <span v-if="slotProps.value">{{ almacenes.find(a => a.value === slotProps.value)?.label }}</span>
                <span v-else class="text-gray-400">{{ slotProps.placeholder }}</span>
              </template>
            </Select>
          </div>

          <!-- Tipo Operacion -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Tipo</label>
            <Select
              v-model="selectedTipo"
              :options="tiposOperacion"
              optionLabel="label"
              optionValue="value"
              placeholder="Todos"
              class="w-full"
            >
              <template #value="slotProps">
                <span v-if="slotProps.value">{{ tiposOperacion.find(t => t.value === slotProps.value)?.label }}</span>
                <span v-else class="text-gray-400">{{ slotProps.placeholder }}</span>
              </template>
            </Select>
          </div>

          <!-- Fecha Inicio -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Desde</label>
            <DatePicker
              v-model="fechaInicio"
              dateFormat="dd/mm/yy"
              placeholder="Fecha inicio"
              class="w-full"
              showIcon
            />
          </div>

          <!-- Fecha Fin -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Hasta</label>
            <DatePicker
              v-model="fechaFin"
              dateFormat="dd/mm/yy"
              placeholder="Fecha fin"
              class="w-full"
              showIcon
            />
          </div>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3 mt-4">
          <div class="flex items-center gap-2">
            <Checkbox v-model="incluirAnulados" inputId="incluirAnulados" :binary="true" />
            <label for="incluirAnulados" class="text-sm text-gray-700 cursor-pointer">
              Incluir anulados
            </label>
          </div>
          <div class="flex gap-2">
            <Button
              label="Limpiar"
              icon="pi pi-times"
              severity="secondary"
              outlined
              @click="limpiarFiltros"
              :disabled="!hasFilters"
            />
            <Button
              label="Buscar"
              icon="pi pi-search"
              class="!bg-amber-600 !border-amber-600"
              @click="aplicarFiltros"
            />
          </div>
        </div>
      </template>
    </Card>

    <!-- Tabla de Kardex -->
    <Card class="kardex-table-card">
      <template #content>
        <div class="kardex-table-wrap">
          <DataTable
            :value="kardexRecords"
            :loading="loading"
            :paginator="true"
            :rows="rowsPerPage"
            :totalRecords="totalRecords"
            :lazy="true"
            @page="onPageChange"
            :rowsPerPageOptions="[10, 20, 50, 100]"
            paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
            currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords}"
            responsiveLayout="scroll"
            stripedRows
            class="text-sm kardex-table"
            :tableStyle="{ width: '100%', minWidth: '1000px' }"
          >
          <template #empty>
            <div class="text-center py-8 text-gray-500">
              <i class="pi pi-book text-4xl mb-2"></i>
              <p>No se encontraron registros de kardex</p>
              <p class="text-sm mt-1">Realice movimientos de inventario para generar registros</p>
            </div>
          </template>

          <Column field="fecha" header="Fecha" style="width: 100px">
            <template #body="{ data }">
              {{ formatDateDisplay(data.fecha) }}
            </template>
          </Column>

          <Column header="Producto" style="min-width: 180px">
            <template #body="{ data }">
              <div class="product-cell">
                <span class="font-medium">{{ data.producto?.codigo }}</span>
                <span class="text-gray-600 ml-2">{{ data.producto?.nombre }}</span>
              </div>
            </template>
          </Column>

          <Column field="almacen.nombre" header="Almacen" style="min-width: 140px">
            <template #body="{ data }">
              <span class="break-words">{{ data.almacen?.nombre || '-' }}</span>
            </template>
          </Column>

          <Column field="tipo_operacion" header="Tipo" style="width: 100px">
            <template #body="{ data }">
              <Tag
                :value="getTipoLabel(data.tipo_operacion)"
                :severity="getTipoSeverity(data.tipo_operacion)"
              />
            </template>
          </Column>

          <Column field="documento_referencia" header="Documento" style="min-width: 110px">
            <template #body="{ data }">
              <span class="text-blue-600 font-mono text-xs break-words">
                {{ data.documento_referencia || '-' }}
              </span>
            </template>
          </Column>

          <Column header="Entrada" style="width: 90px" class="text-right">
            <template #body="{ data }">
              <span v-if="isEntrada(data.tipo_operacion)" class="text-green-600 font-medium">
                {{ formatNumber(data.cantidad) }}
              </span>
              <span v-else class="text-gray-400">-</span>
            </template>
          </Column>

          <Column header="Salida" style="width: 90px" class="text-right">
            <template #body="{ data }">
              <span v-if="!isEntrada(data.tipo_operacion)" class="text-red-600 font-medium">
                {{ formatNumber(data.cantidad) }}
              </span>
              <span v-else class="text-gray-400">-</span>
            </template>
          </Column>

          <Column header="C. Unit." style="width: 100px" class="text-right">
            <template #body="{ data }">
              {{ formatCurrency(data.costo_unitario) }}
            </template>
          </Column>

          <Column header="Costo Total" style="width: 110px" class="text-right">
            <template #body="{ data }">
              <span :class="isEntrada(data.tipo_operacion) ? 'text-green-600' : 'text-red-600'">
                {{ formatCurrency(data.costo_total) }}
              </span>
            </template>
          </Column>

          <Column header="Saldo Cant." style="width: 100px" class="text-right">
            <template #body="{ data }">
              <span class="font-semibold">{{ formatNumber(data.saldo_cantidad) }}</span>
            </template>
          </Column>

          <Column header="Saldo Valor" style="width: 120px" class="text-right">
            <template #body="{ data }">
              <span class="font-semibold text-blue-700">
                {{ formatCurrency(data.saldo_costo_total) }}
              </span>
            </template>
          </Column>
          </DataTable>
        </div>
      </template>
    </Card>

    <!-- Dialog Reporte Valorizado -->
    <Dialog
      v-model:visible="reporteDialogVisible"
      header="Reporte Kardex Valorizado"
      :modal="true"
      :style="{ width: '90vw', maxWidth: '1200px' }"
      :closable="!reporteLoading"
    >
      <div v-if="reporteLoading" class="flex flex-col items-center justify-center py-12">
        <ProgressSpinner style="width: 50px; height: 50px" />
        <p class="mt-4 text-gray-600">Generando reporte...</p>
      </div>

      <div v-else-if="reporteData">
        <!-- Periodo -->
        <div class="bg-gray-50 rounded-lg p-4 mb-4">
          <div class="flex items-center justify-between">
            <div>
              <span class="text-gray-600">Periodo:</span>
              <span class="font-medium ml-2">
                {{ formatDateDisplay(reporteData.periodo?.inicio) }} - {{ formatDateDisplay(reporteData.periodo?.fin) }}
              </span>
            </div>
            <div class="text-right">
              <span class="text-gray-600">Total Productos:</span>
              <span class="font-semibold ml-2 text-lg">{{ reporteData.resumen?.total_productos || 0 }}</span>
            </div>
          </div>
        </div>

        <!-- Resumen General -->
        <div class="grid grid-cols-3 gap-4 mb-6">
          <div class="bg-green-50 border border-green-200 rounded-lg p-4">
            <p class="text-green-800 text-sm font-medium">Total Entradas</p>
            <p class="text-2xl font-bold text-green-700">{{ formatCurrency(reporteData.resumen?.total_entradas) }}</p>
          </div>
          <div class="bg-red-50 border border-red-200 rounded-lg p-4">
            <p class="text-red-800 text-sm font-medium">Total Salidas</p>
            <p class="text-2xl font-bold text-red-700">{{ formatCurrency(reporteData.resumen?.total_salidas) }}</p>
          </div>
          <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <p class="text-blue-800 text-sm font-medium">Valor Inventario Final</p>
            <p class="text-2xl font-bold text-blue-700">{{ formatCurrency(reporteData.resumen?.valor_inventario_final) }}</p>
          </div>
        </div>

        <!-- Detalle por Producto -->
        <Tabs v-if="reporteData.productos?.length > 0" :value="reporteData.productos[0]?.producto?.codigo">
          <TabList>
            <Tab v-for="(producto, index) in reporteData.productos" :key="index" :value="producto.producto?.codigo">
              {{ producto.producto?.codigo }}
            </Tab>
          </TabList>
          <TabPanels>
            <TabPanel
              v-for="(producto, index) in reporteData.productos"
              :key="index"
              :value="producto.producto?.codigo"
            >
            <div class="space-y-4">
              <!-- Info Producto -->
              <div class="flex items-center justify-between bg-gray-50 p-3 rounded-lg">
                <div>
                  <span class="font-semibold">{{ producto.producto?.codigo }}</span>
                  <span class="text-gray-600 ml-2">{{ producto.producto?.nombre }}</span>
                  <span class="text-gray-400 ml-2">({{ producto.producto?.unidad_medida }})</span>
                </div>
                <div class="text-right">
                  <span class="text-sm text-gray-600">Costo Promedio:</span>
                  <span class="font-semibold ml-2">{{ formatCurrency(producto.saldo_final?.costo_promedio) }}</span>
                </div>
              </div>

              <!-- Resumen del Producto -->
              <div class="grid grid-cols-4 gap-3 text-sm">
                <div class="border rounded p-3">
                  <p class="text-gray-500">Saldo Inicial</p>
                  <p class="font-medium">{{ formatNumber(producto.saldo_inicial?.cantidad) }} und</p>
                  <p class="text-gray-600">{{ formatCurrency(producto.saldo_inicial?.valor) }}</p>
                </div>
                <div class="border rounded p-3 bg-green-50">
                  <p class="text-green-700">Entradas</p>
                  <p class="font-medium text-green-800">+{{ formatNumber(producto.entradas?.cantidad) }} und</p>
                  <p class="text-green-600">{{ formatCurrency(producto.entradas?.valor) }}</p>
                </div>
                <div class="border rounded p-3 bg-red-50">
                  <p class="text-red-700">Salidas</p>
                  <p class="font-medium text-red-800">-{{ formatNumber(producto.salidas?.cantidad) }} und</p>
                  <p class="text-red-600">{{ formatCurrency(producto.salidas?.valor) }}</p>
                </div>
                <div class="border rounded p-3 bg-blue-50">
                  <p class="text-blue-700">Saldo Final</p>
                  <p class="font-medium text-blue-800">{{ formatNumber(producto.saldo_final?.cantidad) }} und</p>
                  <p class="text-blue-600">{{ formatCurrency(producto.saldo_final?.valor) }}</p>
                </div>
              </div>

              <!-- Detalle de Movimientos -->
              <DataTable
                :value="producto.movimientos"
                size="small"
                stripedRows
                class="text-xs"
              >
                <Column field="fecha" header="Fecha" style="width: 90px">
                  <template #body="{ data }">
                    {{ formatDateDisplay(data.fecha) }}
                  </template>
                </Column>
                <Column field="tipo" header="Tipo" style="width: 80px">
                  <template #body="{ data }">
                    <Tag :value="getTipoLabel(data.tipo)" :severity="getTipoSeverity(data.tipo)" />
                  </template>
                </Column>
                <Column field="documento" header="Documento" style="width: 120px" />
                <Column header="Entrada" style="width: 80px" class="text-right">
                  <template #body="{ data }">
                    <span v-if="isEntrada(data.tipo)" class="text-green-600">
                      {{ formatNumber(data.cantidad) }}
                    </span>
                  </template>
                </Column>
                <Column header="Salida" style="width: 80px" class="text-right">
                  <template #body="{ data }">
                    <span v-if="!isEntrada(data.tipo)" class="text-red-600">
                      {{ formatNumber(data.cantidad) }}
                    </span>
                  </template>
                </Column>
                <Column header="C. Unit." style="width: 90px" class="text-right">
                  <template #body="{ data }">
                    {{ formatCurrency(data.costo_unitario) }}
                  </template>
                </Column>
                <Column header="C. Total" style="width: 100px" class="text-right">
                  <template #body="{ data }">
                    {{ formatCurrency(data.costo_total) }}
                  </template>
                </Column>
                <Column header="Saldo" style="width: 80px" class="text-right">
                  <template #body="{ data }">
                    <span class="font-medium">{{ formatNumber(data.saldo_cantidad) }}</span>
                  </template>
                </Column>
                <Column header="Valor" style="width: 100px" class="text-right">
                  <template #body="{ data }">
                    <span class="font-medium text-blue-700">{{ formatCurrency(data.saldo_valor) }}</span>
                  </template>
                </Column>
              </DataTable>
            </div>
          </TabPanel>
          </TabPanels>
        </Tabs>

        <div v-else class="text-center py-8 text-gray-500">
          <i class="pi pi-inbox text-4xl mb-2"></i>
          <p>No hay movimientos en el periodo seleccionado</p>
        </div>
      </div>

      <template #footer>
        <Button
          label="Cerrar"
          severity="secondary"
          @click="reporteDialogVisible = false"
          :disabled="reporteLoading"
        />
        <Button
          v-if="reporteData"
          label="Exportar PDF"
          icon="pi pi-file-pdf"
          severity="danger"
          @click="exportarKardex('pdf')"
        />
      </template>
    </Dialog>
  </div>
</template>

<style scoped>
.kardex-page {
  max-width: 100%;
  overflow-x: hidden;
}

.kardex-table-wrap {
  width: 100%;
  max-width: 100%;
  overflow-x: auto;
}

.product-cell {
  white-space: normal;
  word-break: break-word;
}
</style>
