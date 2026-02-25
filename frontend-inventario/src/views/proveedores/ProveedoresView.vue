<script setup>
import { ref, onMounted, computed } from 'vue'
import { useToast } from 'primevue/usetoast'
import api from '@/services/api'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Dialog from 'primevue/dialog'
import Select from 'primevue/select'
import Tag from 'primevue/tag'
import ToggleSwitch from 'primevue/toggleswitch'
import Textarea from 'primevue/textarea'
import Rating from 'primevue/rating'
import ProgressSpinner from 'primevue/progressspinner'

const toast = useToast()

// Estado
const proveedores = ref([])
const loading = ref(false)
const totalRecords = ref(0)
const lazyParams = ref({
  first: 0,
  rows: 15,
  sortField: 'razon_social',
  sortOrder: 1
})

// Filtros
const filtros = ref({
  search: '',
  tipo: null,
  activo: null
})

// Opciones
const tiposProveedor = [
  { label: 'Bienes', value: 'BIENES' },
  { label: 'Servicios', value: 'SERVICIOS' },
  { label: 'Bienes y Servicios', value: 'AMBOS' }
]

const estadosActivo = [
  { label: 'Activos', value: true },
  { label: 'Inactivos', value: false }
]

// Diálogos
const dialogVisible = ref(false)
const deleteDialogVisible = ref(false)
const viewDialogVisible = ref(false)
const calificarDialogVisible = ref(false)
const isEditing = ref(false)
const selectedProveedor = ref(null)
const saving = ref(false)
const validatingRuc = ref(false)

// Formulario
const formData = ref({
  ruc: '',
  razon_social: '',
  nombre_comercial: '',
  direccion: '',
  telefono: '',
  email: '',
  contacto_nombre: '',
  contacto_telefono: '',
  contacto_email: '',
  tipo: 'BIENES',
  activo: true
})

// Calificación
const nuevaCalificacion = ref(0)

// Estadísticas
const estadisticas = computed(() => {
  return {
    total: totalRecords.value,
    activos: proveedores.value.filter(p => p.activo).length,
    promedioCalificacion: calcularPromedioCalificacion()
  }
})

const calcularPromedioCalificacion = () => {
  if (proveedores.value.length === 0) return 0
  const conCalificacion = proveedores.value.filter(p => p.calificacion > 0)
  if (conCalificacion.length === 0) return 0
  const suma = conCalificacion.reduce((acc, p) => acc + parseFloat(p.calificacion || 0), 0)
  return (suma / conCalificacion.length).toFixed(1)
}

// Cargar proveedores
const cargarProveedores = async () => {
  loading.value = true
  try {
    const params = {
      page: Math.floor(lazyParams.value.first / lazyParams.value.rows) + 1,
      per_page: lazyParams.value.rows,
      sort_field: lazyParams.value.sortField,
      sort_order: lazyParams.value.sortOrder === 1 ? 'asc' : 'desc'
    }

    if (filtros.value.search) params.search = filtros.value.search
    if (filtros.value.tipo) params.tipo = filtros.value.tipo
    if (filtros.value.activo !== null) params.activo = filtros.value.activo

    const response = await api.get('/proveedores', { params })
    proveedores.value = response.data.data
    totalRecords.value = response.data.meta?.total || response.data.total || proveedores.value.length
  } catch (error) {
    console.error('Error al cargar proveedores:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'No se pudieron cargar los proveedores',
      life: 3000
    })
  } finally {
    loading.value = false
  }
}

// Validar RUC con SUNAT
const validarRuc = async () => {
  if (formData.value.ruc.length !== 11) {
    toast.add({
      severity: 'warn',
      summary: 'Advertencia',
      detail: 'El RUC debe tener 11 dígitos',
      life: 3000
    })
    return
  }

  validatingRuc.value = true
  try {
    const response = await api.get(`/proveedores/validar-ruc/${formData.value.ruc}`)
    const datos = response.data.data

    formData.value.razon_social = datos.razon_social || ''
    formData.value.nombre_comercial = datos.nombre_comercial || ''
    formData.value.direccion = datos.direccion || ''

    toast.add({
      severity: 'success',
      summary: 'RUC Validado',
      detail: `${datos.razon_social} - Estado: ${datos.estado}`,
      life: 4000
    })
  } catch (error) {
    console.error('Error validando RUC:', error)
    const mensaje = error.response?.data?.message || 'No se pudo validar el RUC. Ingrese los datos manualmente.'
    toast.add({
      severity: 'warn',
      summary: 'Validación RUC',
      detail: mensaje,
      life: 4000
    })
  } finally {
    validatingRuc.value = false
  }
}

