<script setup>
import { ref, computed, onMounted } from 'vue'
import { useToast } from 'primevue/usetoast'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import DatePicker from 'primevue/datepicker'
import Dialog from 'primevue/dialog'
import Tag from 'primevue/tag'
import InputNumber from 'primevue/inputnumber'
import Textarea from 'primevue/textarea'
import Card from 'primevue/card'
import Divider from 'primevue/divider'
import api from '@/services/api'

const toast = useToast()

// Estado
const cotizaciones = ref([])
const loading = ref(false)
const totalRecords = ref(0)
const estadisticas = ref({
  total: 0,
  pendientes: 0,
  por_aprobar: 0,
  valor_aprobadas_mes: 0
})

// Filtros
const filters = ref({
  search: '',
  estado: null,
  proveedor_id: null
})

const lazyParams = ref({
  first: 0,
  rows: 15,
  sortField: 'fecha_solicitud',
  sortOrder: -1
})

// Catálogos
const proveedores = ref([])
const productos = ref([])

const estados = [
  { label: 'Todos', value: null },
  { label: 'Borrador', value: 'BORRADOR' },
  { label: 'Enviada', value: 'ENVIADA' },
  { label: 'Recibida', value: 'RECIBIDA' },
  { label: 'Aprobada', value: 'APROBADA' },
  { label: 'Rechazada', value: 'RECHAZADA' },
  { label: 'Vencida', value: 'VENCIDA' },
  { label: 'Anulada', value: 'ANULADA' }
]

// Diálogos
const dialogForm = ref(false)
const dialogDetalle = ref(false)
const dialogRespuesta = ref(false)
const isEditing = ref(false)
const submitting = ref(false)

// Formulario cotización
const form = ref({
  proveedor_id: null,
  fecha_solicitud: new Date(),
  fecha_vencimiento: null,
  moneda: 'PEN',
  tipo_cambio: 1,
  condiciones_pago: '',
  tiempo_entrega_dias: null,
  observaciones: '',
  detalles: []
})

// Formulario ítem
const itemForm = ref({
  producto_id: null,
  cantidad: 1,
  precio_unitario: 0,
  descuento: 0,
  especificaciones: ''
})
const editingItemIndex = ref(-1)

// Cotización seleccionada
const selectedCotizacion = ref(null)

// Formulario respuesta proveedor
const respuestaForm = ref({
  detalles: [],
  condiciones_pago: '',
  tiempo_entrega_dias: null
})

const monedas = [
  { label: 'Soles (PEN)', value: 'PEN' },
  { label: 'Dólares (USD)', value: 'USD' }
]

// Computed
const formTitle = computed(() => isEditing.value ? 'Editar Cotización' : 'Nueva Cotización')

const subtotalForm = computed(() => {
  return form.value.detalles.reduce((sum, d) => {
    let sub = d.cantidad * d.precio_unitario
    if (d.descuento > 0) sub -= sub * (d.descuento / 100)
    return sum + sub
  }, 0)
})

const igvForm = computed(() => subtotalForm.value * 0.18)
const totalForm = computed(() => subtotalForm.value + igvForm.value)

// Métodos
const cargarCotizaciones = async () => {
  loading.value = true
  try {
    const params = {
      page: (lazyParams.value.first / lazyParams.value.rows) + 1,
      per_page: lazyParams.value.rows,
      sort_field: lazyParams.value.sortField,
      sort_order: lazyParams.value.sortOrder === 1 ? 'asc' : 'desc',
      ...filters.value
    }

    const response = await api.get('/cotizaciones', { params })
    cotizaciones.value = response.data.data
    totalRecords.value = response.data.meta?.total || response.data.total || 0
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Error al cargar cotizaciones', life: 3000 })
  } finally {
    loading.value = false
  }
}

const cargarEstadisticas = async () => {
  try {
    const response = await api.get('/cotizaciones/estadisticas')
    estadisticas.value = response.data.data
  } catch (error) {
    console.error('Error cargando estadísticas:', error)
  }
}

const cargarCatalogos = async () => {
  try {
    const [provRes, prodRes] = await Promise.all([
      api.get('/proveedores', { params: { per_page: 1000 } }),
      api.get('/inventario/productos', { params: { per_page: 1000 } })
    ])
    proveedores.value = provRes.data.data
    productos.value = prodRes.data.data
  } catch (error) {
    console.error('Error cargando catálogos:', error)
  }
}

