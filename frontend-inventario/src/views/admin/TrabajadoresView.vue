<script setup>
import { ref, computed, onMounted } from 'vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Dialog from 'primevue/dialog'
import Select from 'primevue/select'
import Tag from 'primevue/tag'
import Textarea from 'primevue/textarea'
import DatePicker from 'primevue/datepicker'
import Toast from 'primevue/toast'
import ConfirmDialog from 'primevue/confirmdialog'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import { useAuthStore } from '@/stores/auth'
import api from '@/services/api'

const toast = useToast()
const confirm = useConfirm()
const authStore = useAuthStore()

// Estado
const trabajadores = ref([])
const centrosCosto = ref([])
const loading = ref(false)
const dialogVisible = ref(false)
const searchQuery = ref('')
const selectedCentroCosto = ref(null)
const selectedEstado = ref(null)
const isEditing = ref(false)

// Estado kardex PDF
const kardexDialogVisible = ref(false)
const kardexTrabajador = ref(null)
const kardexFile = ref(null)
const kardexFileInput = ref(null)
const uploadingKardex = ref(false)

// Formulario
const formData = ref({
  id: null,
  nombre: '',
  dni: '',
  cargo: '',
  telefono: '',
  centro_costo_id: null,
  fecha_ingreso: null,
  observaciones: '',
  activo: true
})

// Opciones de estado
const estadoOptions = [
  { label: 'Activos', value: true },
  { label: 'Inactivos', value: false }
]

