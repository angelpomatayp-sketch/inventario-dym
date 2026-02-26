<script setup>
import { ref, onMounted, computed } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import { useAuthStore } from '@/stores/auth'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Dialog from 'primevue/dialog'
import Select from 'primevue/select'
import Tag from 'primevue/tag'
import ToggleSwitch from 'primevue/toggleswitch'
import Password from 'primevue/password'
import ConfirmDialog from 'primevue/confirmdialog'
import usuariosService from '@/services/usuariosService'
import api from '@/services/api'

const toast = useToast()
const confirm = useConfirm()
const authStore = useAuthStore()

// Estado
const usuarios = ref([])
const roles = ref([])
const centrosCosto = ref([])
const almacenes = ref([])
const loading = ref(false)
const dialogVisible = ref(false)
const deleteDialogVisible = ref(false)
const isEditing = ref(false)
const selectedUsuario = ref(null)
const searchQuery = ref('')
const submitting = ref(false)

// Formulario
const formData = ref({
  nombre: '',
  email: '',
  password: '',
  password_confirmation: '',
  dni: '',
  telefono: '',
  rol: null,
  centro_costo_id: null,
  almacen_id: null,
  activo: true
})

// Selects con objetos completos (fix PrimeVue 4)
const selectedRol = ref(null)
const selectedCentroCosto = ref(null)
const selectedAlmacen = ref(null)

// Labels para roles
const roleLabels = {
  'super_admin': 'Super Admin',
  'jefe_logistica': 'Jefe Logística',
  'asistente_admin': 'Asist. Administrativa',
  'almacenero': 'Almacenero',
  'residente': 'Residente',
  'solicitante': 'Solicitante',
  'auditor': 'Auditor'
}

// Colores para roles
const roleSeverity = (rol) => {
  const severities = {
    'super_admin': 'danger',
    'jefe_logistica': 'warn',
    'asistente_admin': 'info',
    'almacenero': 'success',
    'residente': 'primary',
    'solicitante': 'secondary',
    'auditor': 'contrast'
  }
  return severities[rol] || 'secondary'
}

// Computed - filtrar usuarios
const filteredUsuarios = computed(() => {
  if (!searchQuery.value) return usuarios.value
  const query = searchQuery.value.toLowerCase()
  return usuarios.value.filter(u =>
    u.nombre?.toLowerCase().includes(query) ||
    u.email?.toLowerCase().includes(query) ||
    u.dni?.includes(query)
  )
})

// Computed - mostrar campo almacén solo para almaceneros
const showAlmacenField = computed(() => {
  return selectedRol.value?.value === 'almacenero'
})

// Computed - centro de costo asignado del usuario actual
const centroCostoAsignado = computed(() => authStore.user?.centro_costo_id || null)

// Computed - nombre del centro de costo asignado
const nombreCentroCostoAsignado = computed(() => {
  if (authStore.user?.centro_costo) {
    const cc = authStore.user.centro_costo
    if (cc.codigo && cc.nombre) {
      return `${cc.codigo} - ${cc.nombre}`
    }
    return cc.nombre || 'Centro de costo asignado'
  }
  if (!centroCostoAsignado.value || centrosCosto.value.length === 0) return null
  const ccId = parseInt(centroCostoAsignado.value)
  const cc = centrosCosto.value.find(c => parseInt(c.value) === ccId)
  return cc?.label || null
})

// Cargar datos iniciales
onMounted(async () => {
  await Promise.all([
    loadUsuarios(),
    loadRoles(),
    loadCentrosCosto(),
    loadAlmacenes()
  ])
})

const loadUsuarios = async () => {
  loading.value = true
  try {
    const response = await usuariosService.getAll()
    usuarios.value = response.data || []
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudieron cargar los usuarios', life: 3000 })
  } finally {
    loading.value = false
  }
}

const loadRoles = async () => {
  try {
    const response = await usuariosService.getRoles()
    roles.value = (response.data || []).map(r => ({
      label: roleLabels[r.name] || r.name,
      value: r.name
    }))
  } catch (error) {
    console.error('Error cargando roles:', error)
  }
}

