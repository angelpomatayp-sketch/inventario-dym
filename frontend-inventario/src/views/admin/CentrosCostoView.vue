<script setup>
import { ref, onMounted, computed } from 'vue'
import { useToast } from 'primevue/usetoast'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Dialog from 'primevue/dialog'
import Tag from 'primevue/tag'
import ToggleSwitch from 'primevue/toggleswitch'
import Textarea from 'primevue/textarea'
import api from '@/services/api'

const toast = useToast()

// Estado
const centrosCosto = ref([])
const loading = ref(false)
const dialogVisible = ref(false)
const deleteDialogVisible = ref(false)
const isEditing = ref(false)
const selectedCentroCosto = ref(null)
const searchQuery = ref('')
const submitting = ref(false)

// Formulario
const formData = ref({
  codigo: '',
  nombre: '',
  descripcion: '',
  activo: true
})

// Computed - filtrar centros de costo
const filteredCentrosCosto = computed(() => {
  if (!searchQuery.value) return centrosCosto.value
  const query = searchQuery.value.toLowerCase()
  return centrosCosto.value.filter(cc =>
    cc.codigo?.toLowerCase().includes(query) ||
    cc.nombre?.toLowerCase().includes(query)
  )
})

// Cargar datos iniciales
onMounted(async () => {
  await loadCentrosCosto()
})

const loadCentrosCosto = async () => {
  loading.value = true
  try {
    const response = await api.get('/administracion/centros-costo')
    centrosCosto.value = response.data?.data || []
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudieron cargar los centros de costo', life: 3000 })
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
    activo: true
  }
  isEditing.value = false
  dialogVisible.value = true
}

const editCentroCosto = (centroCosto) => {
  formData.value = {
    id: centroCosto.id,
    codigo: centroCosto.codigo,
    nombre: centroCosto.nombre,
    descripcion: centroCosto.descripcion || '',
    activo: centroCosto.activo
  }
  isEditing.value = true
  dialogVisible.value = true
}

const confirmDelete = (centroCosto) => {
  selectedCentroCosto.value = centroCosto
  deleteDialogVisible.value = true
}

const saveCentroCosto = async () => {
  submitting.value = true
  try {
    if (isEditing.value) {
      await api.put(`/administracion/centros-costo/${formData.value.id}`, formData.value)
      toast.add({ severity: 'success', summary: 'Éxito', detail: 'Centro de costo actualizado correctamente', life: 3000 })
    } else {
      await api.post('/administracion/centros-costo', formData.value)
      toast.add({ severity: 'success', summary: 'Éxito', detail: 'Centro de costo creado correctamente', life: 3000 })
    }
    dialogVisible.value = false
    await loadCentrosCosto()
  } catch (error) {
    const message = error.response?.data?.message || 'Error al guardar el centro de costo'
    toast.add({ severity: 'error', summary: 'Error', detail: message, life: 5000 })
  } finally {
    submitting.value = false
  }
}

const deleteCentroCosto = async () => {
  try {
    await api.delete(`/administracion/centros-costo/${selectedCentroCosto.value.id}`)
    toast.add({ severity: 'success', summary: 'Éxito', detail: 'Centro de costo eliminado correctamente', life: 3000 })
    deleteDialogVisible.value = false
    await loadCentrosCosto()
  } catch (error) {
    const message = error.response?.data?.message || 'Error al eliminar el centro de costo'
    toast.add({ severity: 'error', summary: 'Error', detail: message, life: 5000 })
  }
}
</script>

<template>
  <div class="space-y-4">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold text-gray-800">Centros de Costo / Proyectos</h2>
        <p class="text-gray-500 text-sm">Gestión de centros de costo y proyectos</p>
      </div>
      <Button
        label="Nuevo Centro de Costo"
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
            placeholder="Buscar centro de costo..."
            class="w-full pl-10"
          />
        </div>
        <Button
          icon="pi pi-refresh"
          severity="secondary"
          text
          rounded
          @click="loadCentrosCosto"
          :loading="loading"
          v-tooltip="'Actualizar'"
        />
      </div>

      <DataTable
        :value="filteredCentrosCosto"
        :loading="loading"
        paginator
        :rows="10"
        stripedRows
        emptyMessage="No hay centros de costo registrados"
        class="text-sm"
      >
        <Column field="codigo" header="Código" sortable style="width: 120px">
          <template #body="{ data }">
            <span class="font-mono font-medium text-amber-700">{{ data.codigo }}</span>
          </template>
        </Column>
        <Column field="nombre" header="Nombre" sortable style="min-width: 200px">
          <template #body="{ data }">
            <div>
              <p class="font-medium text-gray-800">{{ data.nombre }}</p>
              <p class="text-xs text-gray-500">{{ data.descripcion || 'Sin descripción' }}</p>
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
        <Column header="Acciones" style="width: 100px">
          <template #body="{ data }">
            <div class="flex gap-1">
              <Button
                icon="pi pi-pencil"
                severity="secondary"
                text
                rounded
                size="small"
                @click="editCentroCosto(data)"
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
      :header="isEditing ? 'Editar Centro de Costo' : 'Nuevo Centro de Costo'"
      modal
      :style="{ width: '500px' }"
      :closable="!submitting"
    >
      <div class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Código *</label>
          <InputText v-model="formData.codigo" class="w-full" placeholder="CC-001" />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Nombre *</label>
          <InputText v-model="formData.nombre" class="w-full" placeholder="Nombre del centro de costo" />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
          <Textarea v-model="formData.descripcion" rows="2" class="w-full" placeholder="Descripción (opcional)" />
        </div>

        <div class="flex items-center gap-2">
          <ToggleSwitch v-model="formData.activo" />
          <label class="text-sm text-gray-700">Centro de costo activo</label>
        </div>
      </div>

      <template #footer>
        <Button label="Cancelar" severity="secondary" @click="dialogVisible = false" :disabled="submitting" />
        <Button
          :label="isEditing ? 'Actualizar' : 'Guardar'"
          @click="saveCentroCosto"
          :loading="submitting"
          class="!bg-amber-600 !border-amber-600"
        />
      </template>
    </Dialog>

    <!-- Dialog Confirmar Eliminación -->
    <Dialog v-model:visible="deleteDialogVisible" header="Confirmar Eliminación" modal :style="{ width: '400px' }">
      <div class="flex items-center gap-4">
        <i class="pi pi-exclamation-triangle text-4xl text-amber-500"></i>
        <p>¿Está seguro que desea eliminar el centro de costo <strong>{{ selectedCentroCosto?.nombre }}</strong>?</p>
      </div>
      <template #footer>
        <Button label="Cancelar" severity="secondary" @click="deleteDialogVisible = false" />
        <Button label="Eliminar" severity="danger" @click="deleteCentroCosto" />
      </template>
    </Dialog>
  </div>
</template>
