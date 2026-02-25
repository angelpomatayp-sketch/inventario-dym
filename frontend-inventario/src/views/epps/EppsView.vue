<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useAuthStore } from '@/stores/auth'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Select from 'primevue/select'
import DatePicker from 'primevue/datepicker'
import Dialog from 'primevue/dialog'
import Tag from 'primevue/tag'
import Textarea from 'primevue/textarea'
import Card from 'primevue/card'
import api from '@/services/api'

const toast = useToast()
const authStore = useAuthStore()
const almacenAsignado = computed(() => authStore.user?.almacen_id || null)

// Estado general
const loading = ref(false)
const estadisticas = ref({
  total_asignaciones: 0,
  vigentes: 0,
  por_vencer: 0,
  vencidos: 0,
  proximos_vencer: 0
})

// ==================== ASIGNACIONES ====================
const asignaciones = ref([])
const totalAsignaciones = ref(0)
const asignacionFilters = ref({
  search: '',
  estado: null,
  categoria: null,
  producto_id: null
})
const asignacionLazyParams = ref({
  first: 0,
  rows: 15,
  sortField: 'fecha_entrega',
  sortOrder: -1
})


// ==================== CATÁLOGOS ====================
const trabajadores = ref([])
const categorias = ref([])
const almacenes = ref([])
const productosEpp = ref([])  // Productos de familias marcadas como EPP

const estados = [
  { label: 'Todos', value: null },
  { label: 'Vigente', value: 'VIGENTE' },
  { label: 'Por Vencer', value: 'POR_VENCER' },
  { label: 'Vencido', value: 'VENCIDO' },
  { label: 'Devuelto', value: 'DEVUELTO' },
  { label: 'Extraviado', value: 'EXTRAVIADO' },
  { label: 'Dañado', value: 'DAÑADO' }
]

// ==================== DIÁLOGOS ====================
const dialogAsignacion = ref(false)
const dialogConfirmar = ref(false)
const dialogRenovar = ref(false)
const dialogHistorial = ref(false)
const submitting = ref(false)

// Formulario asignación (usando objetos completos para Select)
const asignacionForm = ref({
  producto: null,       // Producto EPP del inventario (obligatorio)
  trabajador: null,     // Objeto completo del trabajador
  almacen: null,        // Objeto completo del almacén (para descuento de stock)
  fecha_entrega: new Date(),
  cantidad: 1,
  talla: null,          // Objeto para talla
  numero_serie: '',
  observaciones: ''
})

// Confirmación recepción
const selectedAsignacion = ref(null)

// Renovación
const renovarForm = ref({
  motivo: null,
  almacen: null,
  nueva_talla: '',
  observaciones: ''
})

const motivosRenovacion = [
  { label: 'Vencimiento', value: 'VENCIMIENTO' },
  { label: 'Deterioro', value: 'DETERIORO' },
  { label: 'Extravío', value: 'EXTRAVIO' },
  { label: 'Cambio de Talla', value: 'CAMBIO_TALLA' },
  { label: 'Otro', value: 'OTRO' }
]

// Historial
const historialData = ref([])
const selectedTrabajador = ref(null)

// ==================== MÉTODOS - CARGA ====================
const cargarEstadisticas = async () => {
  try {
    const response = await api.get('/epps/estadisticas')
    estadisticas.value = response.data.data
  } catch (error) {
    console.error('Error cargando estadísticas:', error)
  }
}

const cargarAsignaciones = async () => {
  loading.value = true
  try {
    const params = {
      page: (asignacionLazyParams.value.first / asignacionLazyParams.value.rows) + 1,
      per_page: asignacionLazyParams.value.rows,
      sort_field: asignacionLazyParams.value.sortField,
      sort_order: asignacionLazyParams.value.sortOrder === 1 ? 'asc' : 'desc',
      ...asignacionFilters.value
    }
    const response = await api.get('/epps/asignaciones', { params })
    asignaciones.value = response.data.data
    totalAsignaciones.value = response.data.meta?.total || 0
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Error al cargar asignaciones', life: 3000 })
  } finally {
    loading.value = false
  }
}