const loadCentrosCosto = async () => {
  try {
    const response = await api.get('/administracion/centros-costo')
    centrosCosto.value = (response.data?.data || []).map(cc => ({
      label: `${cc.codigo} - ${cc.nombre}`,
      value: cc.id
    }))
  } catch (error) {
    console.error('Error cargando centros de costo:', error)
  }
}

const loadAlmacenes = async () => {
  try {
    const response = await api.get('/administracion/almacenes')
    almacenes.value = (response.data?.data || []).map(a => ({
      label: `${a.codigo} - ${a.nombre}`,
      value: a.id
    }))
  } catch (error) {
    console.error('Error cargando almacenes:', error)
  }
}

// Métodos
const openNew = () => {
  formData.value = {
    nombre: '',
    email: '',
    password: '',
    password_confirmation: '',
    dni: '',
    telefono: '',
    rol: null,
    centro_costo_id: centroCostoAsignado.value || null,
    almacen_id: null,
    activo: true
  }
  selectedRol.value = null
  // Pre-seleccionar centro de costo si el usuario tiene uno asignado
  if (centroCostoAsignado.value) {
    const ccId = parseInt(centroCostoAsignado.value)
    selectedCentroCosto.value = centrosCosto.value.find(c => parseInt(c.value) === ccId) || null
  } else {
    selectedCentroCosto.value = null
  }
  selectedAlmacen.value = null
  isEditing.value = false
  dialogVisible.value = true
}

const editUsuario = (usuario) => {
  formData.value = {
    id: usuario.id,
    nombre: usuario.nombre,
    email: usuario.email,
    password: '',
    password_confirmation: '',
    dni: usuario.dni || '',
    telefono: usuario.telefono || '',
    rol: usuario.roles?.[0]?.name || null,
    centro_costo_id: usuario.centro_costo_id,
    almacen_id: usuario.almacen_id,
    activo: usuario.activo
  }
  // Establecer objetos seleccionados para los Selects
  const rolName = usuario.roles?.[0]?.name
  selectedRol.value = roles.value.find(r => r.value === rolName) || null
  selectedCentroCosto.value = centrosCosto.value.find(cc => cc.value === usuario.centro_costo_id) || null
  selectedAlmacen.value = almacenes.value.find(a => a.value === usuario.almacen_id) || null
  isEditing.value = true
  dialogVisible.value = true
}

const confirmDelete = (usuario) => {
  selectedUsuario.value = usuario
  deleteDialogVisible.value = true
}

const saveUsuario = async () => {
  submitting.value = true
  try {
    const rolValue = selectedRol.value?.value || null

    // Usar centro de costo asignado si existe, sino el seleccionado
    const centroCostoId = centroCostoAsignado.value || selectedCentroCosto.value?.value || null

    // Construir datos para enviar al backend
    const data = {
      nombre: formData.value.nombre,
      email: formData.value.email,
      dni: formData.value.dni || null,
      telefono: formData.value.telefono || null,
      centro_costo_id: centroCostoId,
      almacen_id: rolValue === 'almacenero' ? (selectedAlmacen.value?.value || null) : null,
      activo: formData.value.activo,
      // Backend espera 'roles' como array
      roles: rolValue ? [rolValue] : [],
      // empresa_id del usuario autenticado
      empresa_id: authStore.user?.empresa_id || 1
    }

    // Solo enviar password si se proporcionó
    if (formData.value.password) {
      data.password = formData.value.password
      data.password_confirmation = formData.value.password_confirmation
    }

    if (isEditing.value) {
      await usuariosService.update(formData.value.id, data)
      toast.add({ severity: 'success', summary: 'Éxito', detail: 'Usuario actualizado correctamente', life: 3000 })
    } else {
      await usuariosService.create(data)
      toast.add({ severity: 'success', summary: 'Éxito', detail: 'Usuario creado correctamente', life: 3000 })
    }

    dialogVisible.value = false
    await loadUsuarios()
  } catch (error) {
    const message = error.response?.data?.message || 'Error al guardar el usuario'
    toast.add({ severity: 'error', summary: 'Error', detail: message, life: 5000 })
  } finally {
    submitting.value = false
  }
}