const onPage = (event) => {
  lazyParams.value = { ...lazyParams.value, ...event }
  cargarCotizaciones()
}

const onSort = (event) => {
  lazyParams.value = { ...lazyParams.value, ...event }
  cargarCotizaciones()
}

const buscar = () => {
  lazyParams.value.first = 0
  cargarCotizaciones()
}

const limpiarFiltros = () => {
  filters.value = { search: '', estado: null, proveedor_id: null }
  buscar()
}

const nuevaCotizacion = () => {
  isEditing.value = false
  form.value = {
    proveedor_id: null,
    fecha_solicitud: new Date(),
    fecha_vencimiento: null,
    moneda: 'PEN',
    tipo_cambio: 1,
    condiciones_pago: '',
    tiempo_entrega_dias: null,
    observaciones: '',
    detalles: []
  }
  dialogForm.value = true
}

const editarCotizacion = async (cotizacion) => {
  try {
    const response = await api.get(`/cotizaciones/${cotizacion.id}`)
    const data = response.data.data

    isEditing.value = true
    form.value = {
      id: data.id,
      proveedor_id: data.proveedor_id,
      fecha_solicitud: new Date(data.fecha_solicitud),
      fecha_vencimiento: data.fecha_vencimiento ? new Date(data.fecha_vencimiento) : null,
      moneda: data.moneda,
      tipo_cambio: data.tipo_cambio,
      condiciones_pago: data.condiciones_pago || '',
      tiempo_entrega_dias: data.tiempo_entrega_dias,
      observaciones: data.observaciones || '',
      detalles: data.detalles.map(d => ({
        id: d.id,
        producto_id: d.producto_id,
        producto: d.producto,
        cantidad: parseFloat(d.cantidad),
        precio_unitario: parseFloat(d.precio_unitario),
        descuento: parseFloat(d.descuento) || 0,
        especificaciones: d.especificaciones || ''
      }))
    }
    dialogForm.value = true
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Error al cargar cotización', life: 3000 })
  }
}

const verDetalle = async (cotizacion) => {
  try {
    const response = await api.get(`/cotizaciones/${cotizacion.id}`)
    selectedCotizacion.value = response.data.data
    dialogDetalle.value = true
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Error al cargar detalle', life: 3000 })
  }
}

// Gestión de ítems
const agregarItem = () => {
  if (!itemForm.value.producto_id || itemForm.value.cantidad <= 0) {
    toast.add({ severity: 'warn', summary: 'Atención', detail: 'Seleccione producto y cantidad', life: 3000 })
    return
  }

  const producto = productos.value.find(p => p.id === itemForm.value.producto_id)
  const item = {
    producto_id: itemForm.value.producto_id,
    producto: producto,
    cantidad: itemForm.value.cantidad,
    precio_unitario: itemForm.value.precio_unitario,
    descuento: itemForm.value.descuento || 0,
    especificaciones: itemForm.value.especificaciones
  }

  if (editingItemIndex.value >= 0) {
    form.value.detalles[editingItemIndex.value] = item
    editingItemIndex.value = -1
  } else {
    form.value.detalles.push(item)
  }

  limpiarItemForm()
}

const editarItem = (index) => {
  const item = form.value.detalles[index]
  itemForm.value = {
    producto_id: item.producto_id,
    cantidad: item.cantidad,
    precio_unitario: item.precio_unitario,
    descuento: item.descuento,
    especificaciones: item.especificaciones
  }
  editingItemIndex.value = index
}

const eliminarItem = (index) => {
  form.value.detalles.splice(index, 1)
}

const limpiarItemForm = () => {
  itemForm.value = {
    producto_id: null,
    cantidad: 1,
    precio_unitario: 0,
    descuento: 0,
    especificaciones: ''
  }
  editingItemIndex.value = -1
}