// Abrir diálogo nuevo
const openNew = () => {
  formData.value = {
    ruc: '',
    razon_social: '',
    nombre_comercial: '',
    direccion: '',
    telefono: '',
    email: '',
    contacto_nombre: '',
    contacto_telefono: '',
    contacto_email: '',
    tipo: 'BIENES',
    activo: true
  }
  isEditing.value = false
  dialogVisible.value = true
}

// Editar proveedor
const editProveedor = (proveedor) => {
  formData.value = {
    ruc: proveedor.ruc,
    razon_social: proveedor.razon_social,
    nombre_comercial: proveedor.nombre_comercial || '',
    direccion: proveedor.direccion || '',
    telefono: proveedor.telefono || '',
    email: proveedor.email || '',
    contacto_nombre: proveedor.contacto_nombre || '',
    contacto_telefono: proveedor.contacto_telefono || '',
    contacto_email: proveedor.contacto_email || '',
    tipo: proveedor.tipo,
    activo: proveedor.activo
  }
  selectedProveedor.value = proveedor
  isEditing.value = true
  dialogVisible.value = true
}

// Ver proveedor
const viewProveedor = (proveedor) => {
  selectedProveedor.value = proveedor
  viewDialogVisible.value = true
}

// Confirmar eliminación
const confirmDelete = (proveedor) => {
  selectedProveedor.value = proveedor
  deleteDialogVisible.value = true
}

// Guardar proveedor
const saveProveedor = async () => {
  // Validaciones
  if (!formData.value.ruc || formData.value.ruc.length !== 11) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'El RUC debe tener 11 dígitos', life: 3000 })
    return
  }
  if (!formData.value.razon_social) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'La razón social es requerida', life: 3000 })
    return
  }
  if (!formData.value.tipo) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'El tipo de proveedor es requerido', life: 3000 })
    return
  }

  saving.value = true
  try {
    if (isEditing.value) {
      await api.put(`/proveedores/${selectedProveedor.value.id}`, formData.value)
      toast.add({
        severity: 'success',
        summary: 'Éxito',
        detail: 'Proveedor actualizado correctamente',
        life: 3000
      })
    } else {
      await api.post('/proveedores', formData.value)
      toast.add({
        severity: 'success',
        summary: 'Éxito',
        detail: 'Proveedor creado correctamente',
        life: 3000
      })
    }
    dialogVisible.value = false
    cargarProveedores()
  } catch (error) {
    console.error('Error guardando proveedor:', error)
    const mensaje = error.response?.data?.message || 'Error al guardar el proveedor'
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: mensaje,
      life: 4000
    })
  } finally {
    saving.value = false
  }
}

// Eliminar proveedor
const deleteProveedor = async () => {
  try {
    await api.delete(`/proveedores/${selectedProveedor.value.id}`)
    toast.add({
      severity: 'success',
      summary: 'Éxito',
      detail: 'Proveedor eliminado correctamente',
      life: 3000
    })
    deleteDialogVisible.value = false
    cargarProveedores()
  } catch (error) {
    console.error('Error eliminando proveedor:', error)
    const mensaje = error.response?.data?.message || 'No se pudo eliminar el proveedor'
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: mensaje,
      life: 4000
    })
  }
}

// Abrir diálogo de calificación
const openCalificar = (proveedor) => {
  selectedProveedor.value = proveedor
  nuevaCalificacion.value = proveedor.calificacion || 3
  calificarDialogVisible.value = true
}

// Guardar calificación
const saveCalificacion = async () => {
  if (nuevaCalificacion.value < 1 || nuevaCalificacion.value > 5) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'La calificación debe ser entre 1 y 5', life: 3000 })
    return
  }

  saving.value = true
  try {
    await api.post(`/proveedores/${selectedProveedor.value.id}/calificar`, {
      calificacion: nuevaCalificacion.value
    })
    toast.add({
      severity: 'success',
      summary: 'Éxito',
      detail: 'Calificación registrada correctamente',
      life: 3000
    })
    calificarDialogVisible.value = false
    cargarProveedores()
  } catch (error) {
    console.error('Error calificando proveedor:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'No se pudo registrar la calificación',
      life: 3000
    })
  } finally {
    saving.value = false
  }
}

