<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useAuthStore } from '@/stores/auth'
import api from '@/services/api'

// PrimeVue Components
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Textarea from 'primevue/textarea'
import Select from 'primevue/select'
import AutoComplete from 'primevue/autocomplete'
import DatePicker from 'primevue/datepicker'
import Card from 'primevue/card'
import Dialog from 'primevue/dialog'
import Tag from 'primevue/tag'
import Divider from 'primevue/divider'
import Message from 'primevue/message'
import Checkbox from 'primevue/checkbox'

const toast = useToast()
const authStore = useAuthStore()

// Almacén y centro de costo asignado del usuario
const almacenAsignado = computed(() => authStore.user?.almacen_id || null)
const nombreAlmacenAsignado = computed(() => {
  if (authStore.user?.almacen) {
    return authStore.user.almacen.nombre
  }
  if (!almacenAsignado.value || almacenes.value.length === 0) return null
  const alm = almacenes.value.find(a => a.value === almacenAsignado.value)
  return alm?.label || null
})

const centroCostoAsignado = computed(() => authStore.user?.centro_costo_id || null)
const nombreCentroCostoAsignado = computed(() => {
  if (authStore.user?.centro_costo) {
    return authStore.user.centro_costo.nombre
  }
  if (!centroCostoAsignado.value || centrosCosto.value.length === 0) return null
  const cc = centrosCosto.value.find(c => c.value === centroCostoAsignado.value)
  return cc?.label || null
})

// Estado
const loading = ref(false)
const vales = ref([])
const totalRecords = ref(0)

// Filtros
const searchQuery = ref('')
const selectedEstado = ref(null)
const selectedAlmacen = ref(null)

// Dialogos
const dialogVisible = ref(false)
const viewDialogVisible = ref(false)
const entregarDialogVisible = ref(false)
const desdeRequisicionDialogVisible = ref(false)

// Datos
const selectedVale = ref(null)
const almacenes = ref([])
const centrosCosto = ref([])
const productoSuggestions = ref([])
const requisicionesAprobadas = ref([])
const selectedRequisicion = ref(null)
const estadisticas = ref({})
const receptorSuggestions = ref([])

// Formulario nuevo vale
const formData = ref({
  almacen_id: null,
  centro_costo_id: null,
  fecha: new Date(),
  receptor: null,
  receptor_nombre: '',
  receptor_dni: '',
  motivo: '',
  observaciones: '',
  detalles: []
})

// Formulario entrega
const entregaData = ref([])

// Opciones
const estados = ref([
  { label: 'Todos', value: null },
  { label: 'Pendiente', value: 'PENDIENTE' },
  { label: 'Entregado', value: 'ENTREGADO' },
  { label: 'Parcial', value: 'PARCIAL' },
  { label: 'Anulado', value: 'ANULADO' }
])

// Cargar datos
const loadVales = async () => {
  loading.value = true
  try {
    const params = {}
    if (searchQuery.value) params.search = searchQuery.value
    if (selectedEstado.value) params.estado = selectedEstado.value
    if (selectedAlmacen.value) params.almacen_id = selectedAlmacen.value

    const response = await api.get('/vales-salida', { params })
    if (response.data.success) {
      vales.value = response.data.data || []
      totalRecords.value = response.data.meta?.total || vales.value.length
    }
  } catch (err) {
    console.error('Error al cargar vales:', err)
    toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudieron cargar los vales', life: 5000 })
  } finally {
    loading.value = false
  }
}

const loadAlmacenes = async () => {
  try {
    const response = await api.get('/administracion/almacenes', { params: { all: true } })
    if (response.data.success) {
      almacenes.value = response.data.data.map(a => ({ label: a.nombre, value: a.id }))
    }
  } catch (err) {
    console.error('Error al cargar almacenes:', err)
  }
}

const loadCentrosCosto = async () => {
  try {
    const response = await api.get('/administracion/centros-costo', { params: { all: true } })
    if (response.data.success) {
      centrosCosto.value = response.data.data.map(c => ({ label: c.nombre, value: c.id }))
    }
  } catch (err) {
    console.error('Error al cargar centros de costo:', err)
  }
}

const loadEstadisticas = async () => {
  try {
    const response = await api.get('/vales-salida/estadisticas')
    if (response.data.success) {
      estadisticas.value = response.data.data
    }
  } catch (err) {
    console.error('Error al cargar estadisticas:', err)
  }
}

