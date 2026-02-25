<script setup>
import { ref, computed, onMounted } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useAuthStore } from '@/stores/auth'
import api from '@/services/api'

// PrimeVue Components
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import Select from 'primevue/select'
import AutoComplete from 'primevue/autocomplete'
import DatePicker from 'primevue/datepicker'
import InputNumber from 'primevue/inputnumber'
import Card from 'primevue/card'
import Dialog from 'primevue/dialog'
import Tag from 'primevue/tag'
import Tabs from 'primevue/tabs'
import TabList from 'primevue/tablist'
import Tab from 'primevue/tab'
import TabPanels from 'primevue/tabpanels'
import TabPanel from 'primevue/tabpanel'
import Divider from 'primevue/divider'
import Message from 'primevue/message'

const toast = useToast()
const authStore = useAuthStore()

// Centro de costo asignado del usuario (para usuarios restringidos)
const centroCostoAsignado = computed(() => authStore.user?.centro_costo_id || null)
const almacenAsignado = computed(() => authStore.user?.almacen_id || null)
const nombreCentroCostoAsignado = computed(() => {
  if (authStore.user?.centro_costo) {
    const cc = authStore.user.centro_costo
    return cc.nombre || 'Centro de costo asignado'
  }
  if (!centroCostoAsignado.value || centrosCosto.value.length === 0) return null
  const cc = centrosCosto.value.find(c => c.value === centroCostoAsignado.value)
  return cc?.label || null
})

// Estado
const loading = ref(false)
const requisiciones = ref([])
const totalRecords = ref(0)

// Filtros
const searchQuery = ref('')
const selectedEstado = ref(null)
const selectedPrioridad = ref(null)
const showMisRequisiciones = ref(false)
const showPendientesAprobar = ref(false)

// Dialogos
const dialogVisible = ref(false)
const viewDialogVisible = ref(false)
const aprobarDialogVisible = ref(false)
const rechazarDialogVisible = ref(false)
const isEditing = ref(false)

// Datos
const selectedRequisicion = ref(null)
const centrosCosto = ref([])
const almacenes = ref([])
const productoSuggestions = ref([])
const estadisticas = ref({})

// Formulario
const formData = ref({
  centro_costo_id: null,
  almacen_id: null,
  fecha_requerida: null,
  prioridad: 'NORMAL',
  motivo: '',
  observaciones: '',
  detalles: []
})

// Formulario de aprobacion/rechazo
const comentarioAprobacion = ref('')

// Opciones
const estados = ref([
  { label: 'Todos', value: null },
  { label: 'Borrador', value: 'BORRADOR' },
  { label: 'Pendiente', value: 'PENDIENTE' },
  { label: 'Aprobada', value: 'APROBADA' },
  { label: 'Rechazada', value: 'RECHAZADA' },
  { label: 'Parcial', value: 'PARCIAL' },
  { label: 'Completada', value: 'COMPLETADA' },
  { label: 'Anulada', value: 'ANULADA' }
])

const prioridades = ref([
  { label: 'Todas', value: null },
  { label: 'Baja', value: 'BAJA' },
  { label: 'Normal', value: 'NORMAL' },
  { label: 'Alta', value: 'ALTA' },
  { label: 'Urgente', value: 'URGENTE' }
])

const prioridadesForm = ref([
  { label: 'Baja', value: 'BAJA' },
  { label: 'Normal', value: 'NORMAL' },
  { label: 'Alta', value: 'ALTA' },
  { label: 'Urgente', value: 'URGENTE' }
])

// Cargar datos
const loadRequisiciones = async () => {
  loading.value = true
  try {
    const params = {}
    if (searchQuery.value) params.search = searchQuery.value
    if (selectedEstado.value) params.estado = selectedEstado.value
    if (selectedPrioridad.value) params.prioridad = selectedPrioridad.value
    if (showMisRequisiciones.value) params.mis_requisiciones = true
    if (showPendientesAprobar.value) params.pendientes_aprobar = true

    const response = await api.get('/requisiciones', { params })
    if (response.data.success) {
      requisiciones.value = response.data.data || []
      totalRecords.value = response.data.meta?.total || requisiciones.value.length
    }
  } catch (err) {
    console.error('Error al cargar requisiciones:', err)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'No se pudieron cargar las requisiciones',
      life: 5000
    })
  } finally {
    loading.value = false
  }
}