const cargarCatalogos = async () => {
  try {
    const [personalRes, catRes, almRes] = await Promise.all([
      api.get('/epps/personal'),  // Trabajadores + usuarios para asignar EPPs
      api.get('/epps/categorias'),
      api.get('/administracion/almacenes', { params: { all: true } })
    ])
    trabajadores.value = personalRes.data.data

    // Transformar categorías a formato para dropdown
    const cats = catRes.data.data
    categorias.value = Object.entries(cats).map(([value, label]) => ({ label, value }))

    // Cargar almacenes para selección
    almacenes.value = almRes.data.data || []

    if (almacenAsignado.value && !asignacionForm.value.almacen) {
      asignacionForm.value.almacen = almacenes.value.find(a => a.id === almacenAsignado.value) || null
    }

    await cargarProductosEpp()
  } catch (error) {
    console.error('Error cargando catálogos:', error)
  }
}

const cargarProductosEpp = async () => {
  try {
    const almacenId = almacenAsignado.value || asignacionForm.value.almacen?.id || null
    const params = { solo_con_stock: true }
    if (almacenId) {
      params.almacen_id = almacenId
    }

    const prodRes = await api.get('/epps/productos-epp', { params })
    productosEpp.value = prodRes.data.data || []
  } catch (error) {
    productosEpp.value = []
    console.error('Error cargando productos EPP:', error)
  }
}

// ==================== MÉTODOS - ASIGNACIONES ====================
const onPageAsignacion = (event) => {
  asignacionLazyParams.value = { ...asignacionLazyParams.value, ...event }
  cargarAsignaciones()
}

const onSortAsignacion = (event) => {
  asignacionLazyParams.value = { ...asignacionLazyParams.value, ...event }
  cargarAsignaciones()
}

const buscarAsignaciones = () => {
  asignacionLazyParams.value.first = 0
  cargarAsignaciones()
}

const nuevaAsignacion = () => {
  asignacionForm.value = {
    producto: null,
    trabajador: null,
    almacen: null,
    fecha_entrega: new Date(),
    cantidad: 1,
    talla: null,
    numero_serie: '',
    observaciones: ''
  }
  dialogAsignacion.value = true
}

const guardarAsignacion = async () => {
  if (!asignacionForm.value.producto || !asignacionForm.value.trabajador) {
    toast.add({ severity: 'warn', summary: 'Atención', detail: 'Complete los campos requeridos', life: 3000 })
    return
  }

  // Almacén siempre requerido para descuento de stock
  if (!asignacionForm.value.almacen) {
    toast.add({ severity: 'warn', summary: 'Atención', detail: 'Seleccione el almacén de donde se descontará el stock', life: 3000 })
    return
  }

  submitting.value = true
  try {
    const payload = {
      producto_id: asignacionForm.value.producto.id,
      trabajador_id: asignacionForm.value.trabajador.id,
      tipo_receptor: asignacionForm.value.trabajador.tipo, // 'trabajador' o 'usuario'
      almacen_id: asignacionForm.value.almacen.id,
      fecha_entrega: asignacionForm.value.fecha_entrega.toISOString().split('T')[0],
      cantidad: asignacionForm.value.cantidad,
      talla: asignacionForm.value.talla?.value || asignacionForm.value.talla || '',
      numero_serie: asignacionForm.value.numero_serie,
      observaciones: asignacionForm.value.observaciones
    }

    await api.post('/epps/asignaciones', payload)
    toast.add({ severity: 'success', summary: 'Éxito', detail: 'EPP asignado correctamente y stock descontado', life: 3000 })
    dialogAsignacion.value = false
    cargarAsignaciones()
    cargarEstadisticas()
    cargarCatalogos() // Refrescar productos para actualizar stock
  } catch (error) {
    const msg = error.response?.data?.message || 'Error al asignar EPP'
    toast.add({ severity: 'error', summary: 'Error', detail: msg, life: 3000 })
  } finally {
    submitting.value = false
  }
}

const abrirConfirmar = (asignacion) => {
  selectedAsignacion.value = asignacion
  dialogConfirmar.value = true
}

const confirmarRecepcion = async () => {
  submitting.value = true
  try {
    await api.post(`/epps/asignaciones/${selectedAsignacion.value.id}/confirmar`)
    toast.add({ severity: 'success', summary: 'Éxito', detail: 'Recepción confirmada', life: 3000 })
    dialogConfirmar.value = false
    cargarAsignaciones()
  } catch (error) {
    const msg = error.response?.data?.message || 'Error al confirmar recepción'
    toast.add({ severity: 'error', summary: 'Error', detail: msg, life: 3000 })
  } finally {
    submitting.value = false
  }
}

