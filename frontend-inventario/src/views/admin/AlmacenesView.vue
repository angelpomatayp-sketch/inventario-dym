<script setup>
import { ref, onMounted, computed } from 'vue'
import { useToast } from 'primevue/usetoast'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Dialog from 'primevue/dialog'
import Select from 'primevue/select'
import Tag from 'primevue/tag'
import ToggleSwitch from 'primevue/toggleswitch'
import Textarea from 'primevue/textarea'
import almacenesService from '@/services/almacenesService'
import usuariosService from '@/services/usuariosService'
import api from '@/services/api'

const toast = useToast()

// Estado
const almacenes = ref([])
const usuarios = ref([])
const centrosCosto = ref([])
const loading = ref(false)
const dialogVisible = ref(false)
const deleteDialogVisible = ref(false)
const isEditing = ref(false)
const selectedAlmacen = ref(null)
const searchQuery = ref('')
const filterTipo = ref(null)
const submitting = ref(false)

// Formulario
const formData = ref({
  codigo: '',
  nombre: '',
  ubicacion: '',
  tipo: null,
  centro_costo_id: null,
  responsable_id: null,
  activo: true
})

// Selects con objetos completos (fix PrimeVue 4)
const selectedTipo = ref(null)
const selectedCentroCosto = ref(null)
const selectedResponsable = ref(null)

// Opciones de tipo
const tiposAlmacen = almacenesService.getTipos()

// Computed - filtrar almacenes
const filteredAlmacenes = computed(() => {
  let result = almacenes.value

  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    result = result.filter(a =>
      a.codigo?.toLowerCase().includes(query) ||
      a.nombre?.toLowerCase().includes(query) ||
      a.ubicacion?.toLowerCase().includes(query)
    )
  }

  if (filterTipo.value) {
    result = result.filter(a => a.tipo === filterTipo.value)
  }

  return result
})

// Cargar datos iniciales
onMounted(async () => {
  await Promise.all([
    loadAlmacenes(),
    loadUsuarios(),
    loadCentrosCosto()
  ])
})

const loadAlmacenes = async () => {
  loading.value = true
  try {
    const response = await almacenesService.getAll()
    almacenes.value = response.data || []
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudieron cargar los almacenes', life: 3000 })
  } finally {
    loading.value = false
  }
}

const loadUsuarios = async () => {
  try {
    const response = await usuariosService.getAll()
    usuarios.value = (response.data || []).map(u => ({
      label: u.nombre,
      value: u.id
    }))
  } catch (error) {
    console.error('Error cargando usuarios:', error)
  }
}

const loadCentrosCosto = async () => {
  try {
    const response = await api.get('/administracion/centros-costo', { params: { all: true } })
    if (response.data.success && response.data.data) {
      centrosCosto.value = response.data.data.map(cc => ({
        label: `${cc.codigo} - ${cc.nombre}`,
        value: cc.id
      }))
    }
  } catch (error) {
    console.error('Error cargando centros de costo:', error)
  }
}

// Métodos
const getTipoSeverity = (tipo) => {
  const severities = {
    'PRINCIPAL': 'info',
    'CAMPAMENTO': 'warn',
    'SATELITE': 'secondary'
  }
  return severities[tipo] || 'secondary'
}

const getTipoLabel = (tipo) => {
  const labels = {
    'PRINCIPAL': 'Principal',
    'CAMPAMENTO': 'Campamento',
    'SATELITE': 'Satélite'
  }
  return labels[tipo] || tipo
}

const openNew = () => {
  formData.value = {
    codigo: '',
    nombre: '',
    ubicacion: '',
    tipo: null,
    centro_costo_id: null,
    responsable_id: null,
    activo: true
  }
  selectedTipo.value = null
  selectedCentroCosto.value = null
  selectedResponsable.value = null
  isEditing.value = false
  dialogVisible.value = true
}

