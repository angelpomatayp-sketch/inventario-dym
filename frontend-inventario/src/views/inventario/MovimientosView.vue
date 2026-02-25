<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Dialog from 'primevue/dialog'
import Select from 'primevue/select'
import Tag from 'primevue/tag'
import Textarea from 'primevue/textarea'
import DatePicker from 'primevue/datepicker'
import Toast from 'primevue/toast'
import AutoComplete from 'primevue/autocomplete'
import { useToast } from 'primevue/usetoast'
import { useAuthStore } from '@/stores/auth'
import api from '@/services/api'

const toast = useToast()
const authStore = useAuthStore()

// Datos del usuario para restricciones por rol
const esAlmacenero = computed(() => authStore.hasRole('almacenero'))
const almacenAsignado = computed(() => authStore.user?.almacen_id || null)

// Nombre del almacén asignado (para mostrar cuando está bloqueado)
const nombreAlmacenAsignado = computed(() => {
  // Primero intentar obtener directamente del objeto almacen del usuario
  if (authStore.user?.almacen?.nombre) {
    return authStore.user.almacen.nombre
  }

  // Fallback: buscar en el array de almacenes
  if (!almacenAsignado.value || almacenes.value.length === 0) return null
  // Comparar como enteros para evitar problemas de tipo string vs number
  const almacenId = parseInt(almacenAsignado.value)
  const almacen = almacenes.value.find(a => parseInt(a.value) === almacenId)
  return almacen?.label || null
})

// Estado
const movimientos = ref([])
const loading = ref(false)
const dialogVisible = ref(false)
const viewDialogVisible = ref(false)
const anularDialogVisible = ref(false)
const selectedMovimiento = ref(null)
const searchQuery = ref('')
const selectedTipo = ref(null)
const selectedEstado = ref(null)
const fechaRango = ref(null)

// Datos para selects
const almacenes = ref([])
const almacenesTransferencia = ref([])
const centrosCosto = ref([])
const productos = ref([])
const productosSugeridos = ref([])

// Formulario
const formData = ref({
  tipo: null,
  subtipo: null,
  almacen_origen_id: null,
  almacen_destino_id: null,
  proveedor_id: null,
  centro_costo_id: null,
  fecha: new Date(),
  documento_referencia: '',
  observaciones: '',
  detalles: []
})

// Nuevo detalle
const nuevoDetalle = ref({
  producto: null,
  cantidad: 1,
  costo_unitario: 0
})

// Motivo de anulación
const motivoAnulacion = ref('')

// Opciones
const tiposMovimiento = [
  { label: 'Entrada', value: 'ENTRADA', icon: 'pi-arrow-down', color: 'success' },
  { label: 'Salida', value: 'SALIDA', icon: 'pi-arrow-up', color: 'danger' },
  { label: 'Transferencia', value: 'TRANSFERENCIA', icon: 'pi-arrows-h', color: 'info' },
  { label: 'Ajuste', value: 'AJUSTE', icon: 'pi-sliders-h', color: 'warn' }
]

const subtiposEntrada = [
  { label: 'Compra', value: 'COMPRA' },
  { label: 'Devolución', value: 'DEVOLUCION' },
  { label: 'Ajuste Inventario', value: 'AJUSTE_INV' },
  { label: 'Transferencia Entrada', value: 'TRANS_ENT' }
]

const subtiposSalida = [
  { label: 'Consumo', value: 'CONSUMO' },
  { label: 'Requisición', value: 'REQUISICION' },
  { label: 'Merma', value: 'MERMA' },
  { label: 'Transferencia Salida', value: 'TRANS_SAL' }
]

const estadosMovimiento = [
  { label: 'En tránsito', value: 'PENDIENTE' },
  { label: 'Completado', value: 'COMPLETADO' },
  { label: 'Anulado', value: 'ANULADO' }
]

