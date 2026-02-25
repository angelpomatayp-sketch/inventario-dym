<script setup>
import { ref, onMounted, computed } from 'vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Dialog from 'primevue/dialog'
import Tag from 'primevue/tag'
import ToggleSwitch from 'primevue/toggleswitch'
import Textarea from 'primevue/textarea'
import Toast from 'primevue/toast'
import Select from 'primevue/select'
import Checkbox from 'primevue/checkbox'
import { useToast } from 'primevue/usetoast'
import api from '@/services/api'

const toast = useToast()

// Estado
const familias = ref([])
const loading = ref(false)
const dialogVisible = ref(false)
const deleteDialogVisible = ref(false)
const isEditing = ref(false)
const selectedFamilia = ref(null)
const searchQuery = ref('')

// Formulario
const formData = ref({
  codigo: '',
  nombre: '',
  descripcion: '',
  es_epp: false,
  categoria_epp: null,
  activo: true
})

// Categorías de EPP
const categoriasEpp = [
  { label: 'Protección de Cabeza', value: 'CABEZA' },
  { label: 'Protección Ocular', value: 'OJOS' },
  { label: 'Protección Auditiva', value: 'OIDOS' },
  { label: 'Protección Respiratoria', value: 'RESPIRATORIO' },
  { label: 'Protección de Manos', value: 'MANOS' },
  { label: 'Protección de Pies', value: 'PIES' },
  { label: 'Protección Corporal', value: 'CUERPO' },
  { label: 'Trabajo en Altura', value: 'ALTURA' }
]

// Computed para obtener label de categoría
const getCategoriaLabel = (value) => {
  const cat = categoriasEpp.find(c => c.value === value)
  return cat ? cat.label : value
}

const filteredFamilias = computed(() => {
  const term = (searchQuery.value || '').toLowerCase().trim()
  if (!term) return familias.value

  return familias.value.filter((f) => {
    return (
      (f.codigo || '').toLowerCase().includes(term) ||
      (f.nombre || '').toLowerCase().includes(term) ||
      (f.descripcion || '').toLowerCase().includes(term)
    )
  })
})

// Cargar familias desde API
const loadFamilias = async () => {
  loading.value = true
  try {
    const response = await api.get('/inventario/familias', { params: { all: true } })
    if (response.data.success) {
      familias.value = response.data.data
    }
  } catch (err) {
    console.error('Error al cargar familias:', err)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'No se pudieron cargar las familias',
      life: 3000
    })
  } finally {
    loading.value = false
  }
}

// Métodos
const openNew = () => {
  formData.value = {
    codigo: '',
    nombre: '',
    descripcion: '',
    es_epp: false,
    categoria_epp: null,
    activo: true
  }
  isEditing.value = false
  dialogVisible.value = true
}

const editFamilia = (familia) => {
  formData.value = { ...familia }
  isEditing.value = true
  dialogVisible.value = true
}

const confirmDelete = (familia) => {
  selectedFamilia.value = familia
  deleteDialogVisible.value = true
}

const saveFamilia = async () => {
  try {
    if (isEditing.value) {
      const response = await api.put(`/inventario/familias/${formData.value.id}`, formData.value)
      if (response.data.success) {
        toast.add({
          severity: 'success',
          summary: 'Actualizado',
          detail: 'Familia actualizada correctamente',
          life: 3000
        })
      }
    } else {
      const response = await api.post('/inventario/familias', formData.value)
      if (response.data.success) {
        toast.add({
          severity: 'success',
          summary: 'Creado',
          detail: 'Familia creada correctamente',
          life: 3000
        })
      }
    }
    dialogVisible.value = false
    loadFamilias()
  } catch (err) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: err.response?.data?.message || 'Error al guardar la familia',
      life: 5000
    })
  }
}

const deleteFamilia = async () => {
  try {
    const response = await api.delete(`/inventario/familias/${selectedFamilia.value.id}`)
    if (response.data.success) {
      toast.add({
        severity: 'success',
        summary: 'Eliminado',
        detail: 'Familia eliminada correctamente',
        life: 3000
      })
      loadFamilias()
    }
  } catch (err) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: err.response?.data?.message || 'Error al eliminar la familia',
      life: 5000
    })
  } finally {
    deleteDialogVisible.value = false
  }
}

onMounted(() => {
  loadFamilias()
})
</script>