const loadCentrosCosto = async () => {
  try {
    const response = await api.get('/administracion/centros-costo', { params: { all: true } })
    if (response.data.success) {
      centrosCosto.value = response.data.data.map(c => ({
        label: c.nombre,
        value: c.id
      }))
    }
  } catch (err) {
    console.error('Error al cargar centros de costo:', err)
  }
}

const loadAlmacenes = async () => {
  try {
    const response = await api.get('/administracion/almacenes', { params: { all: true } })
    if (response.data.success) {
      almacenes.value = response.data.data.map(a => ({
        label: a.nombre,
        value: a.id
      }))
    }
  } catch (err) {
    console.error('Error al cargar almacenes:', err)
  }
}

const loadEstadisticas = async () => {
  try {
    const response = await api.get('/requisiciones/estadisticas')
    if (response.data.success) {
      estadisticas.value = response.data.data
    }
  } catch (err) {
    console.error('Error al cargar estadisticas:', err)
  }
}

const searchProductos = async (event) => {
  try {
    const almacenId = almacenAsignado.value || formData.value.almacen_id || null
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
        label: `${p.codigo} - ${p.nombre}`
      }))
    }
  } catch (err) {
    productoSuggestions.value = []
  }
}

// Acciones
const openNewDialog = () => {
  formData.value = {
    centro_costo_id: centroCostoAsignado.value || null,
    almacen_id: almacenAsignado.value || null,
    fecha_requerida: new Date(Date.now() + 7 * 24 * 60 * 60 * 1000), // +7 dias
    prioridad: 'NORMAL',
    motivo: '',
    observaciones: '',
    detalles: []
  }
  isEditing.value = false
  dialogVisible.value = true
}

const editRequisicion = async (requisicion) => {
  try {
    const response = await api.get(`/requisiciones/${requisicion.id}`)
    if (response.data.success) {
      const data = response.data.data
      formData.value = {
        id: data.id,
        centro_costo_id: data.centro_costo_id,
        almacen_id: data.almacen_id,
        fecha_requerida: new Date(data.fecha_requerida),
        prioridad: data.prioridad,
        motivo: data.motivo,
        observaciones: data.observaciones || '',
        detalles: data.detalles.map(d => ({
          id: d.id,
          producto: {
            id: d.producto.id,
            codigo: d.producto.codigo,
            nombre: d.producto.nombre,
            unidad: d.producto.unidad_medida,
            label: `${d.producto.codigo} - ${d.producto.nombre}`
          },
          cantidad_solicitada: parseFloat(d.cantidad_solicitada),
          especificaciones: d.especificaciones || ''
        }))
      }
      isEditing.value = true
      dialogVisible.value = true
    }
  } catch (err) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'No se pudo cargar la requisicion',
      life: 5000
    })
  }
}

const viewRequisicion = async (requisicion) => {
  try {
    const response = await api.get(`/requisiciones/${requisicion.id}`)
    if (response.data.success) {
      selectedRequisicion.value = response.data.data
      viewDialogVisible.value = true
    }
  } catch (err) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'No se pudo cargar la requisicion',
      life: 5000
    })
  }
}

const addDetalle = () => {
  formData.value.detalles.push({
    producto: null,
    cantidad_solicitada: 1,
    especificaciones: ''
  })
}

const removeDetalle = (index) => {
  formData.value.detalles.splice(index, 1)
}