// Cargar datos
const loadMovimientos = async () => {
  loading.value = true
  try {
    const params = {}
    if (selectedTipo.value) params.tipo = selectedTipo.value
    if (selectedEstado.value) params.estado = selectedEstado.value
    if (fechaRango.value && fechaRango.value[0]) {
      params.fecha_inicio = fechaRango.value[0].toISOString().split('T')[0]
      if (fechaRango.value[1]) {
        params.fecha_fin = fechaRango.value[1].toISOString().split('T')[0]
      }
    }

    const response = await api.get('/inventario/movimientos', { params })
    if (response.data.success) {
      movimientos.value = response.data.data || []
    }
  } catch (err) {
    console.error('Error al cargar movimientos:', err)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'No se pudieron cargar los movimientos',
      life: 5000
    })
  } finally {
    loading.value = false
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

const loadAlmacenesTransferencia = async () => {
  try {
    const params = {
      all: true,
      solo_destinos_transferencia: true
    }

    if (almacenAsignado.value) {
      params.exclude_id = almacenAsignado.value
    }

    const response = await api.get('/administracion/almacenes', { params })
    if (response.data.success) {
      almacenesTransferencia.value = response.data.data.map(a => ({
        label: a.nombre,
        value: a.id
      }))
    }
  } catch (err) {
    console.error('Error al cargar almacenes destino de transferencia:', err)
    almacenesTransferencia.value = []
  }
}

const loadCentrosCosto = async () => {
  try {
    const response = await api.get('/administracion/centros-costo', { params: { all: true } })
    if (response.data.success) {
      centrosCosto.value = response.data.data.map(c => ({
        label: `${c.codigo} - ${c.nombre}`,
        value: c.id
      }))
    }
  } catch (err) {
    console.error('Error al cargar centros de costo:', err)
  }
}

const loadProductos = async () => {
  try {
    const response = await api.get('/inventario/productos', { params: { per_page: 100 } })
    if (response.data.success) {
      productos.value = response.data.data || []
    }
  } catch (err) {
    console.error('Error al cargar productos:', err)
  }
}

onMounted(() => {
  loadMovimientos()
  loadAlmacenes()
  loadAlmacenesTransferencia()
  loadCentrosCosto()
  loadProductos()
})

// Buscar productos para autocompletar
const buscarProducto = (event) => {
  const query = event.query.toLowerCase()
  productosSugeridos.value = productos.value.filter(p =>
    p.nombre.toLowerCase().includes(query) ||
    p.codigo.toLowerCase().includes(query)
  )
}

// Computed
const filteredMovimientos = computed(() => {
  if (!searchQuery.value) return movimientos.value
  const query = searchQuery.value.toLowerCase()
  return movimientos.value.filter(m =>
    m.numero?.toLowerCase().includes(query) ||
    m.documento_referencia?.toLowerCase().includes(query)
  )
})

const subtiposDisponibles = computed(() => {
  if (formData.value.tipo === 'ENTRADA') return subtiposEntrada
  if (formData.value.tipo === 'SALIDA') return subtiposSalida
  return []
})

const totalMovimiento = computed(() => {
  return formData.value.detalles.reduce((sum, d) => sum + (d.cantidad * d.costo_unitario), 0)
})

const requiereAlmacenOrigen = computed(() => {
  return ['SALIDA', 'TRANSFERENCIA'].includes(formData.value.tipo)
})

const requiereAlmacenDestino = computed(() => {
  return ['ENTRADA', 'TRANSFERENCIA'].includes(formData.value.tipo)
})

const requiereCosto = computed(() => {
  return formData.value.tipo === 'ENTRADA'
})

// Para almaceneros: bloquear selección de almacén según el tipo de movimiento
const almacenOrigenBloqueado = computed(() => {
  if (!esAlmacenero.value) return false
  // En SALIDA y TRANSFERENCIA, el origen es su almacén (bloqueado)
  return ['SALIDA', 'TRANSFERENCIA'].includes(formData.value.tipo)
})

const almacenDestinoBloqueado = computed(() => {
  if (!esAlmacenero.value) return false
  // En ENTRADA, el destino es su almacén (bloqueado)
  // En TRANSFERENCIA, puede elegir destino (no bloqueado)
  return formData.value.tipo === 'ENTRADA'
})

const almacenesDestinoDisponibles = computed(() => {
  if (formData.value.tipo === 'TRANSFERENCIA' && esAlmacenero.value) {
    return almacenesTransferencia.value
  }

  const origenId = Number(formData.value.almacen_origen_id || 0)
  return almacenes.value.filter(a => Number(a.value) !== origenId)
})

watch(
  () => [formData.value.tipo, formData.value.almacen_origen_id, formData.value.almacen_destino_id],
  () => {
    if (formData.value.tipo !== 'TRANSFERENCIA') return
    const origenId = Number(formData.value.almacen_origen_id || 0)
    const destinoId = Number(formData.value.almacen_destino_id || 0)
    if (origenId > 0 && destinoId > 0 && origenId === destinoId) {
      formData.value.almacen_destino_id = null
    }
  }
)

// Métodos
const getTipoSeverity = (tipo) => {
  const map = {
    'ENTRADA': 'success',
    'SALIDA': 'danger',
    'TRANSFERENCIA': 'info',
    'AJUSTE': 'warn'
  }
  return map[tipo] || 'secondary'
}

const getEstadoSeverity = (estado) => {
  if (estado === 'COMPLETADO') return 'success'
  if (estado === 'PENDIENTE') return 'warn'
  return 'danger'
}

const formatCurrency = (value) => {
  const numericValue = Number(value)
  const safeValue = Number.isFinite(numericValue) ? numericValue : 0
  return `S/ ${safeValue.toFixed(2)}`
}

const formatDate = (date) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('es-PE', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
  })
}

