<script setup>
import { ref, onMounted, computed } from 'vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Dialog from 'primevue/dialog'
import Tag from 'primevue/tag'
import ToggleSwitch from 'primevue/toggleswitch'
import Toast from 'primevue/toast'
import { useToast } from 'primevue/usetoast'
import api from '@/services/api'

const toast = useToast()

// Estado
const unidades = ref([])
const loading = ref(false)
const dialogVisible = ref(false)
const deleteDialogVisible = ref(false)
const isEditing = ref(false)
const selectedUnidad = ref(null)
const searchQuery = ref('')

// Formulario
const formData = ref({
  codigo: '',
  nombre: '',
  abreviatura: '',
  activo: true
})

// Cargar unidades desde API
const loadUnidades = async () => {
  loading.value = true
  try {
    const response = await api.get('/inventario/unidades')
    if (response.data.success) {
      unidades.value = (response.data.data || []).map((u) => ({
        id: u.id,
        codigo: u.codigo,
        nombre: u.nombre,
        abreviatura: u.abreviatura,
        activo: !!u.activo
      }))
    }
  } catch (err) {
    console.error('Error al cargar unidades:', err)
    unidades.value = []
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'No se pudieron cargar las unidades',
      life: 3000
    })
  } finally {
    loading.value = false
  }
}

const filteredUnidades = computed(() => {
  const term = (searchQuery.value || '').toLowerCase().trim()
  if (!term) return unidades.value

  return unidades.value.filter((u) => {
    return (
      (u.codigo || '').toLowerCase().includes(term) ||
      (u.nombre || '').toLowerCase().includes(term) ||
      (u.abreviatura || '').toLowerCase().includes(term)
    )
  })
})

// Métodos
const openNew = () => {
  formData.value = {
    codigo: '',
    nombre: '',
    abreviatura: '',
    activo: true
  }
  isEditing.value = false
  dialogVisible.value = true
}

const editUnidad = async (unidad) => {
  try {
    const response = await api.get(`/inventario/unidades/${unidad.id}`)
    const data = response.data?.data || unidad
    formData.value = {
      id: data.id,
      codigo: data.codigo,
      nombre: data.nombre,
      abreviatura: data.abreviatura,
      activo: !!data.activo
    }
    isEditing.value = true
    dialogVisible.value = true
  } catch (err) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'No se pudo cargar la unidad para editar',
      life: 3000
    })
  }
}

const confirmDelete = (unidad) => {
  selectedUnidad.value = unidad
  deleteDialogVisible.value = true
}

const saveUnidad = async () => {
  try {
    if (isEditing.value) {
      const response = await api.put(`/inventario/unidades/${formData.value.id}`, formData.value)
      if (response.data.success) {
        toast.add({
          severity: 'success',
          summary: 'Actualizado',
          detail: 'Unidad actualizada correctamente',
          life: 3000
        })
      }
    } else {
      const response = await api.post('/inventario/unidades', formData.value)
      if (response.data.success) {
        toast.add({
          severity: 'success',
          summary: 'Creado',
          detail: 'Unidad creada correctamente',
          life: 3000
        })
      }
    }
    dialogVisible.value = false
    loadUnidades()
  } catch (err) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: err.response?.data?.message || 'No se pudo guardar la unidad',
      life: 4000
    })
    await loadUnidades()
  }
}

const deleteUnidad = async () => {
  try {
    const response = await api.delete(`/inventario/unidades/${selectedUnidad.value.id}`)
    if (response.data.success) {
      toast.add({
        severity: 'success',
        summary: 'Eliminado',
        detail: 'Unidad eliminada correctamente',
        life: 3000
      })
      loadUnidades()
    }
  } catch (err) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: err.response?.data?.message || 'No se pudo eliminar la unidad',
      life: 4000
    })
    await loadUnidades()
  } finally {
    deleteDialogVisible.value = false
  }
}

onMounted(() => {
  loadUnidades()
})
</script>

