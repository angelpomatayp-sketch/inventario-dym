<script setup>
import { ref, onMounted, computed } from 'vue'
import { useToast } from 'primevue/usetoast'
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
import usuariosService from '@/services/usuariosService'
import api from '@/services/api'

const toast = useToast()
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
</script>

<template>
  <div class="space-y-4">
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
        <Column header="Acciones" style="width: 100px">
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