const deleteUsuario = async () => {
  try {
    await usuariosService.delete(selectedUsuario.value.id)
    toast.add({ severity: 'success', summary: 'Éxito', detail: 'Usuario eliminado correctamente', life: 3000 })
    deleteDialogVisible.value = false
    await loadUsuarios()
  } catch (error) {
    const message = error.response?.data?.message || 'Error al eliminar el usuario'
    toast.add({ severity: 'error', summary: 'Error', detail: message, life: 5000 })
  }
}

const getRolLabel = (roles) => {
  if (!roles || roles.length === 0) return 'Sin rol'
  return roleLabels[roles[0].name] || roles[0].name
}

// ==================== KARDEX PDF ====================

const kardexDialogVisible = ref(false)
const kardexUsuario = ref(null)
const kardexFile = ref(null)
const kardexFileInput = ref(null)
const uploadingKardex = ref(false)

const openKardexDialog = (usuario) => {
  kardexUsuario.value = usuario
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
      `/administracion/usuarios/${kardexUsuario.value.id}/kardex-pdf`,
      formDataUpload,
      { headers: { 'Content-Type': 'multipart/form-data' } }
    )
    if (response.data.success) {
      toast.add({ severity: 'success', summary: 'Subido', detail: 'Kardex PDF subido exitosamente', life: 3000 })
      const idx = usuarios.value.findIndex(u => u.id === kardexUsuario.value.id)
      if (idx !== -1) {
        usuarios.value[idx] = { ...usuarios.value[idx], ...response.data.data, tiene_kardex: true }
        kardexUsuario.value = usuarios.value[idx]
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

const verKardexPdf = async (usuario) => {
  try {
    const response = await api.get(
      `/administracion/usuarios/${usuario.id}/kardex-pdf`,
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

const confirmarEliminarKardex = (usuario) => {
  confirm.require({
    message: `¿Eliminar el kardex PDF de ${usuario.nombre}?`,
    header: 'Eliminar Kardex PDF',
    icon: 'pi pi-exclamation-triangle',
    acceptClass: 'p-button-danger',
    accept: () => eliminarKardexPdf(usuario)
  })
}

const eliminarKardexPdf = async (usuario) => {
  try {
    await api.delete(`/administracion/usuarios/${usuario.id}/kardex-pdf`)
    toast.add({ severity: 'success', summary: 'Eliminado', detail: 'Kardex PDF eliminado', life: 3000 })
    const idx = usuarios.value.findIndex(u => u.id === usuario.id)
    if (idx !== -1) {
      usuarios.value[idx] = { ...usuarios.value[idx], tiene_kardex: false, kardex_pdf_nombre_original: null, kardex_pdf_tamano: null, kardex_pdf_subido_en: null }
      if (kardexUsuario.value?.id === usuario.id) kardexUsuario.value = usuarios.value[idx]
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

const formatDateTime = (date) => {
  if (!date) return '-'
  return new Date(date).toLocaleString('es-PE', { dateStyle: 'short', timeStyle: 'short' })
}
</script>

<template>
  <div class="space-y-4">
    <ConfirmDialog />
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold text-gray-800">Usuarios</h2>
        <p class="text-gray-500 text-sm">Gestión de usuarios del sistema</p>
      </div>
      <Button
        label="Nuevo Usuario"
        icon="pi pi-plus"
        @click="openNew"
        class="!bg-amber-600 !border-amber-600 hover:!bg-amber-700"
      />
    </div>

    <!-- Tabla -->
    <div class="bg-white rounded-xl shadow-sm p-4">
      <!-- Buscador -->
      <div class="flex items-center gap-4 mb-4">
        <div class="relative flex-1 max-w-md">
          <i class="pi pi-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
          <InputText
            v-model="searchQuery"
            placeholder="Buscar usuario..."
            class="w-full pl-10"
          />
        </div>
        <Button
          icon="pi pi-refresh"
          severity="secondary"
          text
          rounded
          @click="loadUsuarios"
          :loading="loading"
          v-tooltip="'Actualizar'"
        />
      </div>

      <DataTable
        :value="filteredUsuarios"
        :loading="loading"
        paginator
        :rows="10"
        stripedRows
        emptyMessage="No hay usuarios registrados"
        class="text-sm"
      >
        <Column field="nombre" header="Nombre" sortable style="min-width: 200px">
          <template #body="{ data }">
            <div class="flex items-center gap-3">
              <div class="w-9 h-9 rounded-full bg-amber-100 flex items-center justify-center">
                <i class="pi pi-user text-amber-600"></i>
              </div>
              <div>
                <p class="font-medium text-gray-800">{{ data.nombre }}</p>
                <p class="text-xs text-gray-500">{{ data.email }}</p>
              </div>
            </div>
          </template>
        </Column>
        <Column field="dni" header="DNI" sortable style="width: 100px">
          <template #body="{ data }">
            <span class="font-mono text-gray-600">{{ data.dni || '-' }}</span>
          </template>
        </Column>
        <Column field="telefono" header="Teléfono" style="width: 110px">
          <template #body="{ data }">
            <span class="text-gray-600">{{ data.telefono || '-' }}</span>
          </template>
        </Column>
        <Column header="Rol" style="width: 140px">
          <template #body="{ data }">
            <Tag
              :value="getRolLabel(data.roles)"
              :severity="roleSeverity(data.roles?.[0]?.name)"
            />
          </template>
        </Column>
        <Column header="Centro de Costo" style="min-width: 150px">
          <template #body="{ data }">
            <span v-if="data.centro_costo" class="text-gray-700">
              {{ data.centro_costo.nombre }}
            </span>
            <span v-else class="text-gray-400">-</span>
          </template>
        </Column>
        <Column header="Almacén" style="min-width: 130px">
          <template #body="{ data }">
            <span v-if="data.almacen" class="text-gray-700">
              {{ data.almacen.nombre }}
            </span>
            <span v-else class="text-gray-400">-</span>
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
        <Column header="Acciones" style="width: 120px">
          <template #body="{ data }">
            <div class="flex gap-1">
              <Button
                icon="pi pi-pencil"
                severity="secondary"
                text
                rounded
                size="small"
                @click="editUsuario(data)"
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
      :header="isEditing ? 'Editar Usuario' : 'Nuevo Usuario'"
      modal
      :style="{ width: '550px' }"
      :closable="!submitting"
    >
      <div class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Nombre completo *</label>
          <InputText v-model="formData.nombre" class="w-full" placeholder="Ingrese nombre" />
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">DNI</label>
            <InputText v-model="formData.dni" class="w-full" placeholder="12345678" maxlength="8" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Teléfono</label>
            <InputText v-model="formData.telefono" class="w-full" placeholder="999888777" />
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
          <InputText v-model="formData.email" type="email" class="w-full" placeholder="usuario@empresa.com" />
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Contraseña {{ isEditing ? '(dejar vacío para mantener)' : '*' }}
            </label>
            <Password
              v-model="formData.password"
              class="w-full"
              toggleMask
              :feedback="false"
              inputClass="w-full"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Confirmar contraseña</label>
            <Password
              v-model="formData.password_confirmation"
              class="w-full"
              toggleMask
              :feedback="false"
              inputClass="w-full"
            />
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Rol *</label>
          <Select
            v-model="selectedRol"
            :options="roles"
            optionLabel="label"
            placeholder="Seleccione rol"
            class="w-full"
          >
            <template #value="slotProps">
              <span v-if="slotProps.value">{{ slotProps.value.label }}</span>
              <span v-else class="text-gray-400">{{ slotProps.placeholder }}</span>
            </template>
          </Select>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Centro de Costo / Proyecto</label>
            <!-- Si el usuario tiene centro de costo asignado, mostrar bloqueado -->
            <template v-if="centroCostoAsignado">
              <InputText
                :modelValue="nombreCentroCostoAsignado || 'Centro de costo asignado'"
                class="w-full"
                disabled
              />
              <p class="text-xs text-gray-500 mt-1">Asignado a tu centro de costo</p>
            </template>
            <Select
              v-else
              v-model="selectedCentroCosto"
              :options="centrosCosto"
              optionLabel="label"
              placeholder="Seleccione"
              class="w-full"
              showClear
            >
              <template #value="slotProps">
                <span v-if="slotProps.value">{{ slotProps.value.label }}</span>
                <span v-else class="text-gray-400">{{ slotProps.placeholder }}</span>
              </template>
            </Select>
          </div>
          <div v-if="showAlmacenField">
            <label class="block text-sm font-medium text-gray-700 mb-1">Almacén asignado *</label>
            <Select
              v-model="selectedAlmacen"
              :options="almacenes"
              optionLabel="label"
              placeholder="Seleccione almacén"
              class="w-full"
            >
              <template #value="slotProps">
                <span v-if="slotProps.value">{{ slotProps.value.label }}</span>
                <span v-else class="text-gray-400">{{ slotProps.placeholder }}</span>
              </template>
            </Select>
          </div>
        </div>

        <div class="flex items-center gap-2">
          <ToggleSwitch v-model="formData.activo" />
          <label class="text-sm text-gray-700">Usuario activo</label>
        </div>
      </div>

      <template #footer>
        <Button label="Cancelar" severity="secondary" @click="dialogVisible = false" :disabled="submitting" />
        <Button
          :label="isEditing ? 'Actualizar' : 'Guardar'"
          @click="saveUsuario"
          :loading="submitting"
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
      <div v-if="kardexUsuario" class="space-y-4">
        <div class="bg-amber-50 border border-amber-200 rounded-lg p-3">
          <p class="font-semibold text-gray-800">{{ kardexUsuario.nombre }}</p>
          <p class="text-sm text-gray-500">{{ kardexUsuario.email }} · DNI: {{ kardexUsuario.dni || '-' }}</p>
        </div>

        <div v-if="kardexUsuario.tiene_kardex" class="border border-green-200 bg-green-50 rounded-lg p-3">
          <div class="flex items-start justify-between gap-2">
            <div class="flex items-center gap-2">
              <i class="pi pi-file-pdf text-red-500 text-2xl"></i>
              <div>
                <p class="text-sm font-medium text-gray-800 break-all">{{ kardexUsuario.kardex_pdf_nombre_original }}</p>
                <p class="text-xs text-gray-500">
                  {{ formatBytes(kardexUsuario.kardex_pdf_tamano) }}
                  · Subido: {{ formatDateTime(kardexUsuario.kardex_pdf_subido_en) }}
                </p>
              </div>
            </div>
            <div class="flex gap-1 flex-shrink-0">
              <Button icon="pi pi-eye" severity="success" text rounded size="small" @click="verKardexPdf(kardexUsuario)" v-tooltip.top="'Ver PDF'" />
              <Button icon="pi pi-trash" severity="danger" text rounded size="small" @click="confirmarEliminarKardex(kardexUsuario)" v-tooltip.top="'Eliminar PDF'" />
            </div>
          </div>
        </div>
        <div v-else class="text-center py-3 text-gray-400 border border-dashed border-gray-300 rounded-lg">
          <i class="pi pi-file-pdf text-3xl mb-1 block"></i>
          <p class="text-sm">Sin kardex PDF adjunto</p>
        </div>

        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-2">
            {{ kardexUsuario.tiene_kardex ? 'Reemplazar PDF' : 'Subir PDF' }}
          </label>
          <p class="text-xs text-gray-500 mb-2">Formatos: PDF · Máximo: 20 MB · Multi-página permitido</p>
          <input ref="kardexFileInput" type="file" accept="application/pdf" class="hidden" @change="onKardexFileChange" />
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
        <Button label="Subir PDF" icon="pi pi-upload" :loading="uploadingKardex" :disabled="!kardexFile" @click="subirKardexPdf" class="!bg-amber-600 !border-amber-600" />
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
        <p>¿Está seguro que desea eliminar al usuario <strong>{{ selectedUsuario?.nombre }}</strong>?</p>
      </div>
      <template #footer>
        <Button label="Cancelar" severity="secondary" @click="deleteDialogVisible = false" />
        <Button label="Eliminar" severity="danger" @click="deleteUsuario" />
      </template>
    </Dialog>
  </div>
</template>