const openNew = async (tipo) => {
  // Asegurar que los almacenes estén cargados
  if (almacenes.value.length === 0) {
    await loadAlmacenes()
  }
  if (tipo === 'TRANSFERENCIA' && esAlmacenero.value && almacenesTransferencia.value.length === 0) {
    await loadAlmacenesTransferencia()
  }

  // Si es almacenero, pre-asignar su almacén
  let almacenOrigen = null
  let almacenDestino = null

  if (esAlmacenero.value && almacenAsignado.value) {
    // Convertir a entero para comparación y asignación consistente
    const almacenId = parseInt(almacenAsignado.value)
    // Verificar que el almacén asignado existe en la lista
    const almacenExiste = almacenes.value.some(a => parseInt(a.value) === almacenId)
    if (almacenExiste) {
      if (tipo === 'ENTRADA') {
        almacenDestino = almacenId
      } else if (tipo === 'SALIDA') {
        almacenOrigen = almacenId
      } else if (tipo === 'TRANSFERENCIA') {
        almacenOrigen = almacenId
      }
    }
  }

  formData.value = {
    tipo: tipo,
    subtipo: null,
    almacen_origen_id: almacenOrigen,
    almacen_destino_id: almacenDestino,
    proveedor_id: null,
    centro_costo_id: null,
    fecha: new Date(),
    documento_referencia: '',
    observaciones: '',
    detalles: []
  }
  nuevoDetalle.value = { producto: null, cantidad: 1, costo_unitario: 0 }
  dialogVisible.value = true
}

const agregarDetalle = () => {
  if (!nuevoDetalle.value.producto) {
    toast.add({ severity: 'warn', summary: 'Atención', detail: 'Seleccione un producto', life: 3000 })
    return
  }

  if (nuevoDetalle.value.cantidad <= 0) {
    toast.add({ severity: 'warn', summary: 'Atención', detail: 'La cantidad debe ser mayor a 0', life: 3000 })
    return
  }

  // Verificar si el producto ya está en la lista
  const existe = formData.value.detalles.find(d => d.producto_id === nuevoDetalle.value.producto.id)
  if (existe) {
    toast.add({ severity: 'warn', summary: 'Atención', detail: 'El producto ya está en la lista', life: 3000 })
    return
  }

  formData.value.detalles.push({
    producto_id: nuevoDetalle.value.producto.id,
    producto_codigo: nuevoDetalle.value.producto.codigo,
    producto_nombre: nuevoDetalle.value.producto.nombre,
    unidad: nuevoDetalle.value.producto.unidad_medida,
    cantidad: nuevoDetalle.value.cantidad,
    costo_unitario: nuevoDetalle.value.costo_unitario,
    stock_disponible: nuevoDetalle.value.producto.stock_total || 0
  })

  nuevoDetalle.value = { producto: null, cantidad: 1, costo_unitario: 0 }
}

const quitarDetalle = (index) => {
  formData.value.detalles.splice(index, 1)
}

const viewMovimiento = async (movimiento) => {
  try {
    const response = await api.get(`/inventario/movimientos/${movimiento.id}`)
    if (response.data.success) {
      selectedMovimiento.value = response.data.data
      viewDialogVisible.value = true
    }
  } catch (err) {
    console.error('Error al cargar movimiento:', err)
    toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudo cargar el detalle', life: 5000 })
  }
}

const confirmAnular = (movimiento) => {
  selectedMovimiento.value = movimiento
  motivoAnulacion.value = ''
  anularDialogVisible.value = true
}

const saveMovimiento = async () => {
  // Validaciones
  if (!formData.value.tipo) {
    toast.add({ severity: 'warn', summary: 'Atención', detail: 'Seleccione el tipo de movimiento', life: 3000 })
    return
  }

  if (requiereAlmacenOrigen.value && !formData.value.almacen_origen_id) {
    toast.add({ severity: 'warn', summary: 'Atención', detail: 'Seleccione el almacén de origen', life: 3000 })
    return
  }

  if (requiereAlmacenDestino.value && !formData.value.almacen_destino_id) {
    toast.add({ severity: 'warn', summary: 'Atención', detail: 'Seleccione el almacén de destino', life: 3000 })
    return
  }

  if (formData.value.detalles.length === 0) {
    toast.add({ severity: 'warn', summary: 'Atención', detail: 'Agregue al menos un producto', life: 3000 })
    return
  }

  loading.value = true
  try {
    const dataToSend = {
      tipo: formData.value.tipo,
      subtipo: formData.value.subtipo,
      almacen_origen_id: formData.value.almacen_origen_id,
      almacen_destino_id: formData.value.almacen_destino_id,
      centro_costo_id: formData.value.centro_costo_id,
      fecha: formData.value.fecha.toISOString().split('T')[0],
      documento_referencia: formData.value.documento_referencia,
      observaciones: formData.value.observaciones,
      detalles: formData.value.detalles.map(d => ({
        producto_id: d.producto_id,
        cantidad: d.cantidad,
        costo_unitario: d.costo_unitario
      }))
    }

    const response = await api.post('/inventario/movimientos', dataToSend)

    if (response.data.success) {
      toast.add({
        severity: 'success',
        summary: 'Movimiento registrado',
        detail: `Se registró el movimiento ${response.data.data.numero}`,
        life: 3000
      })
      dialogVisible.value = false
      await loadMovimientos()
      await loadProductos() // Recargar para actualizar stock
    }
  } catch (err) {
    console.error('Error al guardar movimiento:', err)
    const errorMessage = err.response?.data?.message || 'Error al registrar el movimiento'
    toast.add({ severity: 'error', summary: 'Error', detail: errorMessage, life: 5000 })
  } finally {
    loading.value = false
  }
}