const loadRequisicionesAprobadas = async () => {
  try {
    const response = await api.get('/vales-salida/requisiciones-aprobadas')
    if (response.data.success) {
      requisicionesAprobadas.value = response.data.data
    }
  } catch (err) {
    console.error('Error al cargar requisiciones:', err)
  }
}

const searchProductos = async (event) => {
  try {
    const almacenId = almacenAsignado.value || formData.value.almacen_id || selectedRequisicion.value?.almacen_id || null
    if (!almacenId) {
      productoSuggestions.value = []
      return
    }

    const response = await api.get('/inventario/productos', {
      params: {
        search: event.query,
        per_page: 10,
        almacen_id: almacenId,
        solo_con_stock: true
      }
    })
    if (response.data.success) {
      productoSuggestions.value = response.data.data.map(p => ({
        id: p.id,
        codigo: p.codigo,
        nombre: p.nombre,
        unidad: p.unidad_medida,
        stock: p.stock_total || 0,
        label: `${p.codigo} - ${p.nombre} (Stock: ${p.stock_total || 0})`
      }))
    }
  } catch (err) {
    productoSuggestions.value = []
  }
}

const searchReceptores = async (event) => {
  try {
    const centroCostoId = centroCostoAsignado.value || formData.value.centro_costo_id || selectedRequisicion.value?.centro_costo_id || null
    const almacenId = almacenAsignado.value || formData.value.almacen_id || null

    const params = {
      search: event.query || '',
      centro_costo_id: centroCostoId,
      almacen_id: almacenId
    }

    const response = await api.get('/vales-salida/personal', { params })
    if (response.data.success) {
      receptorSuggestions.value = (response.data.data || []).map(p => ({
        id: p.id,
        tipo: p.tipo,
        nombre: p.nombre,
        dni: p.dni,
        display_name: p.display_name
      }))
    }
  } catch (err) {
    receptorSuggestions.value = []
  }
}

const onSelectReceptor = () => {
  const receptor = formData.value.receptor
  if (!receptor) {
    formData.value.receptor_nombre = ''
    formData.value.receptor_dni = ''
    return
  }
  formData.value.receptor_nombre = receptor.nombre
  formData.value.receptor_dni = receptor.dni || ''
}

// Acciones
const openNewDialog = () => {
  formData.value = {
    almacen_id: almacenAsignado.value || null,
    centro_costo_id: centroCostoAsignado.value || null,
    fecha: new Date(),
    receptor: null,
    receptor_nombre: '',
    receptor_dni: '',
    motivo: '',
    observaciones: '',
    detalles: []
  }
  dialogVisible.value = true
}

const openDesdeRequisicionDialog = () => {
  loadRequisicionesAprobadas()
  selectedRequisicion.value = null
  formData.value.receptor = null
  formData.value.receptor_nombre = ''
  formData.value.receptor_dni = ''
  desdeRequisicionDialogVisible.value = true
}

const viewVale = async (vale) => {
  try {
    const response = await api.get(`/vales-salida/${vale.id}`)
    if (response.data.success) {
      selectedVale.value = response.data.data
      viewDialogVisible.value = true
    }
  } catch (err) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudo cargar el vale', life: 5000 })
  }
}

const openEntregarDialog = async (vale) => {
  try {
    const response = await api.get(`/vales-salida/${vale.id}`)
    if (response.data.success) {
      selectedVale.value = response.data.data
      entregaData.value = response.data.data.detalles.map(d => ({
        detalle_id: d.id,
        producto: d.producto,
        cantidad_solicitada: parseFloat(d.cantidad_solicitada),
        cantidad_entregada: parseFloat(d.cantidad_entregada),
        cantidad_entregar: Math.max(0, parseFloat(d.cantidad_solicitada) - parseFloat(d.cantidad_entregada)),
        entregar: true
      }))
      entregarDialogVisible.value = true
    }
  } catch (err) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudo cargar el vale', life: 5000 })
  }
}

const addDetalle = () => {
  formData.value.detalles.push({
    producto: null,
    cantidad_solicitada: 1
  })
}

const removeDetalle = (index) => {
  formData.value.detalles.splice(index, 1)
}