// Cargar datos
const loadTrabajadores = async () => {
  loading.value = true
  try {
    const params = {}
    if (selectedCentroCosto.value) params.centro_costo_id = selectedCentroCosto.value
    if (selectedEstado.value !== null) params.activo = selectedEstado.value

    const response = await api.get('/administracion/trabajadores', { params })
    if (response.data.success) {
      trabajadores.value = response.data.data.data || response.data.data || []
    }
  } catch (err) {
    console.error('Error al cargar trabajadores:', err)
    toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudieron cargar los trabajadores', life: 5000 })
  } finally {
    loading.value = false
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

onMounted(() => {
  loadTrabajadores()
  loadCentrosCosto()
})

// Filtrar trabajadores
const filteredTrabajadores = computed(() => {
  if (!searchQuery.value) return trabajadores.value
  const query = searchQuery.value.toLowerCase()
  return trabajadores.value.filter(t =>
    t.nombre?.toLowerCase().includes(query) ||
    t.dni?.toLowerCase().includes(query) ||
    t.cargo?.toLowerCase().includes(query)
  )
})

// Métodos CRUD
const openNew = () => {
  formData.value = {
    id: null,
    nombre: '',
    dni: '',
    cargo: '',
    telefono: '',
    centro_costo_id: authStore.user?.centro_costo_id || null,
    fecha_ingreso: new Date(),
    observaciones: '',
    activo: true
  }
  isEditing.value = false
  dialogVisible.value = true
}

const editTrabajador = (trabajador) => {
  formData.value = {
    id: trabajador.id,
    nombre: trabajador.nombre,
    dni: trabajador.dni || '',
    cargo: trabajador.cargo || '',
    telefono: trabajador.telefono || '',
    centro_costo_id: trabajador.centro_costo_id,
    fecha_ingreso: trabajador.fecha_ingreso ? new Date(trabajador.fecha_ingreso) : null,
    observaciones: trabajador.observaciones || '',
    activo: trabajador.activo
  }
  isEditing.value = true
  dialogVisible.value = true
}

const saveTrabajador = async () => {
  if (!formData.value.nombre.trim()) {
    toast.add({ severity: 'warn', summary: 'Atención', detail: 'El nombre es requerido', life: 3000 })
    return
  }

  loading.value = true
  try {
    const dataToSend = {
      ...formData.value,
      fecha_ingreso: formData.value.fecha_ingreso?.toISOString().split('T')[0]
    }

    if (isEditing.value) {
      await api.put(`/administracion/trabajadores/${formData.value.id}`, dataToSend)
      toast.add({ severity: 'success', summary: 'Éxito', detail: 'Trabajador actualizado', life: 3000 })
    } else {
      await api.post('/administracion/trabajadores', dataToSend)
      toast.add({ severity: 'success', summary: 'Éxito', detail: 'Trabajador registrado', life: 3000 })
    }
    dialogVisible.value = false
    await loadTrabajadores()
  } catch (err) {
    console.error('Error al guardar:', err)
    const message = err.response?.data?.message || 'Error al guardar el trabajador'
    toast.add({ severity: 'error', summary: 'Error', detail: message, life: 5000 })
  } finally {
    loading.value = false
  }
}

const confirmDelete = (trabajador) => {
  confirm.require({
    message: `¿Está seguro de eliminar a ${trabajador.nombre}?`,
    header: 'Confirmar eliminación',
    icon: 'pi pi-exclamation-triangle',
    acceptClass: 'p-button-danger',
    accept: () => deleteTrabajador(trabajador)
  })
}

const deleteTrabajador = async (trabajador) => {
  try {
    await api.delete(`/administracion/trabajadores/${trabajador.id}`)
    toast.add({ severity: 'success', summary: 'Éxito', detail: 'Trabajador eliminado', life: 3000 })
    await loadTrabajadores()
  } catch (err) {
    console.error('Error al eliminar:', err)
    toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudo eliminar el trabajador', life: 5000 })
  }
}

// ==================== KARDEX PDF ====================

const openKardexDialog = (trabajador) => {
  kardexTrabajador.value = trabajador
  kardexFile.value = null
  kardexDialogVisible.value = true
}

const onKardexFileChange = (event) => {
  const file = event.target.files[0]
  if (!file) return

  if (file.type !== 'application/pdf') {
    toast.add({ severity: 'warn', summary: 'Formato inválido', detail: 'Solo se permiten archivos PDF', life: 4000 })
    event.target.value = ''
    return
  }

  if (file.size > 20 * 1024 * 1024) {
    toast.add({ severity: 'warn', summary: 'Archivo muy grande', detail: 'El PDF no debe superar 20 MB', life: 4000 })
    event.target.value = ''
    return
  }

  kardexFile.value = file
}

const subirKardexPdf = async () => {
  if (!kardexFile.value) {
    toast.add({ severity: 'warn', summary: 'Sin archivo', detail: 'Seleccione un archivo PDF', life: 3000 })
    return
  }

  uploadingKardex.value = true
  try {
    const formDataUpload = new FormData()
    formDataUpload.append('kardex_pdf', kardexFile.value)

    const response = await api.post(
      `/administracion/trabajadores/${kardexTrabajador.value.id}/kardex-pdf`,
      formDataUpload,
      { headers: { 'Content-Type': 'multipart/form-data' } }
    )

    if (response.data.success) {
      toast.add({ severity: 'success', summary: 'Subido', detail: 'Kardex PDF subido exitosamente', life: 3000 })
      // Actualizar el trabajador en la lista
      const idx = trabajadores.value.findIndex(t => t.id === kardexTrabajador.value.id)
      if (idx !== -1) {
        trabajadores.value[idx] = { ...trabajadores.value[idx], ...response.data.data, tiene_kardex: true }
        kardexTrabajador.value = trabajadores.value[idx]
      }
      kardexFile.value = null
      if (kardexFileInput.value) kardexFileInput.value.value = ''
    }
  } catch (err) {
    const message = err.response?.data?.message || 'Error al subir el PDF'
    toast.add({ severity: 'error', summary: 'Error', detail: message, life: 5000 })
  } finally {
    uploadingKardex.value = false
  }
}

const verKardexPdf = async (trabajador) => {
  try {
    const response = await api.get(
      `/administracion/trabajadores/${trabajador.id}/kardex-pdf`,
      { responseType: 'blob' }
    )
    const blob = new Blob([response.data], { type: 'application/pdf' })
    const url = URL.createObjectURL(blob)
    window.open(url, '_blank')
    setTimeout(() => URL.revokeObjectURL(url), 60000)
  } catch (err) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudo abrir el PDF', life: 5000 })
  }
}

const confirmarEliminarKardex = (trabajador) => {
  confirm.require({
    message: `¿Eliminar el kardex PDF de ${trabajador.nombre}?`,
    header: 'Eliminar Kardex PDF',
    icon: 'pi pi-exclamation-triangle',
    acceptClass: 'p-button-danger',
    accept: () => eliminarKardexPdf(trabajador)
  })
}

const eliminarKardexPdf = async (trabajador) => {
  try {
    await api.delete(`/administracion/trabajadores/${trabajador.id}/kardex-pdf`)
    toast.add({ severity: 'success', summary: 'Eliminado', detail: 'Kardex PDF eliminado', life: 3000 })
    const idx = trabajadores.value.findIndex(t => t.id === trabajador.id)
    if (idx !== -1) {
      trabajadores.value[idx] = {
        ...trabajadores.value[idx],
        tiene_kardex: false,
        kardex_pdf_nombre_original: null,
        kardex_pdf_tamano: null,
        kardex_pdf_subido_en: null
      }
      if (kardexTrabajador.value?.id === trabajador.id) {
        kardexTrabajador.value = trabajadores.value[idx]
      }
    }
  } catch (err) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudo eliminar el PDF', life: 5000 })
  }
}