const anularMovimiento = async () => {
  if (!motivoAnulacion.value.trim()) {
    toast.add({ severity: 'warn', summary: 'Atención', detail: 'Ingrese el motivo de anulación', life: 3000 })
    return
  }

  loading.value = true
  try {
    const response = await api.post(`/inventario/movimientos/${selectedMovimiento.value.id}/anular`, {
      motivo: motivoAnulacion.value
    })

    if (response.data.success) {
      toast.add({
        severity: 'success',
        summary: 'Movimiento anulado',
        detail: 'El movimiento fue anulado y el stock revertido',
        life: 3000
      })
      anularDialogVisible.value = false
      await loadMovimientos()
      await loadProductos()
    }
  } catch (err) {
    console.error('Error al anular:', err)
    const errorMessage = err.response?.data?.message || 'Error al anular el movimiento'
    toast.add({ severity: 'error', summary: 'Error', detail: errorMessage, life: 5000 })
  } finally {
    loading.value = false
  }
}

const confirmarRecepcion = async (movimiento) => {
  loading.value = true
  try {
    const response = await api.post(`/inventario/movimientos/${movimiento.id}/confirmar-recepcion`)
    if (response.data.success) {
      toast.add({
        severity: 'success',
        summary: 'Recepción confirmada',
        detail: 'La transferencia fue recepcionada y el stock se actualizó en destino',
        life: 3000
      })
      await loadMovimientos()
      await loadProductos()
    }
  } catch (err) {
    console.error('Error al confirmar recepción:', err)
    const errorMessage = err.response?.data?.message || 'Error al confirmar la recepción'
    toast.add({ severity: 'error', summary: 'Error', detail: errorMessage, life: 5000 })
  } finally {
    loading.value = false
  }
}

// Watch para recargar al cambiar filtros
watch([selectedTipo, selectedEstado, fechaRango], () => {
  loadMovimientos()
})
</script>