const registrarDevolucion = async (asignacion) => {
  if (!confirm('¿Registrar devolución de este EPP?')) return

  try {
    await api.post(`/epps/asignaciones/${asignacion.id}/devolver`)
    toast.add({ severity: 'success', summary: 'Éxito', detail: 'Devolución registrada', life: 3000 })
    cargarAsignaciones()
    cargarEstadisticas()
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Error al registrar', life: 3000 })
  }
}

const abrirRenovar = (asignacion) => {
  selectedAsignacion.value = asignacion
  renovarForm.value = {
    motivo: null,
    almacen: null,
    nueva_talla: asignacion.talla || '',
    observaciones: ''
  }
  dialogRenovar.value = true
}

const renovarEpp = async () => {
  if (!renovarForm.value.motivo) {
    toast.add({ severity: 'warn', summary: 'Atención', detail: 'Seleccione un motivo', life: 3000 })
    return
  }

  // Almacén siempre requerido para descuento de stock
  if (!renovarForm.value.almacen) {
    toast.add({ severity: 'warn', summary: 'Atención', detail: 'Seleccione el almacén de donde se descontará el stock', life: 3000 })
    return
  }

  submitting.value = true
  try {
    const payload = {
      motivo: renovarForm.value.motivo?.value || renovarForm.value.motivo,
      almacen_id: renovarForm.value.almacen.id,
      nueva_talla: renovarForm.value.nueva_talla,
      observaciones: renovarForm.value.observaciones
    }
    await api.post(`/epps/asignaciones/${selectedAsignacion.value.id}/renovar`, payload)
    toast.add({ severity: 'success', summary: 'Éxito', detail: 'EPP renovado correctamente y stock descontado', life: 3000 })
    dialogRenovar.value = false
    cargarAsignaciones()
    cargarEstadisticas()
    cargarCatalogos() // Refrescar stock
  } catch (error) {
    const msg = error.response?.data?.message || 'Error al renovar'
    toast.add({ severity: 'error', summary: 'Error', detail: msg, life: 3000 })
  } finally {
    submitting.value = false
  }
}

const verHistorial = async (asignacion) => {
  // Obtener el receptor correcto según tipo_receptor
  const receptor = asignacion.trabajador_persona || asignacion.trabajador
  const tipoReceptor = asignacion.tipo_receptor || 'usuario'

  selectedTrabajador.value = receptor
  try {
    const response = await api.get(`/epps/trabajador/${receptor.id}/historial`, {
      params: { tipo_receptor: tipoReceptor }
    })
    historialData.value = response.data.data
    dialogHistorial.value = true
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Error al cargar historial', life: 3000 })
  }
}

// ==================== HELPERS ====================
const getEstadoSeverity = (estado) => {
  const severities = {
    'VIGENTE': 'success',
    'POR_VENCER': 'warn',
    'VENCIDO': 'danger',
    'DEVUELTO': 'secondary',
    'EXTRAVIADO': 'danger',
    'DAÑADO': 'danger'
  }
  return severities[estado] || 'secondary'
}

const getCategoriaSeverity = (categoria) => {
  const severities = {
    'CABEZA': 'info',
    'OJOS': 'info',
    'OIDOS': 'warn',
    'RESPIRATORIO': 'danger',
    'MANOS': 'success',
    'PIES': 'success',
    'CUERPO': 'secondary',
    'ALTURA': 'danger'
  }
  return severities[categoria] || 'secondary'
}

const formatDate = (date) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('es-PE')
}

const getTallasArray = computed(() => {
  const producto = asignacionForm.value.producto
  if (!producto?.tallas_disponibles) return []
  return producto.tallas_disponibles.split(',').map(t => ({ label: t.trim(), value: t.trim() }))
})

const productoRequiereTalla = computed(() => {
  return asignacionForm.value.producto?.requiere_talla || false
})

onMounted(() => {
  cargarEstadisticas()
  cargarAsignaciones()
  cargarCatalogos()
})

watch(
  () => asignacionForm.value.almacen?.id,
  async (nuevoAlmacenId, anteriorAlmacenId) => {
    if (almacenAsignado.value) return
    if (nuevoAlmacenId === anteriorAlmacenId) return
    await cargarProductosEpp()
  }
)
</script>