const saveRequisicion = async (enviarAprobacion = false) => {
  // Validaciones
  if (!formData.value.centro_costo_id) {
    toast.add({ severity: 'warn', summary: 'Validacion', detail: 'Seleccione un centro de costo', life: 4000 })
    return
  }
  if (!formData.value.fecha_requerida) {
    toast.add({ severity: 'warn', summary: 'Validacion', detail: 'Seleccione la fecha requerida', life: 4000 })
    return
  }
  if (!formData.value.motivo?.trim()) {
    toast.add({ severity: 'warn', summary: 'Validacion', detail: 'Ingrese el motivo de la requisicion', life: 4000 })
    return
  }
  if (formData.value.detalles.length === 0) {
    toast.add({ severity: 'warn', summary: 'Validacion', detail: 'Agregue al menos un producto', life: 4000 })
    return
  }

  // Validar detalles
  for (let i = 0; i < formData.value.detalles.length; i++) {
    const det = formData.value.detalles[i]
    if (!det.producto?.id) {
      toast.add({ severity: 'warn', summary: 'Validacion', detail: `Seleccione un producto en la linea ${i + 1}`, life: 4000 })
      return
    }
    if (!det.cantidad_solicitada || det.cantidad_solicitada <= 0) {
      toast.add({ severity: 'warn', summary: 'Validacion', detail: `Ingrese cantidad valida en la linea ${i + 1}`, life: 4000 })
      return
    }
  }

  loading.value = true
  try {
    // Usar centro de costo asignado si existe
    const ccId = centroCostoAsignado.value || formData.value.centro_costo_id

    const dataToSend = {
      centro_costo_id: ccId,
      almacen_id: formData.value.almacen_id,
      fecha_requerida: formatDate(formData.value.fecha_requerida),
      prioridad: formData.value.prioridad,
      motivo: formData.value.motivo,
      observaciones: formData.value.observaciones || null,
      enviar_aprobacion: enviarAprobacion,
      detalles: formData.value.detalles.map(d => ({
        producto_id: d.producto.id,
        cantidad_solicitada: d.cantidad_solicitada,
        especificaciones: d.especificaciones || null
      }))
    }

    let response
    if (isEditing.value) {
      response = await api.put(`/requisiciones/${formData.value.id}`, dataToSend)
    } else {
      response = await api.post('/requisiciones', dataToSend)
    }

    if (response.data.success) {
      toast.add({
        severity: 'success',
        summary: 'Exito',
        detail: response.data.message || 'Requisicion guardada correctamente',
        life: 3000
      })
      dialogVisible.value = false
      loadRequisiciones()
      loadEstadisticas()
    }
  } catch (err) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: err.response?.data?.message || 'Error al guardar la requisicion',
      life: 5000
    })
  } finally {
    loading.value = false
  }
}

const enviarAprobacion = async (requisicion) => {
  try {
    const response = await api.post(`/requisiciones/${requisicion.id}/enviar-aprobacion`)
    if (response.data.success) {
      toast.add({ severity: 'success', summary: 'Exito', detail: 'Requisicion enviada a aprobacion', life: 3000 })
      loadRequisiciones()
      loadEstadisticas()
    }
  } catch (err) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: err.response?.data?.message || 'Error al enviar a aprobacion',
      life: 5000
    })
  }
}

const openAprobarDialog = (requisicion) => {
  selectedRequisicion.value = requisicion
  comentarioAprobacion.value = ''
  aprobarDialogVisible.value = true
}

const openRechazarDialog = (requisicion) => {
  selectedRequisicion.value = requisicion
  comentarioAprobacion.value = ''
  rechazarDialogVisible.value = true
}

const aprobarRequisicion = async () => {
  try {
    const response = await api.post(`/requisiciones/${selectedRequisicion.value.id}/aprobar`, {
      comentario: comentarioAprobacion.value || null
    })
    if (response.data.success) {
      toast.add({ severity: 'success', summary: 'Exito', detail: 'Requisicion aprobada', life: 3000 })
      aprobarDialogVisible.value = false
      loadRequisiciones()
      loadEstadisticas()
    }
  } catch (err) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: err.response?.data?.message || 'Error al aprobar',
      life: 5000
    })
  }
}