const saveVale = async () => {
  // Usar valores asignados si existen
  const almId = almacenAsignado.value || formData.value.almacen_id
  const ccId = centroCostoAsignado.value || formData.value.centro_costo_id

  // Validaciones
  if (!almId) {
    toast.add({ severity: 'warn', summary: 'Validacion', detail: 'Seleccione un almacen', life: 4000 })
    return
  }
  if (!ccId) {
    toast.add({ severity: 'warn', summary: 'Validacion', detail: 'Seleccione un centro de costo', life: 4000 })
    return
  }
  if (!formData.value.receptor) {
    toast.add({ severity: 'warn', summary: 'Validacion', detail: 'Seleccione un receptor', life: 4000 })
    return
  }
  if (formData.value.detalles.length === 0) {
    toast.add({ severity: 'warn', summary: 'Validacion', detail: 'Agregue al menos un producto', life: 4000 })
    return
  }
  const detallesInvalidos = formData.value.detalles.some(d => !d.producto?.id || !d.cantidad_solicitada || d.cantidad_solicitada <= 0)
  if (detallesInvalidos) {
    toast.add({ severity: 'warn', summary: 'Validacion', detail: 'Complete producto y cantidad valida en todos los detalles', life: 4000 })
    return
  }

  loading.value = true
  try {
    const dataToSend = {
      almacen_id: almId,
      centro_costo_id: ccId,
      fecha: formatDate(formData.value.fecha),
      receptor_id: formData.value.receptor?.id || null,
      receptor_tipo: formData.value.receptor?.tipo || null,
      receptor_nombre: formData.value.receptor_nombre,
      receptor_dni: formData.value.receptor_dni || null,
      motivo: formData.value.motivo || null,
      observaciones: formData.value.observaciones || null,
      detalles: formData.value.detalles.map(d => ({
        producto_id: d.producto.id,
        cantidad_solicitada: d.cantidad_solicitada
      }))
    }

    const response = await api.post('/vales-salida', dataToSend)
    if (response.data.success) {
      toast.add({ severity: 'success', summary: 'Exito', detail: 'Vale creado correctamente', life: 3000 })
      dialogVisible.value = false
      loadVales()
      loadEstadisticas()
    }
  } catch (err) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: err.response?.data?.message || 'Error al crear el vale',
      life: 5000
    })
  } finally {
    loading.value = false
  }
}

const crearDesdeRequisicion = async () => {
  // Usar almacén asignado si existe
  const almId = almacenAsignado.value || formData.value.almacen_id

  if (!selectedRequisicion.value) {
    toast.add({ severity: 'warn', summary: 'Validacion', detail: 'Seleccione una requisicion', life: 4000 })
    return
  }
  if (!almId) {
    toast.add({ severity: 'warn', summary: 'Validacion', detail: 'Seleccione un almacen', life: 4000 })
    return
  }
  if (!formData.value.receptor) {
    toast.add({ severity: 'warn', summary: 'Validacion', detail: 'Seleccione un receptor', life: 4000 })
    return
  }

  // Obtener detalles pendientes
  const detallesPendientes = selectedRequisicion.value.detalles
    .filter(d => d.pendiente > 0)
    .map(d => ({
      requisicion_detalle_id: d.id,
      cantidad: d.pendiente
    }))

  if (detallesPendientes.length === 0) {
    toast.add({ severity: 'warn', summary: 'Sin pendientes', detail: 'Esta requisicion no tiene productos pendientes de entregar', life: 4000 })
    return
  }

  loading.value = true
  try {
    const response = await api.post(`/requisiciones/${selectedRequisicion.value.id}/generar-vale`, {
      almacen_id: almId,
      receptor_id: formData.value.receptor?.id || null,
      receptor_tipo: formData.value.receptor?.tipo || null,
      receptor_nombre: formData.value.receptor_nombre,
      receptor_dni: formData.value.receptor_dni || null,
      observaciones: formData.value.observaciones || null,
      detalles: detallesPendientes
    })

    if (response.data.success) {
      toast.add({ severity: 'success', summary: 'Exito', detail: 'Vale creado desde requisicion', life: 3000 })
      desdeRequisicionDialogVisible.value = false
      loadVales()
      loadEstadisticas()
    }
  } catch (err) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: err.response?.data?.message || 'Error al crear el vale',
      life: 5000
    })
  } finally {
    loading.value = false
  }
}

