<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '@/services/api'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import Dropdown from 'primevue/dropdown'
import Calendar from 'primevue/calendar'
import InputNumber from 'primevue/inputnumber'
import Tag from 'primevue/tag'
import TabView from 'primevue/tabview'
import TabPanel from 'primevue/tabpanel'
import Card from 'primevue/card'
import { useToast } from 'primevue/usetoast'

const toast = useToast()

// Estados
const loading = ref(false)
const activeTab = ref(0)

// Datos de préstamos
const prestamos = ref([])
const prestamosPaginacion = ref({})
const filtrosPrestamo = ref({
  estado: null,
  trabajador_id: null,
  solo_vencidos: false
})

// Datos de equipos
const equipos = ref([])
const equiposPaginacion = ref({})
const equiposDisponibles = ref([])

// Catálogos
const trabajadores = ref([])
const centrosCosto = ref([])
const almacenes = ref([])
const productos = ref([])

// Estadísticas
const estadisticas = ref({
  prestamos_activos: 0,
  prestamos_vencidos: 0,
  por_vencer: 0,
  equipos_disponibles: 0,
  equipos_prestados: 0
})

// Diálogos
const dialogEquipo = ref(false)
const dialogPrestamo = ref(false)
const dialogDevolucion = ref(false)
const dialogRenovacion = ref(false)
const dialogHistorial = ref(false)
const dialogImportar = ref(false)

// Formularios
const equipo = ref({})
const prestamo = ref({})
const devolucion = ref({})
const renovacion = ref({})
const historial = ref([])

// Importar productos
const productosParaImportar = ref([])
const productosSeleccionados = ref([])
const tipoControlImportar = ref('CANTIDAD')
const buscarProducto = ref('')
const familias = ref([])
const familiasPrestables = ref([])

// Estados del préstamo para filtro
const estadosPrestamo = [
  { label: 'Todos', value: null },
  { label: 'Activos', value: 'ACTIVO' },
  { label: 'Vencidos', value: 'VENCIDO' },
  { label: 'Devueltos', value: 'DEVUELTO' },
  { label: 'Perdidos', value: 'PERDIDO' },
  { label: 'Dañados', value: 'DANADO' }
]

const tiposControl = [
  { label: 'Individual', value: 'INDIVIDUAL' },
  { label: 'Por Cantidad', value: 'CANTIDAD' }
]

const condicionesDevolucion = [
  { label: 'Bueno', value: 'BUENO' },
  { label: 'Regular', value: 'REGULAR' },
  { label: 'Malo/Dañado', value: 'MALO' },
  { label: 'Perdido', value: 'PERDIDO' }
]

// Computed
const modoEdicionEquipo = computed(() => !!equipo.value.id)

// Métodos - Cargar datos
const cargarDatos = async () => {
  await Promise.all([
    cargarPrestamos(),
    cargarEquipos(),
    cargarEstadisticas(),
    cargarCatalogos()
  ])
}

const cargarPrestamos = async () => {
  try {
    loading.value = true
    const params = { ...filtrosPrestamo.value }
    const response = await api.get('/prestamos', { params })
    prestamos.value = response.data.data.data || []
    prestamosPaginacion.value = response.data.data
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Error cargando préstamos', life: 3000 })
  } finally {
    loading.value = false
  }
}

const cargarEquipos = async () => {
  try {
    const response = await api.get('/prestamos/equipos')
    equipos.value = response.data.data.data || []
    equiposPaginacion.value = response.data.data
  } catch (error) {
    console.error('Error cargando equipos:', error)
  }
}

const cargarEquiposDisponibles = async () => {
  try {
    const response = await api.get('/prestamos/equipos/disponibles')
    equiposDisponibles.value = response.data.data || []
  } catch (error) {
    console.error('Error cargando equipos disponibles:', error)
  }
}

const cargarEstadisticas = async () => {
  try {
    const response = await api.get('/prestamos/estadisticas')
    estadisticas.value = response.data.data
  } catch (error) {
    console.error('Error cargando estadísticas:', error)
  }
}