const rechazarRequisicion = async () => {
  if (!comentarioAprobacion.value?.trim()) {
    toast.add({ severity: 'warn', summary: 'Validacion', detail: 'Ingrese el motivo del rechazo', life: 4000 })
    return
  }
  try {
    const response = await api.post(`/requisiciones/${selectedRequisicion.value.id}/rechazar`, {
      comentario: comentarioAprobacion.value
    })
    if (response.data.success) {
      toast.add({ severity: 'warn', summary: 'Rechazada', detail: 'Requisicion rechazada', life: 3000 })
      rechazarDialogVisible.value = false
      loadRequisiciones()
      loadEstadisticas()
    }
  } catch (err) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: err.response?.data?.message || 'Error al rechazar',
      life: 5000
    })
  }
}

const anularRequisicion = async (requisicion) => {
  if (!confirm('¿Esta seguro de anular esta requisicion?')) return
  try {
    const response = await api.post(`/requisiciones/${requisicion.id}/anular`)
    if (response.data.success) {
      toast.add({ severity: 'warn', summary: 'Anulada', detail: 'Requisicion anulada', life: 3000 })
      loadRequisiciones()
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

const getEstadoSeverity = (estado) => {
  const map = {
    'BORRADOR': 'secondary',
    'PENDIENTE': 'warn',
    'APROBADA': 'success',
    'RECHAZADA': 'danger',
    'PARCIAL': 'info',
    'COMPLETADA': 'success',
    'ANULADA': 'secondary'
  }
  return map[estado] || 'secondary'
}

const getPrioridadSeverity = (prioridad) => {
  const map = {
    'BAJA': 'secondary',
    'NORMAL': 'info',
    'ALTA': 'warn',
    'URGENTE': 'danger'
  }
  return map[prioridad] || 'info'
}

const canEdit = (requisicion) => {
  return ['BORRADOR', 'RECHAZADA'].includes(requisicion.estado)
}

const canApprove = (requisicion) => {
  return requisicion.estado === 'PENDIENTE'
}

const canAnular = (requisicion) => {
  return !['ANULADA', 'COMPLETADA'].includes(requisicion.estado)
}

// Computed
const minDate = computed(() => new Date())

onMounted(() => {
  loadRequisiciones()
  loadCentrosCosto()
  loadAlmacenes()
  loadEstadisticas()
})
</script>

<template>
  <div class="space-y-4">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Requisiciones</h1>
        <p class="text-gray-600 text-sm mt-1">Solicitudes de materiales y suministros</p>
      </div>
      <Button
        label="Nueva Requisicion"
        icon="pi pi-plus"
        class="!bg-amber-600 !border-amber-600"
        @click="openNewDialog"
      />
    </div>

    <!-- Estadisticas -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
      <Card class="!bg-blue-50">
        <template #content>
          <div class="text-center">
            <p class="text-2xl font-bold text-blue-700">{{ estadisticas.total || 0 }}</p>
            <p class="text-sm text-blue-600">Total</p>
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
            <p class="text-2xl font-bold text-green-700">{{ estadisticas.aprobadas || 0 }}</p>
            <p class="text-sm text-green-600">Aprobadas</p>
          </div>
        </template>
      </Card>
      <Card class="!bg-purple-50">
        <template #content>
          <div class="text-center">
            <p class="text-2xl font-bold text-purple-700">{{ estadisticas.mis_pendientes || 0 }}</p>
            <p class="text-sm text-purple-600">Mis Pendientes</p>
          </div>
        </template>
      </Card>
      <Card class="!bg-red-50">
        <template #content>
          <div class="text-center">
            <p class="text-2xl font-bold text-red-700">{{ estadisticas.urgentes || 0 }}</p>
            <p class="text-sm text-red-600">Urgentes</p>
          </div>
        </template>
      </Card>
    </div>

    <!-- Filtros -->
    <Card>
      <template #content>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div>
            <InputText
              v-model="searchQuery"
              placeholder="Buscar por numero o motivo..."
              class="w-full"
              @keyup.enter="loadRequisiciones"
            />
          </div>
          <div>
            <Select
              v-model="selectedEstado"
              :options="estados"
              optionLabel="label"
              optionValue="value"
              placeholder="Estado"
              class="w-full"
              @change="loadRequisiciones"
            >
              <template #value="slotProps">
                <span v-if="slotProps.value !== null && slotProps.value !== undefined">{{ estados.find(e => e.value === slotProps.value)?.label }}</span>
                <span v-else class="text-gray-400">{{ slotProps.placeholder }}</span>
              </template>
            </Select>
          </div>
          <div>
            <Select
              v-model="selectedPrioridad"
              :options="prioridades"
              optionLabel="label"
              optionValue="value"
              placeholder="Prioridad"
              class="w-full"
              @change="loadRequisiciones"
            >
              <template #value="slotProps">
                <span v-if="slotProps.value !== null && slotProps.value !== undefined">{{ prioridades.find(p => p.value === slotProps.value)?.label }}</span>
                <span v-else class="text-gray-400">{{ slotProps.placeholder }}</span>
              </template>
            </Select>
          </div>
          <div class="flex gap-2">
            <Button
              label="Buscar"
              icon="pi pi-search"
              class="!bg-amber-600 !border-amber-600"
              @click="loadRequisiciones"
            />
            <Button
              icon="pi pi-refresh"
              severity="secondary"
              outlined
              @click="searchQuery = ''; selectedEstado = null; selectedPrioridad = null; loadRequisiciones()"
            />
          </div>
        </div>
      </template>
    </Card>

    <!-- Tabla -->
    <Card>
      <template #content>
        <DataTable
          :value="requisiciones"
          :loading="loading"
          :paginator="true"
          :rows="15"
          :rowsPerPageOptions="[10, 15, 25, 50]"
          responsiveLayout="scroll"
          stripedRows
          class="text-sm"
        >
          <template #empty>
            <div class="text-center py-8 text-gray-500">
              <i class="pi pi-file-edit text-4xl mb-2"></i>
              <p>No se encontraron requisiciones</p>
            </div>
          </template>

          <Column field="numero" header="Numero" style="width: 140px">
            <template #body="{ data }">
              <span class="font-mono text-blue-600 font-medium">{{ data.numero }}</span>
            </template>
          </Column>

          <Column field="fecha_solicitud" header="Fecha" style="width: 100px">
            <template #body="{ data }">
              {{ formatDateDisplay(data.fecha_solicitud) }}
            </template>
          </Column>

          <Column field="solicitante.nombre" header="Solicitante" style="min-width: 150px" />

          <Column field="centro_costo.nombre" header="Centro Costo" style="min-width: 120px" />

          <Column field="motivo" header="Motivo" style="min-width: 200px">
            <template #body="{ data }">
              <span class="line-clamp-2">{{ data.motivo }}</span>
            </template>
          </Column>

          <Column field="prioridad" header="Prioridad" style="width: 100px">
            <template #body="{ data }">
              <Tag :value="data.prioridad" :severity="getPrioridadSeverity(data.prioridad)" />
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

          <Column header="Acciones" style="width: 180px">
            <template #body="{ data }">
              <div class="flex gap-1">
                <Button
                  icon="pi pi-eye"
                  severity="info"
                  text
                  rounded
                  size="small"
                  @click="viewRequisicion(data)"
                  v-tooltip.top="'Ver detalle'"
                />
                <Button
                  v-if="canEdit(data)"
                  icon="pi pi-pencil"
                  severity="secondary"
                  text
                  rounded
                  size="small"
                  @click="editRequisicion(data)"
                  v-tooltip.top="'Editar'"
                />
                <Button
                  v-if="data.estado === 'BORRADOR'"
                  icon="pi pi-send"
                  severity="success"
                  text
                  rounded
                  size="small"
                  @click="enviarAprobacion(data)"
                  v-tooltip.top="'Enviar a aprobacion'"
                />
                <Button
                  v-if="canApprove(data)"
                  icon="pi pi-check"
                  severity="success"
                  text
                  rounded
                  size="small"
                  @click="openAprobarDialog(data)"
                  v-tooltip.top="'Aprobar'"
                />
                <Button
                  v-if="canApprove(data)"
                  icon="pi pi-times"
                  severity="danger"
                  text
                  rounded
                  size="small"
                  @click="openRechazarDialog(data)"
                  v-tooltip.top="'Rechazar'"
                />
                <Button
                  v-if="canAnular(data)"
                  icon="pi pi-ban"
                  severity="warn"
                  text
                  rounded
                  size="small"
                  @click="anularRequisicion(data)"
                  v-tooltip.top="'Anular'"
                />
              </div>
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>

    <!-- Dialog Crear/Editar -->
    <Dialog
      v-model:visible="dialogVisible"
      :header="isEditing ? 'Editar Requisicion' : 'Nueva Requisicion'"
      :modal="true"
      :style="{ width: '90vw', maxWidth: '900px', maxHeight: '90vh' }"
      :contentStyle="{ overflow: 'auto', maxHeight: 'calc(90vh - 120px)' }"
    >
      <div class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Centro de Costo *</label>
            <!-- Si el usuario tiene centro de costo asignado, mostrar bloqueado -->
            <template v-if="centroCostoAsignado">
              <InputText
                :modelValue="nombreCentroCostoAsignado || 'Centro de costo asignado'"
                class="w-full"
                disabled
              />
              <p class="text-xs text-gray-500 mt-1">Asignado a tu proyecto</p>
            </template>
            <Select
              v-else
              v-model="formData.centro_costo_id"
              :options="centrosCosto"
              optionLabel="label"
              optionValue="value"
              placeholder="Seleccione"
              class="w-full"
            >
              <template #value="slotProps">
                <span v-if="slotProps.value">{{ centrosCosto.find(c => c.value === slotProps.value)?.label }}</span>
                <span v-else class="text-gray-400">{{ slotProps.placeholder }}</span>
              </template>
            </Select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Almacen Destino</label>
            <Select
              v-model="formData.almacen_id"
              :options="almacenes"
              optionLabel="label"
              optionValue="value"
              placeholder="Seleccione (opcional)"
              class="w-full"
              showClear
            >
              <template #value="slotProps">
                <span v-if="slotProps.value">{{ almacenes.find(a => a.value === slotProps.value)?.label }}</span>
                <span v-else class="text-gray-400">{{ slotProps.placeholder }}</span>
              </template>
            </Select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Fecha Requerida *</label>
            <DatePicker
              v-model="formData.fecha_requerida"
              dateFormat="dd/mm/yy"
              :minDate="minDate"
              placeholder="Seleccione fecha"
              class="w-full"
              showIcon
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Prioridad *</label>
            <Select
              v-model="formData.prioridad"
              :options="prioridadesForm"
              optionLabel="label"
              optionValue="value"
              class="w-full"
            >
              <template #value="slotProps">
                <span v-if="slotProps.value">{{ prioridadesForm.find(p => p.value === slotProps.value)?.label }}</span>
                <span v-else class="text-gray-400">{{ slotProps.placeholder }}</span>
              </template>
            </Select>
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Motivo de la Requisicion *</label>
          <Textarea
            v-model="formData.motivo"
            rows="2"
            class="w-full"
            placeholder="Describa el motivo de la solicitud..."
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Observaciones</label>
          <Textarea
            v-model="formData.observaciones"
            rows="2"
            class="w-full"
            placeholder="Observaciones adicionales (opcional)"
          />
        </div>

        <Divider />

        <!-- Detalles -->
        <div>
          <div class="flex items-center justify-between mb-2">
            <label class="text-sm font-medium text-gray-700">Productos Solicitados *</label>
            <Button
              label="Agregar Producto"
              icon="pi pi-plus"
              size="small"
              severity="secondary"
              @click="addDetalle"
            />
          </div>

          <div v-if="formData.detalles.length === 0" class="text-center py-4 text-gray-500 border rounded">
            <i class="pi pi-inbox text-2xl mb-2"></i>
            <p>No hay productos agregados</p>
          </div>

          <div v-else class="space-y-2">
            <div
              v-for="(detalle, index) in formData.detalles"
              :key="index"
              class="grid grid-cols-12 gap-2 items-end p-3 bg-gray-50 rounded"
            >
              <div class="col-span-5">
                <label class="block text-xs text-gray-500 mb-1">Producto</label>
                <AutoComplete
                  v-model="detalle.producto"
                  :suggestions="productoSuggestions"
                  optionLabel="label"
                  placeholder="Buscar producto..."
                  class="w-full"
                  @complete="searchProductos"
                />
              </div>
              <div class="col-span-2">
                <label class="block text-xs text-gray-500 mb-1">Cantidad</label>
                <InputNumber
                  v-model="detalle.cantidad_solicitada"
                  :min="0.01"
                  :minFractionDigits="0"
                  :maxFractionDigits="2"
                  class="w-full"
                />
              </div>
              <div class="col-span-1 text-center">
                <label class="block text-xs text-gray-500 mb-1">Und</label>
                <span class="text-sm">{{ detalle.producto?.unidad || '-' }}</span>
              </div>
              <div class="col-span-3">
                <label class="block text-xs text-gray-500 mb-1">Especificaciones</label>
                <InputText
                  v-model="detalle.especificaciones"
                  placeholder="Opcional"
                  class="w-full"
                />
              </div>
              <div class="col-span-1">
                <Button
                  icon="pi pi-trash"
                  severity="danger"
                  text
                  rounded
                  @click="removeDetalle(index)"
                />
              </div>
            </div>
          </div>
        </div>
      </div>

      <template #footer>
        <Button label="Cancelar" severity="secondary" @click="dialogVisible = false" />
        <Button
          label="Guardar Borrador"
          icon="pi pi-save"
          severity="secondary"
          outlined
          @click="saveRequisicion(false)"
          :loading="loading"
        />
        <Button
          label="Guardar y Enviar"
          icon="pi pi-send"
          class="!bg-amber-600 !border-amber-600"
          @click="saveRequisicion(true)"
          :loading="loading"
        />
      </template>
    </Dialog>

    <!-- Dialog Ver Detalle -->
    <Dialog
      v-model:visible="viewDialogVisible"
      header="Detalle de Requisicion"
      :modal="true"
      :style="{ width: '90vw', maxWidth: '800px', maxHeight: '90vh' }"
      :contentStyle="{ overflow: 'auto', maxHeight: 'calc(90vh - 120px)' }"
    >
      <div v-if="selectedRequisicion" class="space-y-4">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 bg-gray-50 p-4 rounded-lg">
          <div>
            <p class="text-xs text-gray-500">Numero</p>
            <p class="font-mono font-semibold text-blue-600">{{ selectedRequisicion.numero }}</p>
          </div>
          <div>
            <p class="text-xs text-gray-500">Estado</p>
            <Tag :value="selectedRequisicion.estado" :severity="getEstadoSeverity(selectedRequisicion.estado)" />
          </div>
          <div>
            <p class="text-xs text-gray-500">Prioridad</p>
            <Tag :value="selectedRequisicion.prioridad" :severity="getPrioridadSeverity(selectedRequisicion.prioridad)" />
          </div>
          <div>
            <p class="text-xs text-gray-500">Fecha Requerida</p>
            <p class="font-medium">{{ formatDateDisplay(selectedRequisicion.fecha_requerida) }}</p>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <p class="text-xs text-gray-500">Solicitante</p>
            <p class="font-medium">{{ selectedRequisicion.solicitante?.nombre }}</p>
          </div>
          <div>
            <p class="text-xs text-gray-500">Centro de Costo</p>
            <p class="font-medium">{{ selectedRequisicion.centro_costo?.nombre }}</p>
          </div>
        </div>

        <div>
          <p class="text-xs text-gray-500">Motivo</p>
          <p>{{ selectedRequisicion.motivo }}</p>
        </div>

        <div v-if="selectedRequisicion.observaciones">
          <p class="text-xs text-gray-500">Observaciones</p>
          <p>{{ selectedRequisicion.observaciones }}</p>
        </div>

        <div v-if="selectedRequisicion.comentario_aprobacion" class="bg-blue-50 p-3 rounded">
          <p class="text-xs text-blue-600">Comentario de {{ selectedRequisicion.estado === 'APROBADA' ? 'Aprobacion' : 'Rechazo' }}</p>
          <p class="text-blue-800">{{ selectedRequisicion.comentario_aprobacion }}</p>
          <p class="text-xs text-blue-500 mt-1">Por: {{ selectedRequisicion.aprobador?.nombre }} - {{ formatDateDisplay(selectedRequisicion.fecha_aprobacion) }}</p>
        </div>

        <Divider />

        <DataTable :value="selectedRequisicion.detalles" size="small" stripedRows>
          <Column field="producto.codigo" header="Codigo" style="width: 100px" />
          <Column field="producto.nombre" header="Producto" />
          <Column header="Solicitado" style="width: 100px" class="text-right">
            <template #body="{ data }">
              {{ data.cantidad_solicitada }} {{ data.producto?.unidad_medida }}
            </template>
          </Column>
          <Column header="Aprobado" style="width: 100px" class="text-right">
            <template #body="{ data }">
              <span v-if="data.cantidad_aprobada !== null">
                {{ data.cantidad_aprobada }} {{ data.producto?.unidad_medida }}
              </span>
              <span v-else class="text-gray-400">-</span>
            </template>
          </Column>
          <Column header="Entregado" style="width: 100px" class="text-right">
            <template #body="{ data }">
              {{ data.cantidad_entregada || 0 }} {{ data.producto?.unidad_medida }}
            </template>
          </Column>
        </DataTable>
      </div>

      <template #footer>
        <Button label="Cerrar" severity="secondary" @click="viewDialogVisible = false" />
      </template>
    </Dialog>

    <!-- Dialog Aprobar -->
    <Dialog
      v-model:visible="aprobarDialogVisible"
      header="Aprobar Requisicion"
      :modal="true"
      :style="{ width: '450px' }"
    >
      <div class="space-y-4">
        <Message severity="info" :closable="false">
          Se aprobaran todas las cantidades solicitadas
        </Message>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Comentario (opcional)</label>
          <Textarea
            v-model="comentarioAprobacion"
            rows="3"
            class="w-full"
            placeholder="Comentario de aprobacion..."
          />
        </div>
      </div>
      <template #footer>
        <Button label="Cancelar" severity="secondary" @click="aprobarDialogVisible = false" />
        <Button label="Aprobar" icon="pi pi-check" severity="success" @click="aprobarRequisicion" />
      </template>
    </Dialog>

    <!-- Dialog Rechazar -->
    <Dialog
      v-model:visible="rechazarDialogVisible"
      header="Rechazar Requisicion"
      :modal="true"
      :style="{ width: '450px' }"
    >
      <div class="space-y-4">
        <Message severity="warn" :closable="false">
          La requisicion sera devuelta al solicitante
        </Message>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Motivo del rechazo *</label>
          <Textarea
            v-model="comentarioAprobacion"
            rows="3"
            class="w-full"
            placeholder="Explique el motivo del rechazo..."
          />
        </div>
      </div>
      <template #footer>
        <Button label="Cancelar" severity="secondary" @click="rechazarDialogVisible = false" />
        <Button label="Rechazar" icon="pi pi-times" severity="danger" @click="rechazarRequisicion" />
      </template>
    </Dialog>
  </div>
</template>