<template>
  <div class="p-4">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-800">Equipos de Protección Personal</h1>
        <p class="text-gray-500">Gestión y control de EPPs asignados a trabajadores</p>
      </div>
    </div>

    <!-- Estadísticas -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
      <Card class="shadow-sm">
        <template #content>
          <div class="text-center">
            <i class="pi pi-users text-3xl text-green-500 mb-2"></i>
            <p class="text-gray-500 text-sm">Vigentes</p>
            <p class="text-2xl font-bold text-green-600">{{ estadisticas.vigentes }}</p>
          </div>
        </template>
      </Card>

      <Card class="shadow-sm">
        <template #content>
          <div class="text-center">
            <i class="pi pi-clock text-3xl text-yellow-500 mb-2"></i>
            <p class="text-gray-500 text-sm">Por Vencer</p>
            <p class="text-2xl font-bold text-yellow-600">{{ estadisticas.por_vencer }}</p>
          </div>
        </template>
      </Card>

      <Card class="shadow-sm">
        <template #content>
          <div class="text-center">
            <i class="pi pi-exclamation-triangle text-3xl text-red-500 mb-2"></i>
            <p class="text-gray-500 text-sm">Vencidos</p>
            <p class="text-2xl font-bold text-red-600">{{ estadisticas.vencidos }}</p>
          </div>
        </template>
      </Card>

      <Card class="shadow-sm">
        <template #content>
          <div class="text-center">
            <i class="pi pi-calendar text-3xl text-orange-500 mb-2"></i>
            <p class="text-gray-500 text-sm">Próx. 30 días</p>
            <p class="text-2xl font-bold text-orange-600">{{ estadisticas.proximos_vencer }}</p>
          </div>
        </template>
      </Card>

      <Card class="shadow-sm">
        <template #content>
          <div class="text-center">
            <i class="pi pi-chart-bar text-3xl text-purple-500 mb-2"></i>
            <p class="text-gray-500 text-sm">Total Asig.</p>
            <p class="text-2xl font-bold">{{ estadisticas.total_asignaciones }}</p>
          </div>
        </template>
      </Card>
    </div>

    <!-- Contenido Principal -->
        <div class="mb-4 flex flex-wrap gap-4 items-end">
          <div class="flex-1 min-w-[200px]">
            <label class="block text-sm font-medium text-gray-700 mb-1">Buscar</label>
            <InputText v-model="asignacionFilters.search" placeholder="Trabajador o producto..." class="w-full" @keyup.enter="buscarAsignaciones" />
          </div>
          <div class="w-40">
            <label class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
            <Select v-model="asignacionFilters.estado" :options="estados" optionLabel="label" optionValue="value" placeholder="Todos" class="w-full" />
          </div>
          <div class="w-48">
            <label class="block text-sm font-medium text-gray-700 mb-1">Categoría</label>
            <Select v-model="asignacionFilters.categoria" :options="categorias" optionLabel="label" optionValue="value" placeholder="Todas" class="w-full" showClear />
          </div>
          <Button label="Buscar" icon="pi pi-search" @click="buscarAsignaciones" />
          <Button label="Nueva Asignación" icon="pi pi-plus" severity="success" @click="nuevaAsignacion" />
        </div>

        <DataTable
          :value="asignaciones"
          :loading="loading"
          :lazy="true"
          :paginator="true"
          :rows="asignacionLazyParams.rows"
          :totalRecords="totalAsignaciones"
          :rowsPerPageOptions="[10, 15, 25, 50]"
          @page="onPageAsignacion"
          @sort="onSortAsignacion"
          :sortField="asignacionLazyParams.sortField"
          :sortOrder="asignacionLazyParams.sortOrder"
          stripedRows
          responsiveLayout="scroll"
        >
          <Column field="trabajador.nombre" header="Trabajador" sortable>
            <template #body="{ data }">
              <div>
                <span class="font-medium">{{ data.trabajador_persona?.nombre || data.trabajador?.nombre }}</span>
                <span v-if="data.tipo_receptor === 'usuario'" class="ml-1 text-xs bg-blue-100 text-blue-700 px-1 rounded">Usuario</span>
                <span v-if="data.trabajador_persona?.cargo" class="text-xs text-gray-500 ml-1">({{ data.trabajador_persona.cargo }})</span>
                <br><span class="text-xs text-gray-500">DNI: {{ data.trabajador_persona?.dni || data.trabajador?.dni }}</span>
              </div>
            </template>
          </Column>
          <Column field="producto.nombre" header="EPP / Producto" sortable>
            <template #body="{ data }">
              <div>
                <span class="font-medium">{{ data.producto?.nombre || data.tipo_epp?.nombre }}</span>
                <br>
                <Tag
                  :value="data.producto?.familia?.categoria_epp || data.tipo_epp?.categoria"
                  :severity="getCategoriaSeverity(data.producto?.familia?.categoria_epp || data.tipo_epp?.categoria)"
                  class="text-xs"
                />
              </div>
            </template>
          </Column>
          <Column field="talla" header="Talla" style="width: 80px"></Column>
          <Column field="cantidad" header="Cant." style="width: 70px" class="text-center"></Column>
          <Column field="fecha_entrega" header="Entrega" sortable style="width: 100px">
            <template #body="{ data }">{{ formatDate(data.fecha_entrega) }}</template>
          </Column>
          <Column field="fecha_vencimiento" header="Vencimiento" sortable style="width: 100px">
            <template #body="{ data }">
              <span :class="{ 'text-red-600 font-semibold': new Date(data.fecha_vencimiento) < new Date() }">
                {{ formatDate(data.fecha_vencimiento) }}
              </span>
            </template>
          </Column>
          <Column field="estado" header="Estado" style="width: 110px">
            <template #body="{ data }">
              <Tag :value="data.estado" :severity="getEstadoSeverity(data.estado)" />
            </template>
          </Column>
          <Column field="confirmado_trabajador" header="Confirm." style="width: 80px" class="text-center">
            <template #body="{ data }">
              <i :class="data.confirmado_trabajador ? 'pi pi-check-circle text-green-500' : 'pi pi-times-circle text-gray-400'" class="text-lg"></i>
            </template>
          </Column>
          <Column header="Acciones" style="width: 180px">
            <template #body="{ data }">
              <div class="flex gap-1">
                <Button v-if="!data.confirmado_trabajador && data.estado === 'VIGENTE'" icon="pi pi-check" size="small" severity="success" text rounded @click="abrirConfirmar(data)" v-tooltip.top="'Confirmar Recepción'" />

                <Button v-if="['VIGENTE', 'POR_VENCER', 'VENCIDO'].includes(data.estado)" icon="pi pi-refresh" size="small" severity="warn" text rounded @click="abrirRenovar(data)" v-tooltip.top="'Renovar'" />

                <Button v-if="['VIGENTE', 'POR_VENCER'].includes(data.estado)" icon="pi pi-undo" size="small" severity="secondary" text rounded @click="registrarDevolucion(data)" v-tooltip.top="'Devolver'" />

                <Button icon="pi pi-history" size="small" severity="info" text rounded @click="verHistorial(data)" v-tooltip.top="'Historial'" />
              </div>
            </template>
          </Column>
        </DataTable>

    <!-- Dialog Nueva Asignación -->
    <Dialog v-model:visible="dialogAsignacion" header="Asignar EPP" :style="{ width: '550px', maxHeight: '90vh' }" :contentStyle="{ overflow: 'auto', maxHeight: 'calc(90vh - 120px)' }" :modal="true" :closable="true">
      <div class="space-y-4">
        <!-- Selección de Producto EPP -->
        <div class="border rounded-lg p-4 bg-amber-50">
          <label class="block text-sm font-medium text-gray-700 mb-1">
            <i class="pi pi-shield mr-1"></i>
            Producto EPP *
          </label>
          <Select
            v-model="asignacionForm.producto"
            :options="productosEpp"
            optionLabel="nombre"
            placeholder="Seleccione el EPP a entregar"
            class="w-full"
            filter
            appendTo="body"
            :disabled="productosEpp.length === 0"
          >
            <template #option="{ option }">
              <div class="flex justify-between items-center w-full">
                <div>
                  <span class="font-medium">{{ option.nombre }}</span>
                  <span class="text-xs text-gray-500 ml-2">({{ option.codigo }})</span>
                  <br>
                  <Tag :value="option.categoria_epp" :severity="getCategoriaSeverity(option.categoria_epp)" class="text-xs mt-1" />
                </div>
                <span class="text-xs bg-gray-100 px-2 py-1 rounded">Stock: {{ option.stock_total }}</span>
              </div>
            </template>
          </Select>
          <p v-if="productosEpp.length === 0" class="text-xs text-red-500 mt-1">
            <i class="pi pi-exclamation-triangle mr-1"></i>
            No hay productos EPP disponibles. Primero registre productos en familias EPP.
          </p>
          <p v-else-if="asignacionForm.producto" class="text-xs text-gray-500 mt-1">
            <i class="pi pi-info-circle mr-1"></i>
            Categoría: {{ asignacionForm.producto.familia?.nombre || asignacionForm.producto.categoria_epp }}
            <span v-if="asignacionForm.producto.vida_util_dias"> | Vida útil: {{ asignacionForm.producto.vida_util_dias }} días</span>
          </p>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Trabajador / Usuario *</label>
          <Select
            v-model="asignacionForm.trabajador"
            :options="trabajadores"
            optionLabel="display_name"
            placeholder="Seleccione trabajador o usuario"
            class="w-full"
            filter
            appendTo="body"
          >
            <template #option="{ option }">
              <div class="flex justify-between items-center w-full">
                <div>
                  <span class="font-medium">{{ option.nombre }}</span>
                  <span v-if="option.tipo === 'usuario'" class="ml-2 text-xs bg-blue-100 text-blue-700 px-1 rounded">Usuario</span>
                  <span v-else-if="option.cargo" class="text-xs text-gray-500 ml-2">({{ option.cargo }})</span>
                  <br v-if="option.dni || option.centro_costo">
                  <span v-if="option.dni" class="text-xs text-gray-500">DNI: {{ option.dni }}</span>
                  <span v-if="option.centro_costo" class="text-xs text-gray-400 ml-2">{{ option.centro_costo.nombre }}</span>
                </div>
              </div>
            </template>
          </Select>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Almacén (para descuento de stock) *</label>
          <Select v-model="asignacionForm.almacen" :options="almacenes" optionLabel="nombre" placeholder="Seleccione almacén" class="w-full" filter appendTo="body" />
          <p class="text-xs text-gray-500 mt-1">
            <i class="pi pi-info-circle mr-1"></i>
            Se descontará {{ asignacionForm.cantidad || 1 }} unidad(es) del stock de este almacén
          </p>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Fecha Entrega *</label>
            <DatePicker v-model="asignacionForm.fecha_entrega" dateFormat="dd/mm/yy" class="w-full" appendTo="body" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Cantidad</label>
            <InputNumber v-model="asignacionForm.cantidad" :min="1" class="w-full" />
          </div>
        </div>

        <div class="grid grid-cols-2 gap-4" v-if="productoRequiereTalla">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Talla</label>
            <Select v-model="asignacionForm.talla" :options="getTallasArray" optionLabel="label" placeholder="Seleccione" class="w-full" appendTo="body" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">N° Serie</label>
            <InputText v-model="asignacionForm.numero_serie" class="w-full" />
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Observaciones</label>
          <Textarea v-model="asignacionForm.observaciones" rows="2" class="w-full" />
        </div>
      </div>

      <template #footer>
        <Button label="Cancelar" severity="secondary" @click="dialogAsignacion = false" />
        <Button label="Asignar EPP" icon="pi pi-check" @click="guardarAsignacion" :loading="submitting" />
      </template>
    </Dialog>

    <!-- Dialog Confirmar Recepción -->
    <Dialog v-model:visible="dialogConfirmar" header="Confirmar Recepción de EPP" :style="{ width: '400px' }" modal>
      <template v-if="selectedAsignacion">
        <div class="text-center mb-4">
          <i class="pi pi-shield text-5xl text-amber-500 mb-3"></i>
          <p class="text-lg font-semibold">{{ selectedAsignacion.producto?.nombre || selectedAsignacion.tipo_epp?.nombre }}</p>
          <p class="text-gray-500">Para: {{ selectedAsignacion.trabajador_persona?.nombre || selectedAsignacion.trabajador?.nombre }}</p>
        </div>

        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 mb-4">
          <p class="text-sm text-yellow-800">
            <i class="pi pi-info-circle mr-2"></i>
            El almacenero confirma la recepción/entrega del EPP para este registro.
          </p>
        </div>

        <div class="text-center text-gray-500">
          <i class="pi pi-pencil text-3xl mb-2"></i>
          <p class="text-sm">La confirmación quedará registrada por el almacenero</p>
        </div>
      </template>

      <template #footer>
        <Button label="Cancelar" severity="secondary" @click="dialogConfirmar = false" />
        <Button label="Confirmar" icon="pi pi-check" @click="confirmarRecepcion" :loading="submitting" />
      </template>
    </Dialog>

    <!-- Dialog Renovar -->
    <Dialog v-model:visible="dialogRenovar" header="Renovar EPP" :style="{ width: '450px' }" modal>
      <template v-if="selectedAsignacion">
        <p class="mb-4 text-gray-600">
          Renovar <strong>{{ selectedAsignacion.producto?.nombre || selectedAsignacion.tipo_epp?.nombre }}</strong> de <strong>{{ selectedAsignacion.trabajador_persona?.nombre || selectedAsignacion.trabajador?.nombre }}</strong>
        </p>

        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Motivo de Renovación *</label>
            <Select v-model="renovarForm.motivo" :options="motivosRenovacion" optionLabel="label" placeholder="Seleccione" class="w-full" appendTo="body" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Almacén (para descuento de stock) *</label>
            <Select v-model="renovarForm.almacen" :options="almacenes" optionLabel="nombre" placeholder="Seleccione almacén" class="w-full" filter appendTo="body" />
            <p class="text-xs text-gray-500 mt-1">
              <i class="pi pi-info-circle mr-1"></i>
              Se descontará {{ selectedAsignacion.cantidad || 1 }} unidad(es) del stock para el nuevo EPP
            </p>
          </div>
          <div v-if="selectedAsignacion.producto?.requiere_talla || selectedAsignacion.tipo_epp?.requiere_talla">
            <label class="block text-sm font-medium text-gray-700 mb-1">Nueva Talla</label>
            <InputText v-model="renovarForm.nueva_talla" class="w-full" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Observaciones</label>
            <Textarea v-model="renovarForm.observaciones" rows="2" class="w-full" />
          </div>
        </div>
      </template>

      <template #footer>
        <Button label="Cancelar" severity="secondary" @click="dialogRenovar = false" />
        <Button label="Renovar" icon="pi pi-refresh" @click="renovarEpp" :loading="submitting" />
      </template>
    </Dialog>

    <!-- Dialog Historial -->
    <Dialog v-model:visible="dialogHistorial" header="Historial de EPPs" :style="{ width: '750px', maxHeight: '90vh' }" :contentStyle="{ overflow: 'auto', maxHeight: 'calc(90vh - 120px)' }" modal>
      <template v-if="selectedTrabajador">
        <p class="mb-4 text-lg font-semibold">{{ selectedTrabajador.nombre }}</p>

        <DataTable :value="historialData" responsiveLayout="scroll" :rows="10" :paginator="historialData.length > 10">
          <Column header="EPP">
            <template #body="{ data }">
              <div>
                <span class="font-medium">{{ data.producto?.nombre || data.tipo_epp?.nombre }}</span>
                <br>
                <Tag
                  :value="data.producto?.familia?.categoria_epp || data.tipo_epp?.categoria"
                  :severity="getCategoriaSeverity(data.producto?.familia?.categoria_epp || data.tipo_epp?.categoria)"
                  class="text-xs"
                />
              </div>
            </template>
          </Column>
          <Column field="cantidad" header="Cant." style="width: 60px"></Column>
          <Column field="fecha_entrega" header="Entrega" style="width: 100px">
            <template #body="{ data }">{{ formatDate(data.fecha_entrega) }}</template>
          </Column>
          <Column field="fecha_vencimiento" header="Vencimiento" style="width: 100px">
            <template #body="{ data }">{{ formatDate(data.fecha_vencimiento) }}</template>
          </Column>
          <Column field="estado" header="Estado" style="width: 110px">
            <template #body="{ data }">
              <Tag :value="data.estado" :severity="getEstadoSeverity(data.estado)" />
            </template>
          </Column>
          <Column field="entregado_por.nombre" header="Entregado Por"></Column>
        </DataTable>
      </template>
    </Dialog>
  </div>
</template>