const cargarCatalogos = async () => {
  // Usar Promise.allSettled para que no falle todo si un endpoint falla
  const results = await Promise.allSettled([
    api.get('/prestamos/personal'),  // Trabajadores + usuarios combinados
    api.get('/prestamos/centros-costo'),  // Filtrado según permisos del usuario
    api.get('/administracion/almacenes'),
    api.get('/inventario/productos', { params: { per_page: 1000 } }),
    api.get('/inventario/familias')
  ])

  // Personal (trabajadores + usuarios combinados)
  if (results[0].status === 'fulfilled') {
    const data = results[0].value.data.data
    trabajadores.value = data || []
    console.log('Personal cargado:', trabajadores.value.length)
  } else {
    console.error('Error cargando personal:', results[0].reason)
  }

  // Centros de costo (filtrado para almacenero)
  if (results[1].status === 'fulfilled') {
    const data = results[1].value.data.data
    centrosCosto.value = data || []
  } else {
    console.error('Error cargando centros de costo:', results[1].reason)
  }

  // Almacenes
  if (results[2].status === 'fulfilled') {
    const data = results[2].value.data.data
    almacenes.value = data?.data || data || []
  } else {
    console.error('Error cargando almacenes:', results[2].reason)
  }

  // Productos
  if (results[3].status === 'fulfilled') {
    const data = results[3].value.data.data
    productos.value = data?.data || data || []
  } else {
    console.error('Error cargando productos:', results[3].reason)
  }

  // Familias
  if (results[4].status === 'fulfilled') {
    const data = results[4].value.data.data
    familias.value = data?.data || data || []
  } else {
    console.error('Error cargando familias:', results[4].reason)
  }
}

// Métodos - Importar productos
const abrirImportarProductos = async () => {
  productosSeleccionados.value = []
  tipoControlImportar.value = 'CANTIDAD'
  buscarProducto.value = ''
  await Promise.all([
    cargarProductosParaImportar(),
    cargarFamiliasPrestables()
  ])
  dialogImportar.value = true
}

const cargarFamiliasPrestables = async () => {
  try {
    const response = await api.get('/prestamos/familias-prestables')
    familiasPrestables.value = response.data.data || []
    console.log('Familias prestables:', familiasPrestables.value)
  } catch (error) {
    console.error('Error cargando familias prestables:', error)
  }
}

const cargarProductosParaImportar = async () => {
  try {
    const params = {}
    if (buscarProducto.value) params.buscar = buscarProducto.value
    const response = await api.get('/prestamos/productos-para-importar', { params })
    productosParaImportar.value = response.data.data || []
    console.log('Productos disponibles para importar:', productosParaImportar.value)
    if (productosParaImportar.value.length === 0) {
      toast.add({
        severity: 'info',
        summary: 'Sin productos',
        detail: 'No hay productos de tipo herramientas/equipos disponibles para importar. Verifique que existan productos en esas familias.',
        life: 5000
      })
    }
  } catch (error) {
    console.error('Error cargando productos:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Error al cargar productos: ' + (error.response?.data?.message || error.message),
      life: 5000
    })
  }
}

const importarProductosSeleccionados = async () => {
  if (productosSeleccionados.value.length === 0) {
    toast.add({ severity: 'warn', summary: 'Atención', detail: 'Seleccione al menos un producto', life: 3000 })
    return
  }

  try {
    loading.value = true
    const response = await api.post('/prestamos/importar-productos', {
      producto_ids: productosSeleccionados.value.map(p => p.id),
      tipo_control: tipoControlImportar.value
    })

    const result = response.data.data
    toast.add({
      severity: 'success',
      summary: 'Éxito',
      detail: `Se importaron ${result.importados} productos como equipos prestables`,
      life: 3000
    })

    if (result.errores && result.errores.length > 0) {
      console.warn('Errores de importación:', result.errores)
    }

    dialogImportar.value = false
    await cargarEquipos()
    await cargarEstadisticas()
  } catch (error) {
    const msg = error.response?.data?.message || 'Error importando productos'
    toast.add({ severity: 'error', summary: 'Error', detail: msg, life: 3000 })
  } finally {
    loading.value = false
  }
}

// Métodos - Equipos
const nuevoEquipo = () => {
  equipo.value = {
    codigo: '',
    nombre: '',
    descripcion: '',
    numero_serie: '',
    marca: '',
    modelo: '',
    tipo_control: 'INDIVIDUAL',
    cantidad_total: 1,
    almacen_id: null,
    ubicacion_fisica: '',
    valor_referencial: null,
    fecha_adquisicion: null,
    producto_id: null,
    notas: ''
  }
  dialogEquipo.value = true
}

const editarEquipo = (item) => {
  equipo.value = { ...item }
  if (equipo.value.fecha_adquisicion) {
    equipo.value.fecha_adquisicion = new Date(equipo.value.fecha_adquisicion)
  }
  dialogEquipo.value = true
}