const procesarEntrega = async () => {
  const entregas = entregaData.value
    .filter(e => e.entregar && e.cantidad_entregar > 0)
    .map(e => ({
      detalle_id: e.detalle_id,
      cantidad: e.cantidad_entregar
    }))

  if (entregas.length === 0) {
    toast.add({ severity: 'warn', summary: 'Validacion', detail: 'Seleccione al menos un producto para entregar', life: 4000 })
    return
  }

  loading.value = true
  try {
    const response = await api.post(`/vales-salida/${selectedVale.value.id}/entregar`, {
      entregas: entregas
    })

    if (response.data.success) {
      toast.add({ severity: 'success', summary: 'Exito', detail: 'Entrega procesada correctamente', life: 3000 })
      entregarDialogVisible.value = false
      loadVales()
      loadEstadisticas()
    }
  } catch (err) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: err.response?.data?.message || 'Error al procesar la entrega',
      life: 5000
    })
  } finally {
    loading.value = false
  }
}

const anularVale = async (vale) => {
  if (!confirm('¿Esta seguro de anular este vale de salida?')) return
  try {
    const response = await api.post(`/vales-salida/${vale.id}/anular`)
    if (response.data.success) {
      toast.add({ severity: 'warn', summary: 'Anulado', detail: 'Vale anulado correctamente', life: 3000 })
      loadVales()
      loadEstadisticas()
    }
  } catch (err) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: err.response?.data?.message || 'Error al anular',
      life: 5000
    })
  }
}

// Helpers
const formatDate = (date) => {
  if (!date) return null
  const d = new Date(date)
  return d.toISOString().split('T')[0]
}