<template>
  <div class="space-y-4">
    <Toast />

    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold text-gray-800">Familias / Categorías</h2>
        <p class="text-gray-500 text-sm">Gestión de categorías de productos</p>
      </div>
      <Button
        label="Nueva Familia"
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
            placeholder="Buscar familia..."
            class="w-full pl-10"
          />
        </div>
      </div>

      <DataTable
        :value="filteredFamilias"
        :loading="loading"
        paginator
        :rows="10"
        stripedRows
      >
        <template #empty>
          <div class="text-center py-8 text-gray-500">
            <i class="pi pi-folder-open text-4xl mb-2"></i>
            <p>No hay familias registradas</p>
          </div>
        </template>

        <Column field="codigo" header="Código" sortable style="width: 120px">
          <template #body="{ data }">
            <span class="font-mono font-medium text-gray-700">{{ data.codigo }}</span>
          </template>
        </Column>
        <Column field="nombre" header="Nombre" sortable>
          <template #body="{ data }">
            <div>
              <p class="font-medium text-gray-800">{{ data.nombre }}</p>
              <p class="text-xs text-gray-500" v-if="data.descripcion">{{ data.descripcion }}</p>
            </div>
          </template>
        </Column>
        <Column field="productos_count" header="Productos" style="width: 100px">
          <template #body="{ data }">
            <span class="text-gray-600">{{ data.productos_count || 0 }}</span>
          </template>
        </Column>
        <Column field="es_epp" header="EPP" style="width: 150px">
          <template #body="{ data }">
            <div v-if="data.es_epp">
              <Tag value="EPP" severity="warn" class="mr-1" />
              <span class="text-xs text-gray-500">{{ getCategoriaLabel(data.categoria_epp) }}</span>
            </div>
            <span v-else class="text-gray-400">-</span>
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
              <Button icon="pi pi-pencil" severity="secondary" text rounded @click="editFamilia(data)" />
              <Button icon="pi pi-trash" severity="danger" text rounded @click="confirmDelete(data)" />
            </div>
          </template>
        </Column>
      </DataTable>
    </div>

    <!-- Dialog Crear/Editar -->
    <Dialog
      v-model:visible="dialogVisible"
      :header="isEditing ? 'Editar Familia' : 'Nueva Familia'"
      modal
      :style="{ width: '500px' }"
    >
      <div class="space-y-4">
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Código *</label>
            <InputText v-model="formData.codigo" class="w-full" placeholder="EPP" maxlength="10" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Nombre *</label>
            <InputText v-model="formData.nombre" class="w-full" placeholder="Equipos de Protección" />
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
          <Textarea v-model="formData.descripcion" rows="3" class="w-full" placeholder="Descripción de la categoría (opcional)" />
        </div>

        <!-- Sección EPP -->
        <div class="border rounded-lg p-4 bg-amber-50">
          <div class="flex items-center gap-2 mb-3">
            <Checkbox v-model="formData.es_epp" :binary="true" inputId="esEpp" />
            <label for="esEpp" class="text-sm font-medium text-gray-700">
              Esta familia es de Equipos de Protección Personal (EPP)
            </label>
          </div>

          <div v-if="formData.es_epp">
            <label class="block text-sm font-medium text-gray-700 mb-1">Categoría de EPP *</label>
            <Select
              v-model="formData.categoria_epp"
              :options="categoriasEpp"
              optionLabel="label"
              optionValue="value"
              placeholder="Seleccione categoría"
              class="w-full"
            />
            <p class="text-xs text-gray-500 mt-1">
              <i class="pi pi-info-circle mr-1"></i>
              Los productos de esta familia podrán vincularse a Tipos de EPP
            </p>
          </div>
        </div>

        <div class="flex items-center gap-2">
          <ToggleSwitch v-model="formData.activo" />
          <label class="text-sm text-gray-700">Familia activa</label>
        </div>
      </div>

      <template #footer>
        <Button label="Cancelar" severity="secondary" @click="dialogVisible = false" />
        <Button :label="isEditing ? 'Actualizar' : 'Guardar'" @click="saveFamilia" class="!bg-amber-600 !border-amber-600" />
      </template>
    </Dialog>

    <!-- Dialog Confirmar Eliminación -->
    <Dialog v-model:visible="deleteDialogVisible" header="Confirmar Eliminación" modal :style="{ width: '400px' }">
      <div class="flex items-center gap-4">
        <i class="pi pi-exclamation-triangle text-4xl text-amber-500"></i>
        <div>
          <p>¿Está seguro que desea eliminar la familia <strong>{{ selectedFamilia?.nombre }}</strong>?</p>
          <p class="text-sm text-gray-500 mt-1">Esta acción no se puede deshacer.</p>
        </div>
      </div>
      <template #footer>
        <Button label="Cancelar" severity="secondary" @click="deleteDialogVisible = false" />
        <Button label="Eliminar" severity="danger" @click="deleteFamilia" />
      </template>
    </Dialog>
  </div>
</template>