const guardarEquipo = async () => {
  try {
    loading.value = true
    const data = { ...equipo.value }

    if (data.fecha_adquisicion) {
      data.fecha_adquisicion = data.fecha_adquisicion.toISOString().split('T')[0]
    }

    if (modoEdicionEquipo.value) {
      await api.put(`/prestamos/equipos/${equipo.value.id}`, data)
      toast.add({ severity: 'success', summary: 'Éxito', detail: 'Equipo actualizado', life: 3000 })
    } else {
      await api.post('/prestamos/equipos', data)
      toast.add({ severity: 'success', summary: 'Éxito', detail: 'Equipo registrado', life: 3000 })
    }

    dialogEquipo.value = false
    await cargarEquipos()
  } catch (error) {
    const msg = error.response?.data?.message || 'Error guardando equipo'
    toast.add({ severity: 'error', summary: 'Error', detail: msg, life: 3000 })
  } finally {
    loading.value = false
  }
}

const eliminarEquipo = async (item) => {
  if (!confirm(`¿Eliminar el equipo "${item.nombre}"?`)) return

  try {
    await api.delete(`/prestamos/equipos/${item.id}`)
    toast.add({ severity: 'success', summary: 'Éxito', detail: 'Equipo eliminado', life: 3000 })
    await cargarEquipos()
  } catch (error) {
    const msg = error.response?.data?.message || 'Error eliminando equipo'
    toast.add({ severity: 'error', summary: 'Error', detail: msg, life: 3000 })
  }
}

// Métodos - Préstamos
const nuevoPrestamo = async () => {
  await cargarEquiposDisponibles()
  prestamo.value = {
    equipo_id: null,
    cantidad: 1,
    responsable: null,  // Objeto con id y tipo (trabajador/usuario)
    centro_costo_id: null,
    area_destino: '',
    fecha_prestamo: new Date(),
    fecha_devolucion_esperada: null,
    motivo_prestamo: '',
    observaciones_entrega: ''
  }
  dialogPrestamo.value = true
}

const guardarPrestamo = async () => {
  if (!prestamo.value.responsable) {
    toast.add({ severity: 'warn', summary: 'Atención', detail: 'Seleccione un trabajador o usuario responsable', life: 3000 })
    return
  }

  try {
    loading.value = true
    const data = {
      equipo_id: prestamo.value.equipo_id,
      cantidad: prestamo.value.cantidad,
      trabajador_id: prestamo.value.responsable.id,
      tipo_receptor: prestamo.value.responsable.tipo,  // 'trabajador' o 'usuario'
      centro_costo_id: prestamo.value.centro_costo_id,
      area_destino: prestamo.value.area_destino,
      fecha_prestamo: prestamo.value.fecha_prestamo?.toISOString().split('T')[0],
      fecha_devolucion_esperada: prestamo.value.fecha_devolucion_esperada?.toISOString().split('T')[0],
      motivo_prestamo: prestamo.value.motivo_prestamo,
      observaciones_entrega: prestamo.value.observaciones_entrega
    }

    await api.post('/prestamos', data)
    toast.add({ severity: 'success', summary: 'Éxito', detail: 'Préstamo registrado', life: 3000 })

    dialogPrestamo.value = false
    await cargarDatos()
  } catch (error) {
    const msg = error.response?.data?.message || 'Error creando préstamo'
    toast.add({ severity: 'error', summary: 'Error', detail: msg, life: 3000 })
  } finally {
    loading.value = false
  }
}

const abrirDevolucion = (item) => {
  devolucion.value = {
    prestamo_id: item.id,
    prestamo: item,
    condicion_devolucion: 'BUENO',
    fecha_devolucion: new Date(),
    observaciones_devolucion: ''
  }
  dialogDevolucion.value = true
}

const procesarDevolucion = async () => {
  try {
    loading.value = true
    const data = {
      condicion_devolucion: devolucion.value.condicion_devolucion,
      fecha_devolucion: devolucion.value.fecha_devolucion.toISOString().split('T')[0],
      observaciones_devolucion: devolucion.value.observaciones_devolucion
    }

    await api.post(`/prestamos/${devolucion.value.prestamo_id}/devolver`, data)
    toast.add({ severity: 'success', summary: 'Éxito', detail: 'Devolución procesada', life: 3000 })

    dialogDevolucion.value = false
    await cargarDatos()
  } catch (error) {
    const msg = error.response?.data?.message || 'Error procesando devolución'
    toast.add({ severity: 'error', summary: 'Error', detail: msg, life: 3000 })
  } finally {
    loading.value = false
  }
}

const abrirRenovacion = (item) => {
  renovacion.value = {
    prestamo_id: item.id,
    prestamo: item,
    nueva_fecha_devolucion: null
  }
  dialogRenovacion.value = true
}