const editAlmacen = (almacen) => {
  formData.value = {
    id: almacen.id,
    codigo: almacen.codigo,
    nombre: almacen.nombre,
    ubicacion: almacen.ubicacion || '',
    tipo: almacen.tipo,
    centro_costo_id: almacen.centro_costo_id,
    responsable_id: almacen.responsable_id,
    activo: almacen.activo
  }
  selectedTipo.value = tiposAlmacen.find(t => t.value === almacen.tipo) || null
  selectedCentroCosto.value = centrosCosto.value.find(cc => cc.value == almacen.centro_costo_id) || null
  selectedResponsable.value = usuarios.value.find(u => u.value == almacen.responsable_id) || null
  isEditing.value = true
  dialogVisible.value = true
}

const confirmDelete = (almacen) => {
  selectedAlmacen.value = almacen
  deleteDialogVisible.value = true
}

const saveAlmacen = async () => {
  submitting.value = true
  try {
    const dataToSend = {
      ...formData.value,
      tipo: selectedTipo.value?.value || null,
      centro_costo_id: selectedCentroCosto.value?.value || null,
      responsable_id: selectedResponsable.value?.value || null
    }

    if (isEditing.value) {
      await almacenesService.update(formData.value.id, dataToSend)
      toast.add({ severity: 'success', summary: 'Éxito', detail: 'Almacén actualizado correctamente', life: 3000 })
    } else {
      await almacenesService.create(dataToSend)
      toast.add({ severity: 'success', summary: 'Éxito', detail: 'Almacén creado correctamente', life: 3000 })
    }
    dialogVisible.value = false
    await loadAlmacenes()
  } catch (error) {
    const message = error.response?.data?.message || 'Error al guardar el almacén'
    toast.add({ severity: 'error', summary: 'Error', detail: message, life: 5000 })
  } finally {
    submitting.value = false
  }
}

const deleteAlmacen = async () => {
  try {
    await almacenesService.delete(selectedAlmacen.value.id)
    toast.add({ severity: 'success', summary: 'Éxito', detail: 'Almacén eliminado correctamente', life: 3000 })
    deleteDialogVisible.value = false
    await loadAlmacenes()
  } catch (error) {
    const message = error.response?.data?.message || 'Error al eliminar el almacén'
    toast.add({ severity: 'error', summary: 'Error', detail: message, life: 5000 })
  }
}
</script>