const guardarCotizacion = async () => {
  if (!form.value.proveedor_id) {
    toast.add({ severity: 'warn', summary: 'Atención', detail: 'Seleccione un proveedor', life: 3000 })
    return
  }
  if (form.value.detalles.length === 0) {
    toast.add({ severity: 'warn', summary: 'Atención', detail: 'Agregue al menos un producto', life: 3000 })
    return
  }

  submitting.value = true
  try {
    const payload = {
      ...form.value,
      fecha_solicitud: form.value.fecha_solicitud.toISOString().split('T')[0],
      fecha_vencimiento: form.value.fecha_vencimiento?.toISOString().split('T')[0] || null,
      detalles: form.value.detalles.map(d => ({
        id: d.id,
        producto_id: d.producto_id,
        cantidad: d.cantidad,
        precio_unitario: d.precio_unitario,
        descuento: d.descuento,
        especificaciones: d.especificaciones
      }))
    }

    if (isEditing.value) {
      await api.put(`/cotizaciones/${form.value.id}`, payload)
      toast.add({ severity: 'success', summary: 'Éxito', detail: 'Cotización actualizada', life: 3000 })
    } else {
      await api.post('/cotizaciones', payload)
      toast.add({ severity: 'success', summary: 'Éxito', detail: 'Cotización creada', life: 3000 })
    }

    dialogForm.value = false
    cargarCotizaciones()
    cargarEstadisticas()
  } catch (error) {
    const msg = error.response?.data?.message || 'Error al guardar'
    toast.add({ severity: 'error', summary: 'Error', detail: msg, life: 3000 })
  } finally {
    submitting.value = false
  }
}

const eliminarCotizacion = async (cotizacion) => {
  if (!confirm('¿Eliminar esta cotización?')) return

  try {
    await api.delete(`/cotizaciones/${cotizacion.id}`)
    toast.add({ severity: 'success', summary: 'Éxito', detail: 'Cotización eliminada', life: 3000 })
    cargarCotizaciones()
    cargarEstadisticas()
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Error al eliminar', life: 3000 })
  }
}

// Acciones de flujo
const enviarCotizacion = async (cotizacion) => {
  if (!confirm('¿Enviar esta cotización al proveedor?')) return

  try {
    await api.post(`/cotizaciones/${cotizacion.id}/enviar`)
    toast.add({ severity: 'success', summary: 'Éxito', detail: 'Cotización enviada', life: 3000 })
    cargarCotizaciones()
    cargarEstadisticas()
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Error al enviar', life: 3000 })
  }
}

const abrirRespuesta = async (cotizacion) => {
  try {
    const response = await api.get(`/cotizaciones/${cotizacion.id}`)
    selectedCotizacion.value = response.data.data
    respuestaForm.value = {
      detalles: selectedCotizacion.value.detalles.map(d => ({
        id: d.id,
        producto: d.producto,
        cantidad: parseFloat(d.cantidad),
        precio_unitario: parseFloat(d.precio_unitario)
      })),
      condiciones_pago: selectedCotizacion.value.condiciones_pago || '',
      tiempo_entrega_dias: selectedCotizacion.value.tiempo_entrega_dias
    }
    dialogRespuesta.value = true
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Error al cargar cotización', life: 3000 })
  }
}

const registrarRespuesta = async () => {
  submitting.value = true
  try {
    await api.post(`/cotizaciones/${selectedCotizacion.value.id}/registrar-respuesta`, {
      detalles: respuestaForm.value.detalles.map(d => ({
        id: d.id,
        precio_unitario: d.precio_unitario
      })),
      condiciones_pago: respuestaForm.value.condiciones_pago,
      tiempo_entrega_dias: respuestaForm.value.tiempo_entrega_dias
    })
    toast.add({ severity: 'success', summary: 'Éxito', detail: 'Respuesta registrada', life: 3000 })
    dialogRespuesta.value = false
    cargarCotizaciones()
    cargarEstadisticas()
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Error al registrar respuesta', life: 3000 })
  } finally {
    submitting.value = false
  }
}

const aprobarCotizacion = async (cotizacion) => {
  if (!confirm('¿Aprobar esta cotización?')) return

  try {
    await api.post(`/cotizaciones/${cotizacion.id}/aprobar`)
    toast.add({ severity: 'success', summary: 'Éxito', detail: 'Cotización aprobada', life: 3000 })
    cargarCotizaciones()
    cargarEstadisticas()
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Error al aprobar', life: 3000 })
  }
}

const rechazarCotizacion = async (cotizacion) => {
  if (!confirm('¿Rechazar esta cotización?')) return

  try {
    await api.post(`/cotizaciones/${cotizacion.id}/rechazar`)
    toast.add({ severity: 'success', summary: 'Éxito', detail: 'Cotización rechazada', life: 3000 })
    cargarCotizaciones()
    cargarEstadisticas()
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Error al rechazar', life: 3000 })
  }
}