const procesarRenovacion = async () => {
  try {
    loading.value = true
    const data = {
      nueva_fecha_devolucion: renovacion.value.nueva_fecha_devolucion.toISOString().split('T')[0]
    }

    await api.post(`/prestamos/${renovacion.value.prestamo_id}/renovar`, data)
    toast.add({ severity: 'success', summary: 'Éxito', detail: 'Préstamo renovado', life: 3000 })

    dialogRenovacion.value = false
    await cargarDatos()
  } catch (error) {
    const msg = error.response?.data?.message || 'Error renovando préstamo'
    toast.add({ severity: 'error', summary: 'Error', detail: msg, life: 3000 })
  } finally {
    loading.value = false
  }
}

const verHistorialEquipo = async (item) => {
  try {
    const response = await api.get(`/prestamos/equipos/${item.id}/historial`)
    historial.value = response.data.data || []
    dialogHistorial.value = true
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Error cargando historial', life: 3000 })
  }
}

// Helpers
const getEstadoSeverity = (estado) => {
  const map = {
    'ACTIVO': 'info',
    'VENCIDO': 'danger',
    'DEVUELTO': 'success',
    'RENOVADO': 'info',
    'PERDIDO': 'danger',
    'DANADO': 'warn'
  }
  return map[estado] || 'secondary'
}

const getEstadoEquipoSeverity = (estado) => {
  const map = {
    'DISPONIBLE': 'success',
    'PRESTADO': 'warn',
    'EN_MANTENIMIENTO': 'info',
    'DADO_DE_BAJA': 'danger'
  }
  return map[estado] || 'secondary'
}

const formatFecha = (fecha) => {
  if (!fecha) return '-'
  return new Date(fecha).toLocaleDateString('es-PE')
}

const formatCurrency = (value) => {
  if (!value) return '-'
  return new Intl.NumberFormat('es-PE', { style: 'currency', currency: 'PEN' }).format(value)
}

// Mounted
onMounted(() => {
  cargarDatos()
})
</script>