const formatDateDisplay = (dateStr) => {
  if (!dateStr) return '-'
  const date = new Date(dateStr)
  return date.toLocaleDateString('es-PE', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

const formatCurrency = (value) => {
  if (value === null || value === undefined) return 'S/ 0.00'
  return new Intl.NumberFormat('es-PE', { style: 'currency', currency: 'PEN' }).format(value)
}

const getEstadoSeverity = (estado) => {
  const map = {
    'PENDIENTE': 'warn',
    'ENTREGADO': 'success',
    'PARCIAL': 'info',
    'ANULADO': 'secondary'
  }
  return map[estado] || 'secondary'
}

const canEntregar = (vale) => {
  return vale.estado === 'PENDIENTE' || vale.estado === 'PARCIAL'
}

const canAnular = (vale) => {
  return vale.estado === 'PENDIENTE'
}

watch(() => formData.value.centro_costo_id, () => {
  formData.value.receptor = null
  formData.value.receptor_nombre = ''
  formData.value.receptor_dni = ''
})

watch(() => formData.value.almacen_id, () => {
  formData.value.receptor = null
  formData.value.receptor_nombre = ''
  formData.value.receptor_dni = ''
})

watch(selectedRequisicion, (req) => {
  if (req?.centro_costo_id) {
    formData.value.centro_costo_id = req.centro_costo_id
  }
  formData.value.receptor = null
  formData.value.receptor_nombre = ''
  formData.value.receptor_dni = ''
})

onMounted(() => {
  loadVales()
  loadAlmacenes()
  loadCentrosCosto()
  loadEstadisticas()
})
</script>

<template>
  <div class="space-y-4">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Vales de Salida</h1>
        <p class="text-gray-600 text-sm mt-1">Despacho de materiales del almacen</p>
      </div>
      <div class="flex gap-2">
        <Button
          label="Desde Requisicion"
          icon="pi pi-file-import"
          severity="info"
          outlined
          @click="openDesdeRequisicionDialog"
        />
        <Button
          label="Nuevo Vale"
          icon="pi pi-plus"
          class="!bg-amber-600 !border-amber-600"
          @click="openNewDialog"
        />
      </div>
    </div>

    <!-- Estadisticas -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
      <Card class="!bg-blue-50">
        <template #content>
          <div class="text-center">
            <p class="text-2xl font-bold text-blue-700">{{ estadisticas.total || 0 }}</p>
            <p class="text-sm text-blue-600">Total Vales</p>
          </div>
        </template>
      </Card>
      <Card class="!bg-yellow-50">
        <template #content>
          <div class="text-center">
            <p class="text-2xl font-bold text-yellow-700">{{ estadisticas.pendientes || 0 }}</p>
            <p class="text-sm text-yellow-600">Pendientes</p>
          </div>
        </template>
      </Card>
      <Card class="!bg-green-50">
        <template #content>
          <div class="text-center">
            <p class="text-2xl font-bold text-green-700">{{ estadisticas.entregados_hoy || 0 }}</p>
            <p class="text-sm text-green-600">Entregados Hoy</p>
          </div>
        </template>
      </Card>
      <Card class="!bg-purple-50">
        <template #content>
          <div class="text-center">
            <p class="text-2xl font-bold text-purple-700">{{ formatCurrency(estadisticas.valor_mes) }}</p>
            <p class="text-sm text-purple-600">Valor del Mes</p>
          </div>
        </template>
      </Card>
    </div>

    <!-- Filtros -->
    <Card>
      <template #content>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <InputText v-model="searchQuery" placeholder="Buscar por numero o receptor..." class="w-full" @keyup.enter="loadVales" />
          <Select v-model="selectedEstado" :options="estados" optionLabel="label" optionValue="value" placeholder="Estado" class="w-full" @change="loadVales">
            <template #value="slotProps">
              <span v-if="slotProps.value !== null && slotProps.value !== undefined">{{ estados.find(e => e.value === slotProps.value)?.label }}</span>
              <span v-else class="text-gray-400">{{ slotProps.placeholder }}</span>
            </template>
          </Select>
          <Select v-model="selectedAlmacen" :options="[{label: 'Todos', value: null}, ...almacenes]" optionLabel="label" optionValue="value" placeholder="Almacen" class="w-full" @change="loadVales">
            <template #value="slotProps">
              <span v-if="slotProps.value !== null && slotProps.value !== undefined">{{ almacenes.find(a => a.value === slotProps.value)?.label || 'Todos' }}</span>
              <span v-else class="text-gray-400">{{ slotProps.placeholder }}</span>
            </template>
          </Select>
          <div class="flex gap-2">
            <Button label="Buscar" icon="pi pi-search" class="!bg-amber-600 !border-amber-600" @click="loadVales" />
            <Button icon="pi pi-refresh" severity="secondary" outlined @click="searchQuery = ''; selectedEstado = null; selectedAlmacen = null; loadVales()" />
          </div>
        </div>
      </template>
    </Card>

    <!-- Tabla -->
    <Card>
      <template #content>
        <DataTable :value="vales" :loading="loading" :paginator="true" :rows="15" :rowsPerPageOptions="[10, 15, 25, 50]" responsiveLayout="scroll" stripedRows class="text-sm">
          <template #empty>
            <div class="text-center py-8 text-gray-500">
              <i class="pi pi-sign-out text-4xl mb-2"></i>
              <p>No se encontraron vales de salida</p>
            </div>
          </template>

          <Column field="numero" header="Numero" style="width: 140px">
            <template #body="{ data }">
              <span class="font-mono text-blue-600 font-medium">{{ data.numero }}</span>
            </template>
          </Column>

          <Column field="fecha" header="Fecha" style="width: 100px">
            <template #body="{ data }">{{ formatDateDisplay(data.fecha) }}</template>
          </Column>

          <Column field="almacen.nombre" header="Almacen" style="min-width: 120px" />

          <Column field="centro_costo.nombre" header="Centro Costo" style="min-width: 120px" />

          <Column header="Receptor" style="min-width: 150px">
            <template #body="{ data }">
              <div>
                <p class="font-medium">{{ data.receptor_nombre || data.solicitante?.nombre }}</p>
                <p v-if="data.requisicion" class="text-xs text-gray-500">Req: {{ data.requisicion.numero }}</p>
              </div>
            </template>
          </Column>

          <Column field="estado" header="Estado" style="width: 110px">
            <template #body="{ data }">
              <Tag :value="data.estado" :severity="getEstadoSeverity(data.estado)" />
            </template>
          </Column>

          <Column field="detalles_count" header="Items" style="width: 70px" class="text-center">
            <template #body="{ data }">
              <span class="bg-gray-100 px-2 py-1 rounded text-sm">{{ data.detalles_count }}</span>
            </template>
          </Column>

          <Column header="Acciones" style="width: 150px">
            <template #body="{ data }">
              <div class="flex gap-1">
                <Button icon="pi pi-eye" severity="info" text rounded size="small" @click="viewVale(data)" v-tooltip.top="'Ver detalle'" />
                <Button v-if="canEntregar(data)" icon="pi pi-check-circle" severity="success" text rounded size="small" @click="openEntregarDialog(data)" v-tooltip.top="'Procesar entrega'" />
                <Button v-if="canAnular(data)" icon="pi pi-ban" severity="danger" text rounded size="small" @click="anularVale(data)" v-tooltip.top="'Anular'" />
              </div>
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>

    <!-- Dialog Nuevo Vale -->
    <Dialog v-model:visible="dialogVisible" header="Nuevo Vale de Salida" :modal="true" :style="{ width: '90vw', maxWidth: '800px', maxHeight: '90vh' }" :contentStyle="{ overflow: 'auto', maxHeight: 'calc(90vh - 120px)' }">
      <div class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Almacen *</label>
            <!-- Si el usuario tiene almacén asignado, mostrar bloqueado -->
            <template v-if="almacenAsignado">
              <InputText :modelValue="nombreAlmacenAsignado || 'Almacén asignado'" class="w-full" disabled />
              <p class="text-xs text-gray-500 mt-1">Tu almacén asignado</p>
            </template>
            <Select v-else v-model="formData.almacen_id" :options="almacenes" optionLabel="label" optionValue="value" placeholder="Seleccione" class="w-full">
              <template #value="slotProps">
                <span v-if="slotProps.value">{{ almacenes.find(a => a.value === slotProps.value)?.label }}</span>
                <span v-else class="text-gray-400">{{ slotProps.placeholder }}</span>
              </template>
            </Select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Centro de Costo *</label>
            <!-- Si el usuario tiene centro de costo asignado, mostrar bloqueado -->
            <template v-if="centroCostoAsignado">
              <InputText :modelValue="nombreCentroCostoAsignado || 'Centro de costo asignado'" class="w-full" disabled />
              <p class="text-xs text-gray-500 mt-1">Tu proyecto asignado</p>
            </template>
            <Select v-else v-model="formData.centro_costo_id" :options="centrosCosto" optionLabel="label" optionValue="value" placeholder="Seleccione" class="w-full">
              <template #value="slotProps">
                <span v-if="slotProps.value">{{ centrosCosto.find(c => c.value === slotProps.value)?.label }}</span>
                <span v-else class="text-gray-400">{{ slotProps.placeholder }}</span>
              </template>
            </Select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Receptor *</label>
            <AutoComplete
              v-model="formData.receptor"
              :suggestions="receptorSuggestions"
              optionLabel="display_name"
              placeholder="Buscar trabajador o usuario..."
              class="w-full"
              @complete="searchReceptores"
              @item-select="onSelectReceptor"
              forceSelection
              dropdown
            >
              <template #option="slotProps">
                <div class="flex flex-col">
                  <span class="font-medium">{{ slotProps.option.display_name }}</span>
                  <span class="text-xs text-gray-500">{{ slotProps.option.dni || 'Sin DNI' }}</span>
                </div>
              </template>
            </AutoComplete>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">DNI Receptor</label>
            <InputText v-model="formData.receptor_dni" placeholder="Autocompletado" class="w-full" readonly />
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Motivo</label>
          <Textarea v-model="formData.motivo" rows="2" class="w-full" placeholder="Motivo de la salida..." />
        </div>

        <Divider />

        <div>
          <div class="flex items-center justify-between mb-2">
            <label class="text-sm font-medium text-gray-700">Productos *</label>
            <Button label="Agregar" icon="pi pi-plus" size="small" severity="secondary" @click="addDetalle" />
          </div>

          <div v-if="formData.detalles.length === 0" class="text-center py-4 text-gray-500 border rounded">
            <p>No hay productos agregados</p>
          </div>

          <div v-else class="space-y-2">
            <div v-for="(det, index) in formData.detalles" :key="index" class="grid grid-cols-12 gap-2 items-end p-3 bg-gray-50 rounded">
              <div class="col-span-7">
                <label class="block text-xs text-gray-500 mb-1">Producto</label>
                <AutoComplete v-model="det.producto" :suggestions="productoSuggestions" optionLabel="label" placeholder="Buscar..." class="w-full" @complete="searchProductos" />
              </div>
              <div class="col-span-2">
                <label class="block text-xs text-gray-500 mb-1">Cantidad</label>
                <InputNumber v-model="det.cantidad_solicitada" :min="0.01" class="w-full" />
              </div>
              <div class="col-span-2 text-center">
                <label class="block text-xs text-gray-500 mb-1">Stock</label>
                <span class="text-sm font-medium">{{ det.producto?.stock || '-' }}</span>
              </div>
              <div class="col-span-1">
                <Button icon="pi pi-trash" severity="danger" text rounded @click="removeDetalle(index)" />
              </div>
            </div>
          </div>
        </div>
      </div>

      <template #footer>
        <Button label="Cancelar" severity="secondary" @click="dialogVisible = false" />
        <Button label="Crear Vale" icon="pi pi-check" class="!bg-amber-600 !border-amber-600" @click="saveVale" :loading="loading" />
      </template>
    </Dialog>

    <!-- Dialog Desde Requisicion -->
    <Dialog v-model:visible="desdeRequisicionDialogVisible" header="Crear Vale desde Requisicion" :modal="true" :style="{ width: '90vw', maxWidth: '900px', maxHeight: '90vh' }" :contentStyle="{ overflow: 'auto', maxHeight: 'calc(90vh - 120px)' }">
      <div class="space-y-4">
        <Message severity="info" :closable="false">Seleccione una requisicion aprobada para generar el vale de salida</Message>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Almacen de Despacho *</label>
            <template v-if="almacenAsignado">
              <InputText :modelValue="nombreAlmacenAsignado || 'Almacén asignado'" class="w-full" disabled />
              <p class="text-xs text-gray-500 mt-1">Tu almacén</p>
            </template>
            <Select v-else v-model="formData.almacen_id" :options="almacenes" optionLabel="label" optionValue="value" placeholder="Seleccione" class="w-full">
              <template #value="slotProps">
                <span v-if="slotProps.value">{{ almacenes.find(a => a.value === slotProps.value)?.label }}</span>
                <span v-else class="text-gray-400">{{ slotProps.placeholder }}</span>
              </template>
            </Select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Receptor *</label>
            <AutoComplete
              v-model="formData.receptor"
              :suggestions="receptorSuggestions"
              optionLabel="display_name"
              placeholder="Buscar trabajador o usuario..."
              class="w-full"
              @complete="searchReceptores"
              @item-select="onSelectReceptor"
              forceSelection
              dropdown
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">DNI</label>
            <InputText v-model="formData.receptor_dni" placeholder="Autocompletado" class="w-full" readonly />
          </div>
        </div>

        <Divider />

        <DataTable v-model:selection="selectedRequisicion" :value="requisicionesAprobadas" selectionMode="single" dataKey="id" :paginator="true" :rows="5" responsiveLayout="scroll" class="text-sm">
          <Column selectionMode="single" style="width: 50px" />
          <Column field="numero" header="Requisicion" style="width: 140px">
            <template #body="{ data }"><span class="font-mono text-blue-600">{{ data.numero }}</span></template>
          </Column>
          <Column field="solicitante.nombre" header="Solicitante" />
          <Column field="centro_costo.nombre" header="Centro Costo" />
          <Column header="Productos Pendientes">
            <template #body="{ data }">
              <div class="text-xs space-y-1">
                <div v-for="det in data.detalles.filter(d => d.pendiente > 0)" :key="det.id">
                  {{ det.producto?.codigo }}: {{ det.pendiente }} {{ det.producto?.unidad_medida }}
                </div>
                <p v-if="data.detalles.filter(d => d.pendiente > 0).length === 0" class="text-gray-400">Sin pendientes</p>
              </div>
            </template>
          </Column>
        </DataTable>
      </div>

      <template #footer>
        <Button label="Cancelar" severity="secondary" @click="desdeRequisicionDialogVisible = false" />
        <Button label="Crear Vale" icon="pi pi-check" class="!bg-amber-600 !border-amber-600" @click="crearDesdeRequisicion" :loading="loading" :disabled="!selectedRequisicion" />
      </template>
    </Dialog>

    <!-- Dialog Ver Detalle -->
    <Dialog v-model:visible="viewDialogVisible" header="Detalle del Vale" :modal="true" :style="{ width: '90vw', maxWidth: '700px', maxHeight: '90vh' }" :contentStyle="{ overflow: 'auto', maxHeight: 'calc(90vh - 120px)' }">
      <div v-if="selectedVale" class="space-y-4">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 bg-gray-50 p-4 rounded-lg">
          <div>
            <p class="text-xs text-gray-500">Numero</p>
            <p class="font-mono font-semibold text-blue-600">{{ selectedVale.numero }}</p>
          </div>
          <div>
            <p class="text-xs text-gray-500">Estado</p>
            <Tag :value="selectedVale.estado" :severity="getEstadoSeverity(selectedVale.estado)" />
          </div>
          <div>
            <p class="text-xs text-gray-500">Fecha</p>
            <p class="font-medium">{{ formatDateDisplay(selectedVale.fecha) }}</p>
          </div>
          <div>
            <p class="text-xs text-gray-500">Almacen</p>
            <p class="font-medium">{{ selectedVale.almacen?.nombre }}</p>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <p class="text-xs text-gray-500">Receptor</p>
            <p class="font-medium">{{ selectedVale.receptor_nombre }}</p>
          </div>
          <div>
            <p class="text-xs text-gray-500">Centro de Costo</p>
            <p class="font-medium">{{ selectedVale.centro_costo?.nombre }}</p>
          </div>
        </div>

        <div v-if="selectedVale.requisicion" class="bg-blue-50 p-3 rounded">
          <p class="text-xs text-blue-600">Requisicion Origen</p>
          <p class="text-blue-800 font-mono">{{ selectedVale.requisicion.numero }}</p>
        </div>

        <Divider />

        <DataTable :value="selectedVale.detalles" size="small" stripedRows>
          <Column field="producto.codigo" header="Codigo" style="width: 100px" />
          <Column field="producto.nombre" header="Producto" />
          <Column header="Solicitado" style="width: 100px" class="text-right">
            <template #body="{ data }">{{ data.cantidad_solicitada }}</template>
          </Column>
          <Column header="Entregado" style="width: 100px" class="text-right">
            <template #body="{ data }">
              <span :class="data.cantidad_entregada >= data.cantidad_solicitada ? 'text-green-600' : 'text-yellow-600'">{{ data.cantidad_entregada }}</span>
            </template>
          </Column>
          <Column header="Costo" style="width: 100px" class="text-right">
            <template #body="{ data }">{{ formatCurrency(data.costo_total) }}</template>
          </Column>
        </DataTable>

        <div class="text-right font-semibold">
          Total: {{ formatCurrency(selectedVale.detalles?.reduce((sum, d) => sum + parseFloat(d.costo_total || 0), 0)) }}
        </div>
      </div>

      <template #footer>
        <Button label="Cerrar" severity="secondary" @click="viewDialogVisible = false" />
      </template>
    </Dialog>

    <!-- Dialog Entregar -->
    <Dialog v-model:visible="entregarDialogVisible" header="Procesar Entrega" :modal="true" :style="{ width: '90vw', maxWidth: '700px', maxHeight: '90vh' }" :contentStyle="{ overflow: 'auto', maxHeight: 'calc(90vh - 120px)' }">
      <div class="space-y-4">
        <Message severity="info" :closable="false">Confirme las cantidades a entregar. Esto descontara del inventario.</Message>

        <DataTable :value="entregaData" size="small">
          <Column style="width: 50px">
            <template #body="{ data }">
              <Checkbox v-model="data.entregar" :binary="true" />
            </template>
          </Column>
          <Column field="producto.codigo" header="Codigo" style="width: 100px" />
          <Column field="producto.nombre" header="Producto" />
          <Column header="Pendiente" style="width: 100px" class="text-right">
            <template #body="{ data }">{{ data.cantidad_solicitada - data.cantidad_entregada }}</template>
          </Column>
          <Column header="Entregar" style="width: 120px">
            <template #body="{ data }">
              <InputNumber v-model="data.cantidad_entregar" :min="0" :max="data.cantidad_solicitada - data.cantidad_entregada" :disabled="!data.entregar" class="w-full" size="small" />
            </template>
          </Column>
        </DataTable>
      </div>

      <template #footer>
        <Button label="Cancelar" severity="secondary" @click="entregarDialogVisible = false" />
        <Button label="Confirmar Entrega" icon="pi pi-check" severity="success" @click="procesarEntrega" :loading="loading" />
      </template>
    </Dialog>
  </div>
</template>