// Helpers
const getEstadoSeverity = (estado) => {
  const severities = {
    'BORRADOR': 'secondary',
    'ENVIADA': 'info',
    'RECIBIDA': 'warn',
    'APROBADA': 'success',
    'RECHAZADA': 'danger',
    'VENCIDA': 'danger',
    'ANULADA': 'danger'
  }
  return severities[estado] || 'secondary'
}

const formatCurrency = (value, moneda = 'PEN') => {
  return new Intl.NumberFormat('es-PE', {
    style: 'currency',
    currency: moneda
  }).format(value || 0)
}

const formatDate = (date) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('es-PE')
}

const calcularSubtotalItem = (item) => {
  let sub = item.cantidad * item.precio_unitario
  if (item.descuento > 0) sub -= sub * (item.descuento / 100)
  return sub
}

onMounted(() => {
  cargarCotizaciones()
  cargarEstadisticas()
  cargarCatalogos()
})
</script>

<template>
  <div class="p-4">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-800">Cotizaciones</h1>
        <p class="text-gray-500">Gestión de solicitudes de cotización a proveedores</p>
      </div>
      <Button label="Nueva Cotización" icon="pi pi-plus" @click="nuevaCotizacion" />
    </div>

    <!-- Estadísticas -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
      <Card class="shadow-sm">
        <template #content>
          <div class="flex items-center">
            <div class="p-3 rounded-full bg-blue-100 mr-4">
              <i class="pi pi-file text-blue-600 text-xl"></i>
            </div>
            <div>
              <p class="text-gray-500 text-sm">Total</p>
              <p class="text-2xl font-bold">{{ estadisticas.total }}</p>
            </div>
          </div>
        </template>
      </Card>

      <Card class="shadow-sm">
        <template #content>
          <div class="flex items-center">
            <div class="p-3 rounded-full bg-yellow-100 mr-4">
              <i class="pi pi-clock text-yellow-600 text-xl"></i>
            </div>
            <div>
              <p class="text-gray-500 text-sm">Pendientes</p>
              <p class="text-2xl font-bold">{{ estadisticas.pendientes }}</p>
            </div>
          </div>
        </template>
      </Card>

      <Card class="shadow-sm">
        <template #content>
          <div class="flex items-center">
            <div class="p-3 rounded-full bg-orange-100 mr-4">
              <i class="pi pi-check-circle text-orange-600 text-xl"></i>
            </div>
            <div>
              <p class="text-gray-500 text-sm">Por Aprobar</p>
              <p class="text-2xl font-bold">{{ estadisticas.por_aprobar }}</p>
            </div>
          </div>
        </template>
      </Card>

      <Card class="shadow-sm">
        <template #content>
          <div class="flex items-center">
            <div class="p-3 rounded-full bg-green-100 mr-4">
              <i class="pi pi-dollar text-green-600 text-xl"></i>
            </div>
            <div>
              <p class="text-gray-500 text-sm">Aprobado (Mes)</p>
              <p class="text-2xl font-bold">{{ formatCurrency(estadisticas.valor_aprobadas_mes) }}</p>
            </div>
          </div>
        </template>
      </Card>
    </div>

    <!-- Filtros -->
    <Card class="mb-4 shadow-sm">
      <template #content>
        <div class="flex flex-wrap gap-4 items-end">
          <div class="flex-1 min-w-[200px]">
            <label class="block text-sm font-medium text-gray-700 mb-1">Buscar</label>
            <InputText v-model="filters.search" placeholder="Número o proveedor..." class="w-full" @keyup.enter="buscar" />
          </div>
          <div class="w-48">
            <label class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
            <Select v-model="filters.estado" :options="estados" optionLabel="label" optionValue="value" placeholder="Todos" class="w-full" />
          </div>
          <div class="w-64">
            <label class="block text-sm font-medium text-gray-700 mb-1">Proveedor</label>
            <Select v-model="filters.proveedor_id" :options="proveedores" optionLabel="razon_social" optionValue="id" placeholder="Todos" class="w-full" showClear filter />
          </div>
          <Button label="Buscar" icon="pi pi-search" @click="buscar" />
          <Button label="Limpiar" icon="pi pi-times" severity="secondary" outlined @click="limpiarFiltros" />
        </div>
      </template>
    </Card>

    <!-- Tabla -->
    <Card class="shadow-sm">
      <template #content>
        <DataTable
          :value="cotizaciones"
          :loading="loading"
          :lazy="true"
          :paginator="true"
          :rows="lazyParams.rows"
          :totalRecords="totalRecords"
          :rowsPerPageOptions="[10, 15, 25, 50]"
          @page="onPage"
          @sort="onSort"
          :sortField="lazyParams.sortField"
          :sortOrder="lazyParams.sortOrder"
          stripedRows
          responsiveLayout="scroll"
        >
          <Column field="numero" header="Número" sortable style="width: 120px"></Column>
          <Column field="proveedor.razon_social" header="Proveedor" sortable></Column>
          <Column field="fecha_solicitud" header="Fecha" sortable style="width: 110px">
            <template #body="{ data }">{{ formatDate(data.fecha_solicitud) }}</template>
          </Column>
          <Column field="detalles_count" header="Items" style="width: 80px" class="text-center">
            <template #body="{ data }">
              <span class="bg-gray-100 px-2 py-1 rounded">{{ data.detalles_count || 0 }}</span>
            </template>
          </Column>
          <Column field="total" header="Total" sortable style="width: 130px" class="text-right">
            <template #body="{ data }">{{ formatCurrency(data.total, data.moneda) }}</template>
          </Column>
          <Column field="estado" header="Estado" style="width: 120px">
            <template #body="{ data }">
              <Tag :value="data.estado" :severity="getEstadoSeverity(data.estado)" />
            </template>
          </Column>
          <Column header="Acciones" style="width: 200px">
            <template #body="{ data }">
              <div class="flex gap-1">
                <Button icon="pi pi-eye" size="small" severity="info" text rounded @click="verDetalle(data)" v-tooltip.top="'Ver'" />

                <Button v-if="data.estado === 'BORRADOR'" icon="pi pi-pencil" size="small" severity="secondary" text rounded @click="editarCotizacion(data)" v-tooltip.top="'Editar'" />

                <Button v-if="data.estado === 'BORRADOR'" icon="pi pi-send" size="small" severity="info" text rounded @click="enviarCotizacion(data)" v-tooltip.top="'Enviar'" />

                <Button v-if="data.estado === 'ENVIADA'" icon="pi pi-inbox" size="small" severity="warn" text rounded @click="abrirRespuesta(data)" v-tooltip.top="'Registrar Respuesta'" />

                <Button v-if="data.estado === 'RECIBIDA'" icon="pi pi-check" size="small" severity="success" text rounded @click="aprobarCotizacion(data)" v-tooltip.top="'Aprobar'" />

                <Button v-if="data.estado === 'RECIBIDA'" icon="pi pi-times" size="small" severity="danger" text rounded @click="rechazarCotizacion(data)" v-tooltip.top="'Rechazar'" />

                <Button v-if="data.estado === 'BORRADOR'" icon="pi pi-trash" size="small" severity="danger" text rounded @click="eliminarCotizacion(data)" v-tooltip.top="'Eliminar'" />
              </div>
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>

    <!-- Dialog Formulario -->
    <Dialog v-model:visible="dialogForm" :header="formTitle" :style="{ width: '900px', maxHeight: '90vh' }" :contentStyle="{ overflow: 'auto', maxHeight: 'calc(90vh - 120px)' }" modal>
      <div class="grid grid-cols-2 gap-4 mb-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Proveedor *</label>
          <Select v-model="form.proveedor_id" :options="proveedores" optionLabel="razon_social" optionValue="id" placeholder="Seleccione" class="w-full" filter showClear />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Fecha Solicitud *</label>
          <DatePicker v-model="form.fecha_solicitud" dateFormat="dd/mm/yy" class="w-full" />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Fecha Vencimiento</label>
          <DatePicker v-model="form.fecha_vencimiento" dateFormat="dd/mm/yy" class="w-full" />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Moneda</label>
          <Select v-model="form.moneda" :options="monedas" optionLabel="label" optionValue="value" class="w-full" />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Condiciones de Pago</label>
          <InputText v-model="form.condiciones_pago" class="w-full" />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Tiempo Entrega (días)</label>
          <InputNumber v-model="form.tiempo_entrega_dias" :min="1" class="w-full" />
        </div>
        <div class="col-span-2">
          <label class="block text-sm font-medium text-gray-700 mb-1">Observaciones</label>
          <Textarea v-model="form.observaciones" rows="2" class="w-full" />
        </div>
      </div>

      <Divider />

      <!-- Agregar Ítems -->
      <h4 class="font-semibold mb-3">Productos</h4>
      <div class="bg-gray-50 p-4 rounded-lg mb-4">
        <div class="grid grid-cols-6 gap-3 items-end">
          <div class="col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">Producto</label>
            <Select v-model="itemForm.producto_id" :options="productos" optionLabel="nombre" optionValue="id" placeholder="Seleccione" class="w-full" filter showClear />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Cantidad</label>
            <InputNumber v-model="itemForm.cantidad" :min="0.01" :minFractionDigits="2" class="w-full" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Precio Unit.</label>
            <InputNumber v-model="itemForm.precio_unitario" :min="0" :minFractionDigits="2" mode="currency" currency="PEN" class="w-full" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Desc. %</label>
            <InputNumber v-model="itemForm.descuento" :min="0" :max="100" suffix="%" class="w-full" />
          </div>
          <div>
            <Button :label="editingItemIndex >= 0 ? 'Actualizar' : 'Agregar'" :icon="editingItemIndex >= 0 ? 'pi pi-check' : 'pi pi-plus'" @click="agregarItem" class="w-full" />
          </div>
        </div>
      </div>

      <!-- Lista de ítems -->
      <DataTable :value="form.detalles" responsiveLayout="scroll" class="mb-4">
        <Column header="Producto">
          <template #body="{ data }">
            <span class="font-medium">{{ data.producto?.nombre || 'N/A' }}</span>
            <br><span class="text-xs text-gray-500">{{ data.producto?.codigo }}</span>
          </template>
        </Column>
        <Column field="cantidad" header="Cantidad" style="width: 100px" class="text-right"></Column>
        <Column field="precio_unitario" header="Precio Unit." style="width: 120px" class="text-right">
          <template #body="{ data }">{{ formatCurrency(data.precio_unitario) }}</template>
        </Column>
        <Column field="descuento" header="Desc.%" style="width: 80px" class="text-right">
          <template #body="{ data }">{{ data.descuento }}%</template>
        </Column>
        <Column header="Subtotal" style="width: 120px" class="text-right">
          <template #body="{ data }">{{ formatCurrency(calcularSubtotalItem(data)) }}</template>
        </Column>
        <Column style="width: 80px">
          <template #body="{ data, index }">
            <Button icon="pi pi-pencil" size="small" text severity="secondary" @click="editarItem(index)" />
            <Button icon="pi pi-trash" size="small" text severity="danger" @click="eliminarItem(index)" />
          </template>
        </Column>
      </DataTable>

      <!-- Totales -->
      <div class="flex justify-end">
        <div class="w-64 space-y-2">
          <div class="flex justify-between">
            <span class="text-gray-600">Subtotal:</span>
            <span class="font-medium">{{ formatCurrency(subtotalForm) }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-gray-600">IGV (18%):</span>
            <span class="font-medium">{{ formatCurrency(igvForm) }}</span>
          </div>
          <div class="flex justify-between text-lg font-bold border-t pt-2">
            <span>Total:</span>
            <span class="text-green-600">{{ formatCurrency(totalForm) }}</span>
          </div>
        </div>
      </div>

      <template #footer>
        <Button label="Cancelar" severity="secondary" @click="dialogForm = false" />
        <Button label="Guardar" icon="pi pi-save" @click="guardarCotizacion" :loading="submitting" />
      </template>
    </Dialog>

    <!-- Dialog Detalle -->
    <Dialog v-model:visible="dialogDetalle" header="Detalle de Cotización" :style="{ width: '800px', maxHeight: '90vh' }" :contentStyle="{ overflow: 'auto', maxHeight: 'calc(90vh - 120px)' }" modal>
      <template v-if="selectedCotizacion">
        <div class="grid grid-cols-2 gap-4 mb-4">
          <div>
            <p class="text-sm text-gray-500">Número</p>
            <p class="font-semibold">{{ selectedCotizacion.numero }}</p>
          </div>
          <div>
            <p class="text-sm text-gray-500">Estado</p>
            <Tag :value="selectedCotizacion.estado" :severity="getEstadoSeverity(selectedCotizacion.estado)" />
          </div>
          <div>
            <p class="text-sm text-gray-500">Proveedor</p>
            <p class="font-semibold">{{ selectedCotizacion.proveedor?.razon_social }}</p>
          </div>
          <div>
            <p class="text-sm text-gray-500">Fecha Solicitud</p>
            <p class="font-semibold">{{ formatDate(selectedCotizacion.fecha_solicitud) }}</p>
          </div>
          <div>
            <p class="text-sm text-gray-500">Solicitado por</p>
            <p class="font-semibold">{{ selectedCotizacion.solicitante?.nombre || '-' }}</p>
          </div>
          <div>
            <p class="text-sm text-gray-500">Condiciones de Pago</p>
            <p class="font-semibold">{{ selectedCotizacion.condiciones_pago || '-' }}</p>
          </div>
        </div>

        <Divider />

        <h4 class="font-semibold mb-3">Productos</h4>
        <DataTable :value="selectedCotizacion.detalles" responsiveLayout="scroll" class="mb-4">
          <Column header="Producto">
            <template #body="{ data }">
              <span class="font-medium">{{ data.producto?.nombre }}</span>
              <br><span class="text-xs text-gray-500">{{ data.producto?.codigo }}</span>
            </template>
          </Column>
          <Column field="cantidad" header="Cantidad" style="width: 100px" class="text-right"></Column>
          <Column header="Precio Unit." style="width: 120px" class="text-right">
            <template #body="{ data }">{{ formatCurrency(data.precio_unitario) }}</template>
          </Column>
          <Column header="Subtotal" style="width: 120px" class="text-right">
            <template #body="{ data }">{{ formatCurrency(data.subtotal) }}</template>
          </Column>
        </DataTable>

        <div class="flex justify-end">
          <div class="w-64 space-y-2">
            <div class="flex justify-between">
              <span class="text-gray-600">Subtotal:</span>
              <span class="font-medium">{{ formatCurrency(selectedCotizacion.subtotal) }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-600">IGV (18%):</span>
              <span class="font-medium">{{ formatCurrency(selectedCotizacion.igv) }}</span>
            </div>
            <div class="flex justify-between text-lg font-bold border-t pt-2">
              <span>Total:</span>
              <span class="text-green-600">{{ formatCurrency(selectedCotizacion.total) }}</span>
            </div>
          </div>
        </div>
      </template>
    </Dialog>

    <!-- Dialog Registrar Respuesta -->
    <Dialog v-model:visible="dialogRespuesta" header="Registrar Respuesta del Proveedor" :style="{ width: '800px' }" modal>
      <template v-if="selectedCotizacion">
        <p class="mb-4 text-gray-600">
          Cotización: <strong>{{ selectedCotizacion.numero }}</strong> -
          Proveedor: <strong>{{ selectedCotizacion.proveedor?.razon_social }}</strong>
        </p>

        <div class="grid grid-cols-2 gap-4 mb-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Condiciones de Pago</label>
            <InputText v-model="respuestaForm.condiciones_pago" class="w-full" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Tiempo de Entrega (días)</label>
            <InputNumber v-model="respuestaForm.tiempo_entrega_dias" :min="1" class="w-full" />
          </div>
        </div>

        <Divider />

        <h4 class="font-semibold mb-3">Actualizar Precios</h4>
        <DataTable :value="respuestaForm.detalles" responsiveLayout="scroll">
          <Column header="Producto">
            <template #body="{ data }">
              <span class="font-medium">{{ data.producto?.nombre }}</span>
            </template>
          </Column>
          <Column field="cantidad" header="Cantidad" style="width: 100px" class="text-right"></Column>
          <Column header="Precio Unitario" style="width: 180px">
            <template #body="{ data }">
              <InputNumber v-model="data.precio_unitario" :min="0" :minFractionDigits="2" mode="currency" currency="PEN" class="w-full" />
            </template>
          </Column>
          <Column header="Subtotal" style="width: 120px" class="text-right">
            <template #body="{ data }">{{ formatCurrency(data.cantidad * data.precio_unitario) }}</template>
          </Column>
        </DataTable>
      </template>

      <template #footer>
        <Button label="Cancelar" severity="secondary" @click="dialogRespuesta = false" />
        <Button label="Registrar Respuesta" icon="pi pi-check" @click="registrarRespuesta" :loading="submitting" />
      </template>
    </Dialog>
  </div>
</template>