<template>
  <div class="p-4">
    <!-- Header -->
    <div class="flex justify-between items-center mb-4">
      <h1 class="text-2xl font-bold text-gray-800">Préstamos de Equipos</h1>
    </div>

    <!-- Estadísticas -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
      <Card class="text-center">
        <template #content>
          <div class="text-3xl font-bold text-blue-600">{{ estadisticas.prestamos_activos }}</div>
          <div class="text-sm text-gray-500">Activos</div>
        </template>
      </Card>
      <Card class="text-center">
        <template #content>
          <div class="text-3xl font-bold text-red-600">{{ estadisticas.prestamos_vencidos }}</div>
          <div class="text-sm text-gray-500">Vencidos</div>
        </template>
      </Card>
      <Card class="text-center">
        <template #content>
          <div class="text-3xl font-bold text-yellow-600">{{ estadisticas.por_vencer }}</div>
          <div class="text-sm text-gray-500">Por Vencer</div>
        </template>
      </Card>
      <Card class="text-center">
        <template #content>
          <div class="text-3xl font-bold text-green-600">{{ estadisticas.equipos_disponibles }}</div>
          <div class="text-sm text-gray-500">Disponibles</div>
        </template>
      </Card>
      <Card class="text-center">
        <template #content>
          <div class="text-3xl font-bold text-orange-600">{{ estadisticas.equipos_prestados }}</div>
          <div class="text-sm text-gray-500">Prestados</div>
        </template>
      </Card>
    </div>

    <!-- Tabs -->
    <TabView v-model:activeIndex="activeTab">
      <!-- Tab Préstamos -->
      <TabPanel header="Préstamos">
        <div class="flex flex-wrap gap-2 mb-4">
          <Button label="Nuevo Préstamo" icon="pi pi-plus" @click="nuevoPrestamo" />
          <Dropdown
            v-model="filtrosPrestamo.estado"
            :options="estadosPrestamo"
            optionLabel="label"
            optionValue="value"
            placeholder="Filtrar por estado"
            class="w-48"
            @change="cargarPrestamos"
          />
        </div>

        <DataTable
          :value="prestamos"
          :loading="loading"
          stripedRows
          paginator
          :rows="10"
          class="p-datatable-sm"
        >
          <Column field="numero" header="Número" sortable />
          <Column field="equipo.nombre" header="Equipo">
            <template #body="{ data }">
              <div>
                <span class="font-medium">{{ data.equipo?.nombre }}</span>
                <span v-if="data.cantidad > 1" class="text-gray-500"> (x{{ data.cantidad }})</span>
              </div>
              <div class="text-xs text-gray-500">{{ data.equipo?.codigo }}</div>
            </template>
          </Column>
          <Column field="trabajador.nombre" header="Responsable">
            <template #body="{ data }">
              <div>
                <span class="font-medium">{{ data.trabajador_usuario?.nombre || data.trabajador?.nombre }}</span>
                <Tag v-if="data.tipo_receptor === 'usuario'" value="Usuario" severity="info" class="ml-1 text-xs" />
              </div>
            </template>
          </Column>
          <Column field="fecha_prestamo" header="F. Préstamo">
            <template #body="{ data }">{{ formatFecha(data.fecha_prestamo) }}</template>
          </Column>
          <Column field="fecha_devolucion_esperada" header="F. Devolución">
            <template #body="{ data }">
              <span :class="{ 'text-red-600 font-bold': data.estado === 'VENCIDO' }">
                {{ formatFecha(data.fecha_devolucion_esperada) }}
              </span>
            </template>
          </Column>
          <Column field="estado" header="Estado">
            <template #body="{ data }">
              <Tag :value="data.estado" :severity="getEstadoSeverity(data.estado)" />
            </template>
          </Column>
          <Column header="Acciones" style="width: 200px">
            <template #body="{ data }">
              <div class="flex gap-1">
                <Button
                  v-if="data.estado === 'ACTIVO' || data.estado === 'VENCIDO'"
                  icon="pi pi-check"
                  severity="success"
                  size="small"
                  v-tooltip="'Devolver'"
                  @click="abrirDevolucion(data)"
                />
                <Button
                  v-if="data.estado === 'ACTIVO' || data.estado === 'VENCIDO'"
                  icon="pi pi-refresh"
                  severity="info"
                  size="small"
                  v-tooltip="'Renovar'"
                  @click="abrirRenovacion(data)"
                />
              </div>
            </template>
          </Column>
        </DataTable>
      </TabPanel>

      <!-- Tab Equipos -->
      <TabPanel header="Equipos Prestables">
        <div class="flex gap-2 mb-4">
          <Button label="Importar del Inventario" icon="pi pi-download" severity="success" @click="abrirImportarProductos" />
          <Button label="Nuevo Equipo Manual" icon="pi pi-plus" severity="secondary" @click="nuevoEquipo" />
        </div>

        <DataTable
          :value="equipos"
          :loading="loading"
          stripedRows
          paginator
          :rows="10"
          class="p-datatable-sm"
        >
          <Column field="codigo" header="Código" sortable />
          <Column field="nombre" header="Nombre" sortable />
          <Column field="marca" header="Marca" />
          <Column field="tipo_control" header="Control">
            <template #body="{ data }">
              <span v-if="data.tipo_control === 'INDIVIDUAL'">Individual</span>
              <span v-else>{{ data.cantidad_disponible }}/{{ data.cantidad_total }}</span>
            </template>
          </Column>
          <Column field="almacen.nombre" header="Almacén" />
          <Column field="estado" header="Estado">
            <template #body="{ data }">
              <Tag :value="data.estado" :severity="getEstadoEquipoSeverity(data.estado)" />
            </template>
          </Column>
          <Column header="Acciones" style="width: 180px">
            <template #body="{ data }">
              <div class="flex gap-1">
                <Button icon="pi pi-history" size="small" severity="info" v-tooltip="'Historial'" @click="verHistorialEquipo(data)" />
                <Button icon="pi pi-pencil" size="small" severity="secondary" @click="editarEquipo(data)" />
                <Button icon="pi pi-trash" size="small" severity="danger" @click="eliminarEquipo(data)" />
              </div>
            </template>
          </Column>
        </DataTable>
      </TabPanel>
    </TabView>

    <!-- Dialog Equipo -->
    <Dialog v-model:visible="dialogEquipo" :header="modoEdicionEquipo ? 'Editar Equipo' : 'Nuevo Equipo'" modal :style="{ width: '600px' }">
      <div class="grid grid-cols-2 gap-4">
        <div class="field">
          <label class="block text-sm font-medium mb-1">Código *</label>
          <InputText v-model="equipo.codigo" class="w-full" :disabled="modoEdicionEquipo" />
        </div>
        <div class="field">
          <label class="block text-sm font-medium mb-1">Nombre *</label>
          <InputText v-model="equipo.nombre" class="w-full" />
        </div>
        <div class="field col-span-2">
          <label class="block text-sm font-medium mb-1">Descripción</label>
          <Textarea v-model="equipo.descripcion" class="w-full" rows="2" />
        </div>
        <div class="field">
          <label class="block text-sm font-medium mb-1">Número de Serie</label>
          <InputText v-model="equipo.numero_serie" class="w-full" />
        </div>
        <div class="field">
          <label class="block text-sm font-medium mb-1">Marca</label>
          <InputText v-model="equipo.marca" class="w-full" />
        </div>
        <div class="field">
          <label class="block text-sm font-medium mb-1">Modelo</label>
          <InputText v-model="equipo.modelo" class="w-full" />
        </div>
        <div class="field">
          <label class="block text-sm font-medium mb-1">Tipo de Control *</label>
          <Dropdown v-model="equipo.tipo_control" :options="tiposControl" optionLabel="label" optionValue="value" class="w-full" />
        </div>
        <div v-if="equipo.tipo_control === 'CANTIDAD'" class="field">
          <label class="block text-sm font-medium mb-1">Cantidad Total</label>
          <InputNumber v-model="equipo.cantidad_total" class="w-full" :min="1" />
        </div>
        <div class="field">
          <label class="block text-sm font-medium mb-1">Almacén</label>
          <Dropdown v-model="equipo.almacen_id" :options="almacenes" optionLabel="nombre" optionValue="id" class="w-full" placeholder="Seleccionar" showClear />
        </div>
        <div class="field">
          <label class="block text-sm font-medium mb-1">Ubicación Física</label>
          <InputText v-model="equipo.ubicacion_fisica" class="w-full" />
        </div>
        <div class="field">
          <label class="block text-sm font-medium mb-1">Valor Referencial</label>
          <InputNumber v-model="equipo.valor_referencial" class="w-full" mode="currency" currency="PEN" />
        </div>
        <div class="field">
          <label class="block text-sm font-medium mb-1">Fecha Adquisición</label>
          <Calendar v-model="equipo.fecha_adquisicion" class="w-full" dateFormat="dd/mm/yy" />
        </div>
        <div class="field col-span-2">
          <label class="block text-sm font-medium mb-1">Vincular a Producto (Inventario)</label>
          <Dropdown v-model="equipo.producto_id" :options="productos" optionLabel="nombre" optionValue="id" class="w-full" filter placeholder="Opcional - sincroniza stock" showClear />
        </div>
      </div>
      <template #footer>
        <Button label="Cancelar" severity="secondary" @click="dialogEquipo = false" />
        <Button label="Guardar" icon="pi pi-save" @click="guardarEquipo" :loading="loading" />
      </template>
    </Dialog>

    <!-- Dialog Préstamo -->
    <Dialog v-model:visible="dialogPrestamo" header="Nuevo Préstamo" modal :style="{ width: '550px' }">
      <div class="grid gap-4">
        <div class="field">
          <label class="block text-sm font-medium mb-1">Equipo *</label>
          <Dropdown
            v-model="prestamo.equipo_id"
            :options="equiposDisponibles"
            optionLabel="nombre"
            optionValue="id"
            class="w-full"
            filter
            placeholder="Seleccionar equipo"
          >
            <template #option="{ option }">
              <div class="flex items-center gap-2">
                <span class="font-medium">{{ option.nombre }}</span>
                <span class="text-gray-500 text-sm">({{ option.codigo }})</span>
                <Tag v-if="option.fuente === 'producto_inventario'" value="Inventario" severity="secondary" class="text-xs" />
                <Tag v-if="option.tipo_control === 'CANTIDAD'" :value="`Disp: ${option.cantidad_disponible}`" severity="info" />
              </div>
            </template>
          </Dropdown>
          <p v-if="equiposDisponibles.length === 0" class="text-sm text-orange-600 mt-1">
            <i class="pi pi-info-circle mr-1"></i>
            No hay equipos disponibles. Importe productos desde el inventario en la pestaña "Equipos Prestables".
          </p>
        </div>
        <div class="field">
          <label class="block text-sm font-medium mb-1">Cantidad</label>
          <InputNumber v-model="prestamo.cantidad" class="w-full" :min="1" />
        </div>
        <div class="field">
          <label class="block text-sm font-medium mb-1">Trabajador / Usuario Responsable *</label>
          <Dropdown
            v-model="prestamo.responsable"
            :options="trabajadores"
            optionLabel="display_name"
            class="w-full"
            filter
            placeholder="Seleccionar trabajador o usuario"
          >
            <template #option="{ option }">
              <div class="flex items-center gap-2">
                <span class="font-medium">{{ option.nombre }}</span>
                <Tag v-if="option.tipo === 'usuario'" value="Usuario" severity="info" class="text-xs" />
                <span v-if="option.cargo" class="text-gray-500 text-sm">({{ option.cargo }})</span>
              </div>
              <div v-if="option.dni || option.centro_costo" class="text-xs text-gray-500">
                <span v-if="option.dni">DNI: {{ option.dni }}</span>
                <span v-if="option.centro_costo" class="ml-2">{{ option.centro_costo.nombre }}</span>
              </div>
            </template>
          </Dropdown>
        </div>
        <div class="field">
          <label class="block text-sm font-medium mb-1">Centro de Costo</label>
          <Dropdown v-model="prestamo.centro_costo_id" :options="centrosCosto" optionLabel="nombre" optionValue="id" class="w-full" placeholder="Opcional" showClear />
        </div>
        <div class="field">
          <label class="block text-sm font-medium mb-1">Área Destino</label>
          <InputText v-model="prestamo.area_destino" class="w-full" />
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div class="field">
            <label class="block text-sm font-medium mb-1">Fecha Préstamo</label>
            <Calendar v-model="prestamo.fecha_prestamo" class="w-full" dateFormat="dd/mm/yy" />
          </div>
          <div class="field">
            <label class="block text-sm font-medium mb-1">Fecha Devolución *</label>
            <Calendar v-model="prestamo.fecha_devolucion_esperada" class="w-full" dateFormat="dd/mm/yy" :minDate="new Date()" />
          </div>
        </div>
        <div class="field">
          <label class="block text-sm font-medium mb-1">Motivo del Préstamo</label>
          <Textarea v-model="prestamo.motivo_prestamo" class="w-full" rows="2" />
        </div>
        <div class="field">
          <label class="block text-sm font-medium mb-1">Observaciones</label>
          <Textarea v-model="prestamo.observaciones_entrega" class="w-full" rows="2" />
        </div>
      </div>
      <template #footer>
        <Button label="Cancelar" severity="secondary" @click="dialogPrestamo = false" />
        <Button label="Registrar Préstamo" icon="pi pi-save" @click="guardarPrestamo" :loading="loading" />
      </template>
    </Dialog>

    <!-- Dialog Devolución -->
    <Dialog v-model:visible="dialogDevolucion" header="Registrar Devolución" modal :style="{ width: '450px' }">
      <div v-if="devolucion.prestamo" class="mb-4 p-3 bg-gray-50 rounded">
        <p><strong>Equipo:</strong> {{ devolucion.prestamo.equipo?.nombre }}</p>
        <p><strong>Responsable:</strong> {{ devolucion.prestamo.trabajador?.nombre }}</p>
        <p><strong>F. Préstamo:</strong> {{ formatFecha(devolucion.prestamo.fecha_prestamo) }}</p>
      </div>
      <div class="grid gap-4">
        <div class="field">
          <label class="block text-sm font-medium mb-1">Condición del Equipo *</label>
          <Dropdown v-model="devolucion.condicion_devolucion" :options="condicionesDevolucion" optionLabel="label" optionValue="value" class="w-full" />
        </div>
        <div class="field">
          <label class="block text-sm font-medium mb-1">Fecha Devolución</label>
          <Calendar v-model="devolucion.fecha_devolucion" class="w-full" dateFormat="dd/mm/yy" />
        </div>
        <div class="field">
          <label class="block text-sm font-medium mb-1">Observaciones</label>
          <Textarea v-model="devolucion.observaciones_devolucion" class="w-full" rows="3" />
        </div>
      </div>
      <template #footer>
        <Button label="Cancelar" severity="secondary" @click="dialogDevolucion = false" />
        <Button label="Confirmar Devolución" icon="pi pi-check" severity="success" @click="procesarDevolucion" :loading="loading" />
      </template>
    </Dialog>

    <!-- Dialog Renovación -->
    <Dialog v-model:visible="dialogRenovacion" header="Renovar Préstamo" modal :style="{ width: '400px' }">
      <div v-if="renovacion.prestamo" class="mb-4 p-3 bg-gray-50 rounded">
        <p><strong>Equipo:</strong> {{ renovacion.prestamo.equipo?.nombre }}</p>
        <p><strong>Fecha actual:</strong> {{ formatFecha(renovacion.prestamo.fecha_devolucion_esperada) }}</p>
        <p v-if="renovacion.prestamo.numero_renovaciones > 0" class="text-orange-600">
          <i class="pi pi-info-circle"></i> Renovaciones previas: {{ renovacion.prestamo.numero_renovaciones }}
        </p>
      </div>
      <div class="field">
        <label class="block text-sm font-medium mb-1">Nueva Fecha de Devolución *</label>
        <Calendar v-model="renovacion.nueva_fecha_devolucion" class="w-full" dateFormat="dd/mm/yy" :minDate="new Date()" />
      </div>
      <template #footer>
        <Button label="Cancelar" severity="secondary" @click="dialogRenovacion = false" />
        <Button label="Renovar" icon="pi pi-refresh" severity="info" @click="procesarRenovacion" :loading="loading" />
      </template>
    </Dialog>

    <!-- Dialog Historial -->
    <Dialog v-model:visible="dialogHistorial" header="Historial de Préstamos" modal :style="{ width: '700px' }">
      <DataTable :value="historial" stripedRows class="p-datatable-sm">
        <Column field="numero" header="Préstamo" />
        <Column field="trabajador.nombre" header="Responsable" />
        <Column field="fecha_prestamo" header="Préstamo">
          <template #body="{ data }">{{ formatFecha(data.fecha_prestamo) }}</template>
        </Column>
        <Column field="fecha_devolucion_real" header="Devolución">
          <template #body="{ data }">{{ formatFecha(data.fecha_devolucion_real) || '-' }}</template>
        </Column>
        <Column field="estado" header="Estado">
          <template #body="{ data }">
            <Tag :value="data.estado" :severity="getEstadoSeverity(data.estado)" />
          </template>
        </Column>
        <Column field="condicion_devolucion" header="Condición" />
      </DataTable>
    </Dialog>

    <!-- Dialog Importar Productos -->
    <Dialog v-model:visible="dialogImportar" header="Importar Productos del Inventario" modal :style="{ width: '800px' }">
      <div class="mb-4">
        <p class="text-gray-600 text-sm mb-3">
          Seleccione los productos (herramientas, equipos) que desea habilitar para préstamos.
        </p>

        <!-- Mostrar familias prestables encontradas -->
        <div v-if="familiasPrestables.length > 0" class="mb-3 p-3 bg-blue-50 rounded border border-blue-200">
          <p class="text-sm font-medium text-blue-800 mb-2">
            <i class="pi pi-info-circle mr-1"></i> Familias disponibles para préstamo:
          </p>
          <div class="flex flex-wrap gap-2">
            <Tag v-for="fam in familiasPrestables" :key="fam.id" severity="info">
              {{ fam.nombre }} ({{ fam.productos_count }} productos)
            </Tag>
          </div>
        </div>
        <div v-else class="mb-3 p-3 bg-yellow-50 rounded border border-yellow-200">
          <p class="text-sm text-yellow-800">
            <i class="pi pi-exclamation-triangle mr-1"></i>
            No se encontraron familias de tipo herramientas, equipos o maquinaria.
            Asegúrese de tener familias registradas con esos nombres.
          </p>
        </div>

        <div class="flex gap-2 mb-3">
          <InputText v-model="buscarProducto" placeholder="Buscar producto..." class="flex-1" @keyup.enter="cargarProductosParaImportar" />
          <Button icon="pi pi-search" @click="cargarProductosParaImportar" />
        </div>
        <div class="mb-3">
          <label class="block text-sm font-medium mb-1">Tipo de Control para los productos seleccionados:</label>
          <Dropdown v-model="tipoControlImportar" :options="tiposControl" optionLabel="label" optionValue="value" class="w-64" />
          <p class="text-xs text-gray-500 mt-1">
            <strong>Individual:</strong> Cada unidad se controla por separado. <strong>Por Cantidad:</strong> Se controla el stock total.
          </p>
        </div>
      </div>

      <DataTable
        v-model:selection="productosSeleccionados"
        :value="productosParaImportar"
        dataKey="id"
        stripedRows
        class="p-datatable-sm"
        scrollable
        scrollHeight="300px"
      >
        <Column selectionMode="multiple" headerStyle="width: 3rem" />
        <Column field="codigo" header="Código" style="width: 100px" />
        <Column field="nombre" header="Nombre" />
        <Column field="familia.nombre" header="Familia/Categoría" />
        <Column header="Stock">
          <template #body="{ data }">
            {{ data.stock_almacenes?.reduce((sum, s) => sum + s.stock_actual, 0) || 0 }}
            {{ data.unidad_medida }}
          </template>
        </Column>
      </DataTable>

      <div v-if="productosParaImportar.length === 0" class="text-center py-8 text-gray-500">
        <i class="pi pi-inbox text-4xl mb-2"></i>
        <p>No hay productos disponibles para importar</p>
        <p class="text-sm">Todos los productos ya fueron importados o no existen productos de tipo herramientas/equipos.</p>
      </div>

      <template #footer>
        <div class="flex justify-between items-center w-full">
          <span class="text-sm text-gray-600">
            {{ productosSeleccionados.length }} producto(s) seleccionado(s)
          </span>
          <div class="flex gap-2">
            <Button label="Cancelar" severity="secondary" @click="dialogImportar = false" />
            <Button
              label="Importar Seleccionados"
              icon="pi pi-download"
              severity="success"
              @click="importarProductosSeleccionados"
              :loading="loading"
              :disabled="productosSeleccionados.length === 0"
            />
          </div>
        </div>
      </template>
    </Dialog>
  </div>
</template>

<style scoped>
.field {
  margin-bottom: 0.5rem;
}
</style>