<template>
  <div class="space-y-4">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold text-gray-800">Almacenes</h2>
        <p class="text-gray-500 text-sm">Gestión de almacenes y ubicaciones</p>
      </div>
      <Button
        label="Nuevo Almacén"
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
            placeholder="Buscar almacén..."
            class="w-full pl-10"
          />
        </div>
        <Select
          v-model="filterTipo"
          :options="tiposAlmacen"
          optionLabel="label"
          optionValue="value"
          placeholder="Filtrar por tipo"
          class="w-48"
          showClear
        >
          <template #value="slotProps">
            <span v-if="slotProps.value">{{ tiposAlmacen.find(t => t.value === slotProps.value)?.label }}</span>
            <span v-else class="text-gray-400">{{ slotProps.placeholder }}</span>
          </template>
        </Select>
        <Button
          icon="pi pi-refresh"
          severity="secondary"
          text
          rounded
          @click="loadAlmacenes"
          :loading="loading"
          v-tooltip="'Actualizar'"
        />
      </div>

      <DataTable
        :value="filteredAlmacenes"
        :loading="loading"
        paginator
        :rows="10"
        stripedRows
        emptyMessage="No hay almacenes registrados"
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
              <p class="text-xs text-gray-500">{{ data.ubicacion || 'Sin ubicación' }}</p>
            </div>
          </template>
        </Column>
        <Column field="tipo" header="Tipo" sortable style="width: 130px">
          <template #body="{ data }">
            <Tag :value="getTipoLabel(data.tipo)" :severity="getTipoSeverity(data.tipo)" />
          </template>
        </Column>
        <Column header="Proyecto" style="min-width: 150px">
          <template #body="{ data }">
            <span v-if="data.centro_costo" class="text-gray-700">
              {{ data.centro_costo.codigo }} - {{ data.centro_costo.nombre }}
            </span>
            <span v-else class="text-gray-400">Sin proyecto</span>
          </template>
        </Column>
        <Column header="Responsable" style="min-width: 150px">
          <template #body="{ data }">
            <span v-if="data.responsable" class="text-gray-700">{{ data.responsable.nombre }}</span>
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
                @click="editAlmacen(data)"
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
      :header="isEditing ? 'Editar Almacén' : 'Nuevo Almacén'"
      modal
      :style="{ width: '500px' }"
      :closable="!submitting"
    >
      <div class="space-y-4">
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Código *</label>
            <InputText v-model="formData.codigo" class="w-full" placeholder="ALM-001" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Tipo *</label>
            <Select
              v-model="selectedTipo"
              :options="tiposAlmacen"
              optionLabel="label"
              placeholder="Seleccione"
              class="w-full"
            >
              <template #value="slotProps">
                <span v-if="slotProps.value">{{ slotProps.value.label }}</span>
                <span v-else class="text-gray-400">{{ slotProps.placeholder }}</span>
              </template>
            </Select>
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Nombre *</label>
          <InputText v-model="formData.nombre" class="w-full" placeholder="Nombre del almacén" />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Proyecto (Centro de Costo)</label>
          <Select
            v-model="selectedCentroCosto"
            :options="centrosCosto"
            optionLabel="label"
            placeholder="Seleccione proyecto"
            class="w-full"
            showClear
            filter
          >
            <template #value="slotProps">
              <span v-if="slotProps.value">{{ slotProps.value.label }}</span>
              <span v-else class="text-gray-400">{{ slotProps.placeholder }}</span>
            </template>
          </Select>
          <p class="text-xs text-gray-500 mt-1">Asignar el almacén a un proyecto específico</p>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Ubicación</label>
          <Textarea v-model="formData.ubicacion" rows="2" class="w-full" placeholder="Descripción de la ubicación física" />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Responsable</label>
          <Select
            v-model="selectedResponsable"
            :options="usuarios"
            optionLabel="label"
            placeholder="Seleccione responsable"
            class="w-full"
            showClear
          >
            <template #value="slotProps">
              <span v-if="slotProps.value">{{ slotProps.value.label }}</span>
              <span v-else class="text-gray-400">{{ slotProps.placeholder }}</span>
            </template>
          </Select>
        </div>

        <div class="flex items-center gap-2">
          <ToggleSwitch v-model="formData.activo" />
          <label class="text-sm text-gray-700">Almacén activo</label>
        </div>
      </div>

      <template #footer>
        <Button label="Cancelar" severity="secondary" @click="dialogVisible = false" :disabled="submitting" />
        <Button
          :label="isEditing ? 'Actualizar' : 'Guardar'"
          @click="saveAlmacen"
          :loading="submitting"
          class="!bg-amber-600 !border-amber-600"
        />
      </template>
    </Dialog>

    <!-- Dialog Confirmar Eliminación -->
    <Dialog v-model:visible="deleteDialogVisible" header="Confirmar Eliminación" modal :style="{ width: '400px' }">
      <div class="flex items-center gap-4">
        <i class="pi pi-exclamation-triangle text-4xl text-amber-500"></i>
        <p>¿Está seguro que desea eliminar el almacén <strong>{{ selectedAlmacen?.nombre }}</strong>?</p>
      </div>
      <template #footer>
        <Button label="Cancelar" severity="secondary" @click="deleteDialogVisible = false" />
        <Button label="Eliminar" severity="danger" @click="deleteAlmacen" />
      </template>
    </Dialog>
  </div>
</template>