<template>
  <div class="space-y-4">
    <Toast />

    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold text-gray-800">Movimientos de Inventario</h2>
        <p class="text-gray-500 text-sm">Registro de entradas, salidas y transferencias</p>
      </div>
      <div class="flex gap-2">
        <Button
          label="Entrada"
          icon="pi pi-arrow-down"
          severity="success"
          @click="openNew('ENTRADA')"
        />
        <Button
          label="Salida"
          icon="pi pi-arrow-up"
          severity="danger"
          @click="openNew('SALIDA')"
        />
        <Button
          label="Transferencia"
          icon="pi pi-arrows-h"
          severity="info"
          @click="openNew('TRANSFERENCIA')"
        />
      </div>
    </div>

    <!-- Cards de resumen -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <div class="bg-white rounded-lg p-4 shadow-sm border-l-4 border-green-500">
        <p class="text-sm text-gray-500">Entradas (Hoy)</p>
        <p class="text-2xl font-bold text-green-600">
          {{ movimientos.filter(m => m.tipo === 'ENTRADA' && m.estado === 'COMPLETADO').length }}
        </p>
      </div>
      <div class="bg-white rounded-lg p-4 shadow-sm border-l-4 border-red-500">
        <p class="text-sm text-gray-500">Salidas (Hoy)</p>
        <p class="text-2xl font-bold text-red-600">
          {{ movimientos.filter(m => m.tipo === 'SALIDA' && m.estado === 'COMPLETADO').length }}
        </p>
      </div>
      <div class="bg-white rounded-lg p-4 shadow-sm border-l-4 border-blue-500">
        <p class="text-sm text-gray-500">Transferencias</p>
        <p class="text-2xl font-bold text-blue-600">
          {{ movimientos.filter(m => m.tipo === 'TRANSFERENCIA' && m.estado !== 'ANULADO').length }}
        </p>
      </div>
      <div class="bg-white rounded-lg p-4 shadow-sm border-l-4 border-gray-400">
        <p class="text-sm text-gray-500">Total Movimientos</p>
        <p class="text-2xl font-bold text-gray-700">{{ movimientos.length }}</p>
      </div>
    </div>

    <!-- Tabla -->
    <div class="bg-white rounded-xl shadow-sm p-4">
      <!-- Filtros -->
      <div class="flex flex-wrap items-center gap-4 mb-4">
        <div class="relative flex-1 min-w-64 max-w-md">
          <i class="pi pi-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
          <InputText
            v-model="searchQuery"
            placeholder="Buscar por número o documento..."
            class="w-full pl-10"
          />
        </div>
        <Select
          v-model="selectedTipo"
          :options="tiposMovimiento"
          optionLabel="label"
          optionValue="value"
          placeholder="Tipo"
          class="w-40"
          showClear
        >
          <template #value="slotProps">
            <span v-if="slotProps.value">{{ tiposMovimiento.find(t => t.value === slotProps.value)?.label }}</span>
            <span v-else class="text-gray-400">{{ slotProps.placeholder }}</span>
          </template>
        </Select>
        <Select
          v-model="selectedEstado"
          :options="estadosMovimiento"
          optionLabel="label"
          optionValue="value"
          placeholder="Estado"
          class="w-40"
          showClear
        >
          <template #value="slotProps">
            <span v-if="slotProps.value">{{ estadosMovimiento.find(e => e.value === slotProps.value)?.label }}</span>
            <span v-else class="text-gray-400">{{ slotProps.placeholder }}</span>
          </template>
        </Select>
        <DatePicker
          v-model="fechaRango"
          selectionMode="range"
          placeholder="Rango de fechas"
          dateFormat="dd/mm/yy"
          class="w-56"
          showButtonBar
        />
      </div>

      <DataTable
        :value="filteredMovimientos"
        :loading="loading"
        paginator
        :rows="10"
        stripedRows
        :rowClass="(data) => data.estado === 'ANULADO' ? 'bg-red-50 line-through' : ''"
      >
        <Column field="numero" header="Número" sortable style="width: 150px">
          <template #body="{ data }">
            <span class="font-mono text-sm font-medium text-gray-700">{{ data.numero }}</span>
          </template>
        </Column>
        <Column field="tipo" header="Tipo" sortable style="width: 120px">
          <template #body="{ data }">
            <Tag :value="data.tipo" :severity="getTipoSeverity(data.tipo)" />
          </template>
        </Column>
        <Column field="fecha" header="Fecha" sortable style="width: 110px">
          <template #body="{ data }">
            {{ formatDate(data.fecha) }}
          </template>
        </Column>
        <Column header="Almacén" style="width: 200px">
          <template #body="{ data }">
            <div class="text-sm">
              <span v-if="data.almacen_origen">
                <i class="pi pi-arrow-right text-xs mr-1 text-red-500"></i>
                {{ data.almacen_origen.nombre }}
              </span>
              <span v-if="data.almacen_origen && data.almacen_destino" class="mx-1">→</span>
              <span v-if="data.almacen_destino">
                <i class="pi pi-arrow-down text-xs mr-1 text-green-500"></i>
                {{ data.almacen_destino.nombre }}
              </span>
            </div>
          </template>
        </Column>
        <Column field="detalles_count" header="Items" style="width: 80px" class="text-center" />
        <Column field="documento_referencia" header="Doc. Ref." style="width: 120px">
          <template #body="{ data }">
            <span class="text-sm text-gray-600">{{ data.documento_referencia || '-' }}</span>
          </template>
        </Column>
        <Column field="estado" header="Estado" style="width: 110px">
          <template #body="{ data }">
            <Tag :value="data.estado" :severity="getEstadoSeverity(data.estado)" />
          </template>
        </Column>
        <Column header="Acciones" style="width: 120px">
          <template #body="{ data }">
            <div class="flex gap-1">
              <Button icon="pi pi-eye" severity="info" text rounded @click="viewMovimiento(data)" />
              <Button
                v-if="data.tipo === 'TRANSFERENCIA' && data.estado === 'PENDIENTE'"
                icon="pi pi-check-circle"
                severity="success"
                text
                rounded
                @click="confirmarRecepcion(data)"
                v-tooltip.top="'Confirmar recepción'"
              />
              <Button
                v-if="data.estado !== 'ANULADO'"
                icon="pi pi-ban"
                severity="danger"
                text
                rounded
                @click="confirmAnular(data)"
                v-tooltip.top="'Anular'"
              />
            </div>
          </template>
        </Column>
      </DataTable>
    </div>

    <!-- Dialog Crear Movimiento -->
    <Dialog
      v-model:visible="dialogVisible"
      :header="`Nuevo Movimiento - ${formData.tipo}`"
      modal
      :style="{ width: '900px', maxHeight: '90vh' }"
      :contentStyle="{ overflow: 'auto', maxHeight: 'calc(90vh - 120px)' }"
    >
      <div class="space-y-4">
        <!-- Tipo y Subtipo -->
        <div class="grid grid-cols-3 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Tipo *</label>
            <Select
              v-model="formData.tipo"
              :options="tiposMovimiento"
              optionLabel="label"
              optionValue="value"
              placeholder="Seleccione tipo"
              class="w-full"
              disabled
            >
              <template #value="slotProps">
                <span v-if="slotProps.value">{{ tiposMovimiento.find(t => t.value === slotProps.value)?.label }}</span>
                <span v-else class="text-gray-400">{{ slotProps.placeholder }}</span>
              </template>
            </Select>
          </div>
          <div v-if="subtiposDisponibles.length > 0">
            <label class="block text-sm font-medium text-gray-700 mb-1">Subtipo</label>
            <Select
              v-model="formData.subtipo"
              :options="subtiposDisponibles"
              optionLabel="label"
              optionValue="value"
              placeholder="Seleccione subtipo"
              class="w-full"
              showClear
            >
              <template #value="slotProps">
                <span v-if="slotProps.value">{{ subtiposDisponibles.find(s => s.value === slotProps.value)?.label }}</span>
                <span v-else class="text-gray-400">{{ slotProps.placeholder }}</span>
              </template>
            </Select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Fecha *</label>
            <DatePicker
              v-model="formData.fecha"
              dateFormat="dd/mm/yy"
              class="w-full"
              showIcon
            />
          </div>
        </div>

        <!-- Almacenes -->
        <div class="grid grid-cols-2 gap-4">
          <div v-if="requiereAlmacenOrigen">
            <label class="block text-sm font-medium text-gray-700 mb-1">Almacén Origen *</label>
            <!-- Si está bloqueado, mostrar InputText con el nombre -->
            <InputText
              v-if="almacenOrigenBloqueado && nombreAlmacenAsignado"
              :modelValue="nombreAlmacenAsignado"
              class="w-full"
              disabled
            />
            <!-- Si no está bloqueado, mostrar Select -->
            <Select
              v-else
              v-model="formData.almacen_origen_id"
              :options="almacenes"
              optionLabel="label"
              optionValue="value"
              placeholder="Seleccione almacén"
              class="w-full"
            >
              <template #value="slotProps">
                <span v-if="slotProps.value">{{ almacenes.find(a => a.value === slotProps.value)?.label }}</span>
                <span v-else class="text-gray-400">{{ slotProps.placeholder }}</span>
              </template>
            </Select>
            <small v-if="almacenOrigenBloqueado" class="text-blue-600 flex items-center gap-1 mt-1">
              <i class="pi pi-lock text-xs"></i> Tu almacén asignado
            </small>
          </div>
          <div v-if="requiereAlmacenDestino">
            <label class="block text-sm font-medium text-gray-700 mb-1">Almacén Destino *</label>
            <!-- Si está bloqueado, mostrar InputText con el nombre -->
            <InputText
              v-if="almacenDestinoBloqueado && nombreAlmacenAsignado"
              :modelValue="nombreAlmacenAsignado"
              class="w-full"
              disabled
            />
            <!-- Si no está bloqueado, mostrar Select -->
            <Select
              v-else
              v-model="formData.almacen_destino_id"
              :options="formData.tipo === 'TRANSFERENCIA' ? almacenesDestinoDisponibles : almacenes"
              optionLabel="label"
              optionValue="value"
              placeholder="Seleccione almacén"
              class="w-full"
            >
              <template #value="slotProps">
                <span v-if="slotProps.value">{{ (formData.tipo === 'TRANSFERENCIA' ? almacenesDestinoDisponibles : almacenes).find(a => a.value === slotProps.value)?.label }}</span>
                <span v-else class="text-gray-400">{{ slotProps.placeholder }}</span>
              </template>
            </Select>
            <small v-if="almacenDestinoBloqueado" class="text-blue-600 flex items-center gap-1 mt-1">
              <i class="pi pi-lock text-xs"></i> Tu almacén asignado
            </small>
          </div>
        </div>

        <!-- Centro de costo y documento -->
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Centro de Costo</label>
            <Select
              v-model="formData.centro_costo_id"
              :options="centrosCosto"
              optionLabel="label"
              optionValue="value"
              placeholder="Seleccione (opcional)"
              class="w-full"
              showClear
            >
              <template #value="slotProps">
                <span v-if="slotProps.value">{{ centrosCosto.find(c => c.value === slotProps.value)?.label }}</span>
                <span v-else class="text-gray-400">{{ slotProps.placeholder }}</span>
              </template>
            </Select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Doc. Referencia</label>
            <InputText
              v-model="formData.documento_referencia"
              class="w-full"
              placeholder="Ej: FAC-001, GR-123"
            />
          </div>
        </div>

        <!-- Agregar productos -->
        <div class="border rounded-lg p-4 bg-gray-50">
          <h4 class="font-medium text-gray-700 mb-3">Agregar Productos</h4>
          <div class="grid grid-cols-12 gap-2 items-end">
            <div class="col-span-5">
              <label class="block text-xs text-gray-500 mb-1">Producto</label>
              <AutoComplete
                v-model="nuevoDetalle.producto"
                :suggestions="productosSugeridos"
                @complete="buscarProducto"
                optionLabel="nombre"
                placeholder="Buscar producto..."
                class="w-full"
                dropdown
              >
                <template #option="{ option }">
                  <div class="flex justify-between items-center">
                    <div>
                      <span class="font-mono text-sm">{{ option.codigo }}</span>
                      <span class="ml-2">{{ option.nombre }}</span>
                    </div>
                    <span class="text-xs text-gray-500">Stock: {{ option.stock_total || 0 }}</span>
                  </div>
                </template>
              </AutoComplete>
            </div>
            <div class="col-span-2">
              <label class="block text-xs text-gray-500 mb-1">Cantidad</label>
              <InputNumber
                v-model="nuevoDetalle.cantidad"
                :min="0.01"
                :minFractionDigits="0"
                :maxFractionDigits="2"
                class="w-full"
              />
            </div>
            <div class="col-span-3" v-if="requiereCosto">
              <label class="block text-xs text-gray-500 mb-1">Costo Unit.</label>
              <InputNumber
                v-model="nuevoDetalle.costo_unitario"
                mode="currency"
                currency="PEN"
                locale="es-PE"
                :min="0"
                class="w-full"
              />
            </div>
            <div :class="requiereCosto ? 'col-span-2' : 'col-span-5'">
              <Button
                label="Agregar"
                icon="pi pi-plus"
                @click="agregarDetalle"
                class="w-full"
                severity="secondary"
              />
            </div>
          </div>
        </div>

        <!-- Lista de productos -->
        <div class="border rounded-lg overflow-hidden">
          <table class="w-full text-sm">
            <thead class="bg-gray-100">
              <tr>
                <th class="text-left p-3">Código</th>
                <th class="text-left p-3">Producto</th>
                <th class="text-center p-3">Cantidad</th>
                <th class="text-right p-3" v-if="requiereCosto">Costo Unit.</th>
                <th class="text-right p-3" v-if="requiereCosto">Subtotal</th>
                <th class="text-center p-3 w-16"></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(detalle, index) in formData.detalles" :key="index" class="border-t">
                <td class="p-3 font-mono text-sm">{{ detalle.producto_codigo }}</td>
                <td class="p-3">{{ detalle.producto_nombre }}</td>
                <td class="p-3 text-center">{{ detalle.cantidad }} {{ detalle.unidad }}</td>
                <td class="p-3 text-right" v-if="requiereCosto">{{ formatCurrency(detalle.costo_unitario) }}</td>
                <td class="p-3 text-right font-medium" v-if="requiereCosto">
                  {{ formatCurrency(detalle.cantidad * detalle.costo_unitario) }}
                </td>
                <td class="p-3 text-center">
                  <Button
                    icon="pi pi-trash"
                    severity="danger"
                    text
                    rounded
                    size="small"
                    @click="quitarDetalle(index)"
                  />
                </td>
              </tr>
              <tr v-if="formData.detalles.length === 0">
                <td :colspan="requiereCosto ? 6 : 4" class="p-6 text-center text-gray-400">
                  No hay productos agregados
                </td>
              </tr>
            </tbody>
            <tfoot v-if="requiereCosto && formData.detalles.length > 0" class="bg-gray-50 border-t-2">
              <tr>
                <td colspan="4" class="p-3 text-right font-medium">Total:</td>
                <td class="p-3 text-right font-bold text-lg">{{ formatCurrency(totalMovimiento) }}</td>
                <td></td>
              </tr>
            </tfoot>
          </table>
        </div>

        <!-- Observaciones -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Observaciones</label>
          <Textarea
            v-model="formData.observaciones"
            rows="2"
            class="w-full"
            placeholder="Notas adicionales (opcional)"
          />
        </div>
      </div>

      <template #footer>
        <Button label="Cancelar" severity="secondary" @click="dialogVisible = false" />
        <Button
          label="Registrar Movimiento"
          icon="pi pi-check"
          @click="saveMovimiento"
          :loading="loading"
          class="!bg-amber-600 !border-amber-600"
        />
      </template>
    </Dialog>

    <!-- Dialog Ver Detalle -->
    <Dialog
      v-model:visible="viewDialogVisible"
      header="Detalle del Movimiento"
      modal
      :style="{ width: '700px', maxHeight: '90vh' }"
      :contentStyle="{ overflow: 'auto', maxHeight: 'calc(90vh - 120px)' }"
    >
      <div v-if="selectedMovimiento" class="space-y-4">
        <div class="grid grid-cols-2 gap-4 bg-gray-50 p-4 rounded-lg">
          <div>
            <p class="text-sm text-gray-500">Número</p>
            <p class="font-mono font-bold">{{ selectedMovimiento.numero }}</p>
          </div>
          <div>
            <p class="text-sm text-gray-500">Tipo</p>
            <Tag :value="selectedMovimiento.tipo" :severity="getTipoSeverity(selectedMovimiento.tipo)" />
          </div>
          <div>
            <p class="text-sm text-gray-500">Fecha</p>
            <p class="font-medium">{{ formatDate(selectedMovimiento.fecha) }}</p>
          </div>
          <div>
            <p class="text-sm text-gray-500">Estado</p>
            <Tag :value="selectedMovimiento.estado" :severity="getEstadoSeverity(selectedMovimiento.estado)" />
          </div>
          <div v-if="selectedMovimiento.almacen_origen">
            <p class="text-sm text-gray-500">Almacén Origen</p>
            <p class="font-medium">{{ selectedMovimiento.almacen_origen.nombre }}</p>
          </div>
          <div v-if="selectedMovimiento.almacen_destino">
            <p class="text-sm text-gray-500">Almacén Destino</p>
            <p class="font-medium">{{ selectedMovimiento.almacen_destino.nombre }}</p>
          </div>
          <div v-if="selectedMovimiento.centro_costo">
            <p class="text-sm text-gray-500">Centro de Costo</p>
            <p class="font-medium">{{ selectedMovimiento.centro_costo.nombre }}</p>
          </div>
          <div>
            <p class="text-sm text-gray-500">Usuario</p>
            <p class="font-medium">{{ selectedMovimiento.usuario?.nombre || '-' }}</p>
          </div>
        </div>

        <!-- Detalles -->
        <div class="border rounded-lg overflow-hidden">
          <table class="w-full text-sm">
            <thead class="bg-gray-100">
              <tr>
                <th class="text-left p-3">Producto</th>
                <th class="text-center p-3">Cantidad</th>
                <th class="text-right p-3">Costo Unit.</th>
                <th class="text-right p-3">Total</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="detalle in selectedMovimiento.detalles" :key="detalle.id" class="border-t">
                <td class="p-3">
                  <p class="font-medium">{{ detalle.producto?.nombre }}</p>
                  <p class="text-xs text-gray-500">{{ detalle.producto?.codigo }}</p>
                </td>
                <td class="p-3 text-center">{{ detalle.cantidad }}</td>
                <td class="p-3 text-right">{{ formatCurrency(detalle.costo_unitario) }}</td>
                <td class="p-3 text-right font-medium">{{ formatCurrency(detalle.costo_total) }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="selectedMovimiento.observaciones" class="bg-gray-50 p-3 rounded-lg">
          <p class="text-sm text-gray-500 mb-1">Observaciones</p>
          <p class="text-gray-700 whitespace-pre-line">{{ selectedMovimiento.observaciones }}</p>
        </div>
      </div>

      <template #footer>
        <Button label="Cerrar" severity="secondary" @click="viewDialogVisible = false" />
      </template>
    </Dialog>

    <!-- Dialog Anular -->
    <Dialog
      v-model:visible="anularDialogVisible"
      header="Anular Movimiento"
      modal
      :style="{ width: '450px' }"
    >
      <div class="space-y-4">
        <div class="flex items-center gap-3 p-4 bg-red-50 rounded-lg">
          <i class="pi pi-exclamation-triangle text-3xl text-red-500"></i>
          <div>
            <p class="font-medium text-red-800">¿Está seguro de anular este movimiento?</p>
            <p class="text-sm text-red-600">
              Esta acción revertirá los cambios de stock y no se puede deshacer.
            </p>
          </div>
        </div>

        <div v-if="selectedMovimiento" class="text-sm">
          <p><strong>Número:</strong> {{ selectedMovimiento.numero }}</p>
          <p><strong>Tipo:</strong> {{ selectedMovimiento.tipo }}</p>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Motivo de anulación *</label>
          <Textarea
            v-model="motivoAnulacion"
            rows="3"
            class="w-full"
            placeholder="Ingrese el motivo de la anulación..."
          />
        </div>
      </div>

      <template #footer>
        <Button label="Cancelar" severity="secondary" @click="anularDialogVisible = false" />
        <Button
          label="Anular Movimiento"
          severity="danger"
          icon="pi pi-ban"
          @click="anularMovimiento"
          :loading="loading"
        />
      </template>
    </Dialog>
  </div>
</template>