const formatBytes = (bytes) => {
  if (!bytes) return '-'
  if (bytes >= 1048576) return (bytes / 1048576).toFixed(1) + ' MB'
  return (bytes / 1024).toFixed(0) + ' KB'
}

const formatDate = (date) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('es-PE')
}

const formatDateTime = (date) => {
  if (!date) return '-'
  return new Date(date).toLocaleString('es-PE', { dateStyle: 'short', timeStyle: 'short' })
}
</script>

<template>
  <div class="space-y-4">
    <Toast />
    <ConfirmDialog />

    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold text-gray-800">Trabajadores</h2>
        <p class="text-gray-500 text-sm">Gestión del personal de obra</p>
      </div>
      <Button
        label="Nuevo Trabajador"
        icon="pi pi-plus"
        @click="openNew"
        class="!bg-amber-600 !border-amber-600"
      />
    </div>

    <!-- Tabla -->
    <div class="bg-white rounded-xl shadow-sm p-4">
      <!-- Filtros -->
      <div class="flex flex-wrap items-center gap-4 mb-4">
        <div class="relative flex-1 min-w-64 max-w-md">
          <i class="pi pi-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
          <InputText
            v-model="searchQuery"
            placeholder="Buscar por nombre, DNI o cargo..."
            class="w-full pl-10"
          />
        </div>
        <Select
          v-model="selectedCentroCosto"
          :options="centrosCosto"
          optionLabel="label"
          optionValue="value"
          placeholder="Centro de Costo"
          class="w-48"
          showClear
          @change="loadTrabajadores"
        />
        <Select
          v-model="selectedEstado"
          :options="estadoOptions"
          optionLabel="label"
          optionValue="value"
          placeholder="Estado"
          class="w-36"
          showClear
          @change="loadTrabajadores"
        />
      </div>

      <DataTable
        :value="filteredTrabajadores"
        :loading="loading"
        paginator
        :rows="10"
        stripedRows
        emptyMessage="No hay trabajadores registrados"
        class="text-sm"
      >
        <Column field="nombre" header="Nombre" sortable style="min-width: 200px">
          <template #body="{ data }">
            <div>
              <p class="font-medium text-gray-800">{{ data.nombre }}</p>
              <p class="text-xs text-gray-500">{{ data.cargo || 'Sin cargo' }}</p>
            </div>
          </template>
        </Column>
        <Column field="dni" header="DNI" sortable style="width: 120px">
          <template #body="{ data }">
            <span class="font-mono">{{ data.dni || '-' }}</span>
          </template>
        </Column>
        <Column field="centro_costo" header="Centro de Costo" style="width: 180px">
          <template #body="{ data }">
            <span v-if="data.centro_costo" class="text-sm">
              {{ data.centro_costo.codigo }} - {{ data.centro_costo.nombre }}
            </span>
            <span v-else class="text-gray-400">Sin asignar</span>
          </template>
        </Column>
        <Column field="fecha_ingreso" header="Ingreso" style="width: 100px">
          <template #body="{ data }">
            {{ formatDate(data.fecha_ingreso) }}
          </template>
        </Column>
        <Column header="Kardex" style="width: 80px; text-align: center">
          <template #body="{ data }">
            <span
              v-if="data.tiene_kardex"
              class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-red-100 cursor-pointer"
              v-tooltip.top="'Ver Kardex PDF'"
              @click="verKardexPdf(data)"
            >
              <i class="pi pi-file-pdf text-red-600 text-sm"></i>
            </span>
            <span v-else class="text-gray-300 text-xs">—</span>
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
        <Column header="Acciones" style="width: 120px">
          <template #body="{ data }">
            <div class="flex gap-1">
              <Button
                icon="pi pi-pencil"
                severity="secondary"
                text
                rounded
                size="small"
                @click="editTrabajador(data)"
                v-tooltip.top="'Editar'"
              />
              <Button
                icon="pi pi-file-pdf"
                :severity="data.tiene_kardex ? 'danger' : 'secondary'"
                text
                rounded
                size="small"
                @click="openKardexDialog(data)"
                v-tooltip.top="'Kardex PDF'"
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
      :header="isEditing ? 'Editar Trabajador' : 'Nuevo Trabajador'"
      modal
      :style="{ width: '500px' }"
    >
      <div class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Nombre completo *</label>
          <InputText v-model="formData.nombre" class="w-full" placeholder="Nombres y apellidos" />
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">DNI</label>
            <InputText v-model="formData.dni" class="w-full" placeholder="12345678" maxlength="8" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Teléfono</label>
            <InputText v-model="formData.telefono" class="w-full" placeholder="987654321" />
          </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Cargo</label>
            <InputText v-model="formData.cargo" class="w-full" placeholder="Ej: Operario" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de Ingreso</label>
            <DatePicker
              v-model="formData.fecha_ingreso"
              dateFormat="dd/mm/yy"
              class="w-full"
              showIcon
            />
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Centro de Costo</label>
          <Select
            v-model="formData.centro_costo_id"
            :options="centrosCosto"
            optionLabel="label"
            optionValue="value"
            placeholder="Seleccione centro de costo"
            class="w-full"
            showClear
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Observaciones</label>
          <Textarea v-model="formData.observaciones" rows="2" class="w-full" />
        </div>
      </div>

      <template #footer>
        <Button label="Cancelar" severity="secondary" @click="dialogVisible = false" />
        <Button
          :label="isEditing ? 'Actualizar' : 'Guardar'"
          icon="pi pi-check"
          @click="saveTrabajador"
          :loading="loading"
          class="!bg-amber-600 !border-amber-600"
        />
      </template>
    </Dialog>

    <!-- Dialog Kardex PDF -->
    <Dialog
      v-model:visible="kardexDialogVisible"
      header="Kardex Físico Escaneado (PDF)"
      modal
      :style="{ width: '480px' }"
    >
      <div v-if="kardexTrabajador" class="space-y-4">

        <!-- Info del trabajador -->
        <div class="bg-amber-50 border border-amber-200 rounded-lg p-3">
          <p class="font-semibold text-gray-800">{{ kardexTrabajador.nombre }}</p>
          <p class="text-sm text-gray-500">{{ kardexTrabajador.cargo || 'Sin cargo' }} · DNI: {{ kardexTrabajador.dni || '-' }}</p>
        </div>

        <!-- PDF actual -->
        <div v-if="kardexTrabajador.tiene_kardex" class="border border-green-200 bg-green-50 rounded-lg p-3">
          <div class="flex items-start justify-between gap-2">
            <div class="flex items-center gap-2">
              <i class="pi pi-file-pdf text-red-500 text-2xl"></i>
              <div>
                <p class="text-sm font-medium text-gray-800 break-all">{{ kardexTrabajador.kardex_pdf_nombre_original }}</p>
                <p class="text-xs text-gray-500">
                  {{ formatBytes(kardexTrabajador.kardex_pdf_tamano) }}
                  · Subido: {{ formatDateTime(kardexTrabajador.kardex_pdf_subido_en) }}
                </p>
              </div>
            </div>
            <div class="flex gap-1 flex-shrink-0">
              <Button
                icon="pi pi-eye"
                severity="success"
                text
                rounded
                size="small"
                @click="verKardexPdf(kardexTrabajador)"
                v-tooltip.top="'Ver PDF'"
              />
              <Button
                icon="pi pi-trash"
                severity="danger"
                text
                rounded
                size="small"
                @click="confirmarEliminarKardex(kardexTrabajador)"
                v-tooltip.top="'Eliminar PDF'"
              />
            </div>
          </div>
        </div>

        <div v-else class="text-center py-3 text-gray-400 border border-dashed border-gray-300 rounded-lg">
          <i class="pi pi-file-pdf text-3xl mb-1 block"></i>
          <p class="text-sm">Sin kardex PDF adjunto</p>
        </div>

        <!-- Subir nuevo PDF -->
        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-2">
            {{ kardexTrabajador.tiene_kardex ? 'Reemplazar PDF' : 'Subir PDF' }}
          </label>
          <p class="text-xs text-gray-500 mb-2">Formatos: PDF · Máximo: 20 MB · Multi-página permitido</p>

          <input
            ref="kardexFileInput"
            type="file"
            accept="application/pdf"
            class="hidden"
            @change="onKardexFileChange"
          />

          <div
            class="border-2 border-dashed border-gray-300 rounded-lg p-4 text-center cursor-pointer hover:border-amber-400 hover:bg-amber-50 transition-colors"
            @click="kardexFileInput.click()"
          >
            <i class="pi pi-upload text-2xl text-gray-400 mb-1 block"></i>
            <p v-if="!kardexFile" class="text-sm text-gray-500">Haga clic para seleccionar PDF</p>
            <p v-else class="text-sm text-amber-700 font-medium">
              <i class="pi pi-file-pdf text-red-500 mr-1"></i>
              {{ kardexFile.name }}
              <span class="text-gray-400 ml-1">({{ formatBytes(kardexFile.size) }})</span>
            </p>
          </div>
        </div>
      </div>

      <template #footer>
        <Button label="Cerrar" severity="secondary" @click="kardexDialogVisible = false" />
        <Button
          label="Subir PDF"
          icon="pi pi-upload"
          :loading="uploadingKardex"
          :disabled="!kardexFile"
          @click="subirKardexPdf"
          class="!bg-amber-600 !border-amber-600"
        />
      </template>
    </Dialog>
  </div>
</template>