// Evento de paginación/ordenamiento
const onPage = (event) => {
  lazyParams.value = event
  cargarProveedores()
}

const onSort = (event) => {
  lazyParams.value = event
  cargarProveedores()
}

// Aplicar filtros
const aplicarFiltros = () => {
  lazyParams.value.first = 0
  cargarProveedores()
}

// Limpiar filtros
const limpiarFiltros = () => {
  filtros.value = {
    search: '',
    tipo: null,
    activo: null
  }
  lazyParams.value.first = 0
  cargarProveedores()
}

// Helpers
const getTipoLabel = (tipo) => {
  const opciones = {
    'BIENES': 'Bienes',
    'SERVICIOS': 'Servicios',
    'AMBOS': 'Ambos'
  }
  return opciones[tipo] || tipo
}

const getTipoSeverity = (tipo) => {
  const severities = {
    'BIENES': 'info',
    'SERVICIOS': 'warning',
    'AMBOS': 'success'
  }
  return severities[tipo] || 'secondary'
}

// Inicialización
onMounted(() => {
  cargarProveedores()
})
</script>

<template>
  <div class="space-y-4">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold text-gray-800">Proveedores</h2>
        <p class="text-gray-500 text-sm">Gestión de proveedores y contactos</p>
      </div>
      <div class="flex gap-2">
        <Button
          label="Nuevo Proveedor"
          icon="pi pi-plus"
          @click="openNew"
          class="!bg-amber-600 !border-amber-600 hover:!bg-amber-700"
        />
      </div>
    </div>

    <!-- Cards de resumen -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <div class="bg-white rounded-lg p-4 shadow-sm border-l-4 border-blue-500">
        <p class="text-sm text-gray-500">Total Proveedores</p>
        <p class="text-2xl font-bold text-blue-600">{{ estadisticas.total }}</p>
      </div>
      <div class="bg-white rounded-lg p-4 shadow-sm border-l-4 border-green-500">
        <p class="text-sm text-gray-500">Proveedores Activos</p>
        <p class="text-2xl font-bold text-green-600">{{ estadisticas.activos }}</p>
      </div>
      <div class="bg-white rounded-lg p-4 shadow-sm border-l-4 border-amber-500">
        <p class="text-sm text-gray-500">Calificación Promedio</p>
        <p class="text-2xl font-bold text-amber-600">
          {{ estadisticas.promedioCalificacion }}
          <span class="text-sm font-normal text-gray-400">/ 5</span>
        </p>
      </div>
      <div class="bg-white rounded-lg p-4 shadow-sm border-l-4 border-purple-500">
        <p class="text-sm text-gray-500">Inactivos</p>
        <p class="text-2xl font-bold text-purple-600">{{ estadisticas.total - estadisticas.activos }}</p>
      </div>
    </div>

    <!-- Filtros y Tabla -->
    <div class="bg-white rounded-xl shadow-sm p-4">
      <!-- Filtros -->
      <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-4">
        <div class="md:col-span-2">
          <div class="relative">
            <i class="pi pi-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
            <InputText
              v-model="filtros.search"
              placeholder="Buscar por RUC o razón social..."
              class="w-full pl-10"
              @keyup.enter="aplicarFiltros"
            />
          </div>
        </div>
        <Select
          v-model="filtros.tipo"
          :options="tiposProveedor"
          optionLabel="label"
          optionValue="value"
          placeholder="Tipo"
          class="w-full"
          showClear
        >
          <template #value="slotProps">
            <span v-if="slotProps.value">{{ tiposProveedor.find(t => t.value === slotProps.value)?.label }}</span>
            <span v-else class="text-gray-400">{{ slotProps.placeholder }}</span>
          </template>
        </Select>
        <Select
          v-model="filtros.activo"
          :options="estadosActivo"
          optionLabel="label"
          optionValue="value"
          placeholder="Estado"
          class="w-full"
          showClear
        >
          <template #value="slotProps">
            <span v-if="slotProps.value !== null && slotProps.value !== undefined">{{ estadosActivo.find(e => e.value === slotProps.value)?.label }}</span>
            <span v-else class="text-gray-400">{{ slotProps.placeholder }}</span>
          </template>
        </Select>
        <div class="flex gap-2">
          <Button
            icon="pi pi-search"
            label="Buscar"
            @click="aplicarFiltros"
            class="!bg-amber-600 !border-amber-600"
          />
          <Button
            icon="pi pi-times"
            severity="secondary"
            outlined
            @click="limpiarFiltros"
            v-tooltip.top="'Limpiar filtros'"
          />
        </div>
      </div>

      <!-- Tabla -->
      <DataTable
        :value="proveedores"
        :loading="loading"
        :lazy="true"
        :paginator="true"
        :rows="lazyParams.rows"
        :totalRecords="totalRecords"
        :first="lazyParams.first"
        :sortField="lazyParams.sortField"
        :sortOrder="lazyParams.sortOrder"
        @page="onPage"
        @sort="onSort"
        stripedRows
        :rowsPerPageOptions="[10, 15, 25, 50]"
        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
        currentPageReportTemplate="Mostrando {first} a {last} de {totalRecords} proveedores"
        responsiveLayout="scroll"
      >
        <template #empty>
          <div class="text-center py-8 text-gray-500">
            <i class="pi pi-inbox text-4xl mb-2"></i>
            <p>No se encontraron proveedores</p>
          </div>
        </template>

        <Column field="ruc" header="RUC" sortable style="width: 130px">
          <template #body="{ data }">
            <span class="font-mono text-sm font-medium">{{ data.ruc }}</span>
          </template>
        </Column>

        <Column field="razon_social" header="Razón Social" sortable>
          <template #body="{ data }">
            <div>
              <p class="font-medium text-gray-800">{{ data.razon_social }}</p>
              <p v-if="data.nombre_comercial" class="text-xs text-gray-500">{{ data.nombre_comercial }}</p>
            </div>
          </template>
        </Column>

        <Column field="tipo" header="Tipo" sortable style="width: 120px">
          <template #body="{ data }">
            <Tag :value="getTipoLabel(data.tipo)" :severity="getTipoSeverity(data.tipo)" />
          </template>
        </Column>

        <Column field="contacto_nombre" header="Contacto">
          <template #body="{ data }">
            <div v-if="data.contacto_nombre">
              <p class="text-gray-700">{{ data.contacto_nombre }}</p>
              <p class="text-xs text-gray-500">{{ data.contacto_telefono || data.telefono }}</p>
            </div>
            <span v-else class="text-gray-400 text-sm">Sin contacto</span>
          </template>
        </Column>

        <Column field="email" header="Email">
          <template #body="{ data }">
            <a v-if="data.email" :href="'mailto:' + data.email" class="text-blue-600 hover:underline text-sm">
              {{ data.email }}
            </a>
            <span v-else class="text-gray-400 text-sm">-</span>
          </template>
        </Column>

        <Column field="calificacion" header="Calificación" style="width: 150px">
          <template #body="{ data }">
            <div class="flex items-center gap-2">
              <Rating :modelValue="Math.round(data.calificacion || 0)" :readonly="true" :cancel="false" />
              <span class="text-xs text-gray-500">({{ data.total_calificaciones || 0 }})</span>
            </div>
          </template>
        </Column>

        <Column field="activo" header="Estado" style="width: 90px">
          <template #body="{ data }">
            <Tag
              :value="data.activo ? 'Activo' : 'Inactivo'"
              :severity="data.activo ? 'success' : 'danger'"
            />
          </template>
        </Column>

        <Column header="Acciones" style="width: 140px" frozen alignFrozen="right">
          <template #body="{ data }">
            <div class="flex gap-1">
              <Button
                icon="pi pi-eye"
                severity="info"
                text
                rounded
                size="small"
                @click="viewProveedor(data)"
                v-tooltip.top="'Ver detalle'"
              />
              <Button
                icon="pi pi-star"
                severity="warning"
                text
                rounded
                size="small"
                @click="openCalificar(data)"
                v-tooltip.top="'Calificar'"
              />
              <Button
                icon="pi pi-pencil"
                severity="secondary"
                text
                rounded
                size="small"
                @click="editProveedor(data)"
                v-tooltip.top="'Editar'"
              />
              <Button
                icon="pi pi-trash"
                severity="danger"
                text
                rounded
                size="small"
                @click="confirmDelete(data)"
                v-tooltip.top="'Eliminar'"
              />
            </div>
          </template>
        </Column>
      </DataTable>
    </div>

    <!-- Dialog Crear/Editar -->
    <Dialog
      v-model:visible="dialogVisible"
      :header="isEditing ? 'Editar Proveedor' : 'Nuevo Proveedor'"
      modal
      :style="{ width: '650px' }"
      :closable="!saving"
    >
      <div class="space-y-4">
        <!-- RUC con validación SUNAT -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">RUC *</label>
          <div class="flex gap-2">
            <InputText
              v-model="formData.ruc"
              class="flex-1"
              placeholder="20123456789"
              maxlength="11"
              :disabled="isEditing || validatingRuc"
            />
            <Button
              v-if="!isEditing"
              label="Validar SUNAT"
              icon="pi pi-search"
              :loading="validatingRuc"
              @click="validarRuc"
              severity="secondary"
              :disabled="formData.ruc.length !== 11 || validatingRuc"
            />
          </div>
          <small class="text-gray-500">Ingrese el RUC y presione validar para obtener datos de SUNAT</small>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div class="col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">Razón Social *</label>
            <InputText
              v-model="formData.razon_social"
              class="w-full"
              placeholder="Razón social del proveedor"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Nombre Comercial</label>
            <InputText
              v-model="formData.nombre_comercial"
              class="w-full"
              placeholder="Nombre comercial (opcional)"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Proveedor *</label>
            <Select
              v-model="formData.tipo"
              :options="tiposProveedor"
              optionLabel="label"
              optionValue="value"
              placeholder="Seleccione tipo"
              class="w-full"
            >
              <template #value="slotProps">
                <span v-if="slotProps.value">{{ tiposProveedor.find(t => t.value === slotProps.value)?.label }}</span>
                <span v-else class="text-gray-400">{{ slotProps.placeholder }}</span>
              </template>
            </Select>
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Dirección</label>
          <Textarea
            v-model="formData.direccion"
            rows="2"
            class="w-full"
            placeholder="Dirección completa"
          />
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Teléfono</label>
            <InputText v-model="formData.telefono" class="w-full" placeholder="01-1234567" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
            <InputText
              v-model="formData.email"
              type="email"
              class="w-full"
              placeholder="contacto@empresa.com"
            />
          </div>
        </div>

        <div class="border-t pt-4">
          <p class="text-sm font-medium text-gray-700 mb-3">
            <i class="pi pi-user mr-2"></i>Persona de Contacto
          </p>
          <div class="grid grid-cols-3 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Nombre</label>
              <InputText
                v-model="formData.contacto_nombre"
                class="w-full"
                placeholder="Nombre del contacto"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Teléfono</label>
              <InputText
                v-model="formData.contacto_telefono"
                class="w-full"
                placeholder="999888777"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
              <InputText
                v-model="formData.contacto_email"
                type="email"
                class="w-full"
                placeholder="contacto@email.com"
              />
            </div>
          </div>
        </div>

        <div class="flex items-center gap-2 pt-2">
          <ToggleSwitch v-model="formData.activo" />
          <label class="text-sm text-gray-700">Proveedor activo</label>
        </div>
      </div>

      <template #footer>
        <Button label="Cancelar" severity="secondary" @click="dialogVisible = false" :disabled="saving" />
        <Button
          :label="isEditing ? 'Actualizar' : 'Guardar'"
          :loading="saving"
          @click="saveProveedor"
          class="!bg-amber-600 !border-amber-600"
        />
      </template>
    </Dialog>

    <!-- Dialog Ver Detalle -->
    <Dialog
      v-model:visible="viewDialogVisible"
      header="Detalle del Proveedor"
      modal
      :style="{ width: '550px' }"
    >
      <div v-if="selectedProveedor" class="space-y-4">
        <div class="flex items-start justify-between">
          <div>
            <p class="text-lg font-bold text-gray-800">{{ selectedProveedor.razon_social }}</p>
            <p v-if="selectedProveedor.nombre_comercial" class="text-gray-500">
              {{ selectedProveedor.nombre_comercial }}
            </p>
          </div>
          <Tag
            :value="selectedProveedor.activo ? 'Activo' : 'Inactivo'"
            :severity="selectedProveedor.activo ? 'success' : 'danger'"
          />
        </div>

        <div class="grid grid-cols-2 gap-4 pt-4 border-t">
          <div>
            <p class="text-xs text-gray-500 uppercase">RUC</p>
            <p class="font-mono font-medium">{{ selectedProveedor.ruc }}</p>
          </div>
          <div>
            <p class="text-xs text-gray-500 uppercase">Tipo</p>
            <Tag :value="getTipoLabel(selectedProveedor.tipo)" :severity="getTipoSeverity(selectedProveedor.tipo)" />
          </div>
          <div class="col-span-2">
            <p class="text-xs text-gray-500 uppercase">Dirección</p>
            <p>{{ selectedProveedor.direccion || 'No registrada' }}</p>
          </div>
          <div>
            <p class="text-xs text-gray-500 uppercase">Teléfono</p>
            <p>{{ selectedProveedor.telefono || 'No registrado' }}</p>
          </div>
          <div>
            <p class="text-xs text-gray-500 uppercase">Email</p>
            <p>{{ selectedProveedor.email || 'No registrado' }}</p>
          </div>
        </div>

        <div v-if="selectedProveedor.contacto_nombre" class="pt-4 border-t">
          <p class="text-sm font-medium text-gray-700 mb-2">
            <i class="pi pi-user mr-2"></i>Persona de Contacto
          </p>
          <div class="grid grid-cols-3 gap-4">
            <div>
              <p class="text-xs text-gray-500 uppercase">Nombre</p>
              <p>{{ selectedProveedor.contacto_nombre }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-500 uppercase">Teléfono</p>
              <p>{{ selectedProveedor.contacto_telefono || '-' }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-500 uppercase">Email</p>
              <p>{{ selectedProveedor.contacto_email || '-' }}</p>
            </div>
          </div>
        </div>

        <div class="pt-4 border-t">
          <p class="text-sm font-medium text-gray-700 mb-2">
            <i class="pi pi-star mr-2"></i>Calificación
          </p>
          <div class="flex items-center gap-3">
            <Rating :modelValue="Math.round(selectedProveedor.calificacion || 0)" :readonly="true" :cancel="false" />
            <span class="text-gray-600">
              {{ selectedProveedor.calificacion || 0 }} / 5
              ({{ selectedProveedor.total_calificaciones || 0 }} calificaciones)
            </span>
          </div>
        </div>
      </div>

      <template #footer>
        <Button label="Cerrar" severity="secondary" @click="viewDialogVisible = false" />
        <Button
          label="Editar"
          icon="pi pi-pencil"
          @click="viewDialogVisible = false; editProveedor(selectedProveedor)"
          class="!bg-amber-600 !border-amber-600"
        />
      </template>
    </Dialog>

    <!-- Dialog Calificar -->
    <Dialog
      v-model:visible="calificarDialogVisible"
      header="Calificar Proveedor"
      modal
      :style="{ width: '400px' }"
    >
      <div v-if="selectedProveedor" class="space-y-4">
        <p class="text-gray-600">
          Calificando a: <strong>{{ selectedProveedor.razon_social }}</strong>
        </p>

        <div class="text-center py-4">
          <p class="text-sm text-gray-500 mb-2">Seleccione una calificación</p>
          <Rating v-model="nuevaCalificacion" :cancel="false" />
        </div>

        <div v-if="selectedProveedor.calificacion" class="bg-gray-50 rounded-lg p-3 text-center">
          <p class="text-xs text-gray-500">Calificación actual</p>
          <p class="text-lg font-bold text-amber-600">
            {{ selectedProveedor.calificacion }} / 5
          </p>
          <p class="text-xs text-gray-400">
            ({{ selectedProveedor.total_calificaciones }} calificaciones)
          </p>
        </div>
      </div>

      <template #footer>
        <Button label="Cancelar" severity="secondary" @click="calificarDialogVisible = false" :disabled="saving" />
        <Button
          label="Guardar Calificación"
          icon="pi pi-check"
          :loading="saving"
          @click="saveCalificacion"
          class="!bg-amber-600 !border-amber-600"
        />
      </template>
    </Dialog>

    <!-- Dialog Confirmar Eliminación -->
    <Dialog
      v-model:visible="deleteDialogVisible"
      header="Confirmar Eliminación"
      modal
      :style="{ width: '400px' }"
    >
      <div class="flex items-center gap-4">
        <i class="pi pi-exclamation-triangle text-4xl text-amber-500"></i>
        <div>
          <p>¿Está seguro que desea eliminar al proveedor?</p>
          <p class="font-bold mt-1">{{ selectedProveedor?.razon_social }}</p>
          <p class="text-sm text-gray-500">RUC: {{ selectedProveedor?.ruc }}</p>
        </div>
      </div>
      <template #footer>
        <Button label="Cancelar" severity="secondary" @click="deleteDialogVisible = false" />
        <Button label="Eliminar" severity="danger" @click="deleteProveedor" />
      </template>
    </Dialog>
  </div>
</template>