<template>
  <div class="space-y-4">
    <Toast />

    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold text-gray-800">Unidades de Medida</h2>
        <p class="text-gray-500 text-sm">Gestión de unidades de medida para productos</p>
      </div>
      <Button
        label="Nueva Unidad"
        icon="pi pi-plus"
        @click="openNew"
        class="!bg-amber-600 !border-amber-600 hover:!bg-amber-700"
      />
    </div>

    <!-- Tabla -->
    <div class="bg-white rounded-xl shadow-sm p-4">
      <div class="flex items-center gap-4 mb-4">
        <div class="relative flex-1 max-w-md">
          <i class="pi pi-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
          <InputText
            v-model="searchQuery"
            placeholder="Buscar unidad..."
            class="w-full pl-10"
          />
        </div>
      </div>

      <DataTable
        :value="filteredUnidades"
        :loading="loading"
        dataKey="id"
        paginator
        :rows="10"
        stripedRows
      >
        <template #empty>
          <div class="text-center py-8 text-gray-500">
            <i class="pi pi-ruler text-4xl mb-2"></i>
            <p>No hay unidades registradas</p>
          </div>
        </template>

        <Column field="codigo" header="Código" sortable style="width: 120px">
          <template #body="{ data }">
            <span class="font-mono font-medium text-gray-700">{{ data.codigo }}</span>
          </template>
        </Column>
        <Column field="nombre" header="Nombre" sortable />
        <Column field="abreviatura" header="Abreviatura" style="width: 120px">
          <template #body="{ data }">
            <Tag :value="data.abreviatura" severity="secondary" />
          </template>
        </Column>
        <Column field="activo" header="Estado" style="width: 100px">
          <template #body="{ data }">
            <Tag
              :value="data.activo ? 'Activo' : 'Inactivo'"
              :severity="data.activo ? 'success' : 'danger'"
            />
          </template>
        </Column>
        <Column header="Acciones" style="width: 120px">
          <template #body="{ data }">
            <div class="flex gap-2">
              <Button icon="pi pi-pencil" severity="secondary" text rounded @click="editUnidad(data)" />
              <Button icon="pi pi-trash" severity="danger" text rounded @click="confirmDelete(data)" />
            </div>
          </template>
        </Column>
      </DataTable>
    </div>

    <!-- Dialog Crear/Editar -->
    <Dialog
      v-model:visible="dialogVisible"
      :header="isEditing ? 'Editar Unidad' : 'Nueva Unidad'"
      modal
      :style="{ width: '450px' }"
    >
      <div class="space-y-4">
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Código *</label>
            <InputText v-model="formData.codigo" class="w-full" placeholder="UND" maxlength="10" />
            <small class="text-gray-500">Código único de la unidad</small>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Abreviatura *</label>
            <InputText v-model="formData.abreviatura" class="w-full" placeholder="UND" maxlength="10" />
            <small class="text-gray-500">Se muestra en reportes</small>
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Nombre completo *</label>
          <InputText v-model="formData.nombre" class="w-full" placeholder="Unidad" />
        </div>

        <div class="flex items-center gap-2">
          <ToggleSwitch v-model="formData.activo" />
          <label class="text-sm text-gray-700">Unidad activa</label>
        </div>
      </div>

      <template #footer>
        <Button label="Cancelar" severity="secondary" @click="dialogVisible = false" />
        <Button :label="isEditing ? 'Actualizar' : 'Guardar'" @click="saveUnidad" class="!bg-amber-600 !border-amber-600" />
      </template>
    </Dialog>

    <!-- Dialog Confirmar Eliminación -->
    <Dialog v-model:visible="deleteDialogVisible" header="Confirmar Eliminación" modal :style="{ width: '400px' }">
      <div class="flex items-center gap-4">
        <i class="pi pi-exclamation-triangle text-4xl text-amber-500"></i>
        <div>
          <p>¿Está seguro que desea eliminar la unidad <strong>{{ selectedUnidad?.nombre }}</strong>?</p>
          <p class="text-sm text-gray-500 mt-1">Esta acción no se puede deshacer.</p>
        </div>
      </div>
      <template #footer>
        <Button label="Cancelar" severity="secondary" @click="deleteDialogVisible = false" />
        <Button label="Eliminar" severity="danger" @click="deleteUnidad" />
      </template>
    </Dialog>
  </div>
</template>
