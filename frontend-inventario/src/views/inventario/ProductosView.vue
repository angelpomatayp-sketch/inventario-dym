<script setup>
import { ref, computed, onMounted } from 'vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Dialog from 'primevue/dialog'
import Select from 'primevue/select'
import Tag from 'primevue/tag'
import ToggleSwitch from 'primevue/toggleswitch'
import Textarea from 'primevue/textarea'
import Tabs from 'primevue/tabs'
import TabList from 'primevue/tablist'
import Tab from 'primevue/tab'
import TabPanels from 'primevue/tabpanels'
import TabPanel from 'primevue/tabpanel'
import FileUpload from 'primevue/fileupload'
import Image from 'primevue/image'
import ProgressBar from 'primevue/progressbar'
import Galleria from 'primevue/galleria'
import Toast from 'primevue/toast'
import { useToast } from 'primevue/usetoast'
import { useAuthStore } from '@/stores/auth'
import productosService from '@/services/productosService'
import api from '@/services/api'

const toast = useToast()
const authStore = useAuthStore()
const almacenAsignado = computed(() => authStore.user?.almacen_id || null)
const esAlmacenero = computed(() => (authStore.user?.roles || []).includes('almacenero'))

// Estado
const productos = ref([])

const loading = ref(false)
const dialogVisible = ref(false)
const deleteDialogVisible = ref(false)
const viewDialogVisible = ref(false)
const isEditing = ref(false)
const selectedProducto = ref(null)
const searchQuery = ref('')
const selectedFamilia = ref(null)
const showLowStock = ref(false)

// Estado para imágenes
const productImages = ref([])
const uploadProgress = ref(0)
const isUploading = ref(false)
const MAX_IMAGES = 4

// Estado para galería fullscreen
const galleryVisible = ref(false)
const galleryActiveIndex = ref(0)

// Formulario
const formData = ref({
  codigo: '',
  nombre: '',
  descripcion: '',
  familia: null,
  unidad: null,
  stockMinimo: 0,
  stockMaximo: 0,
  ubicacionFisica: '',
  marca: '',
  modelo: '',
  activo: true,
  // Campos EPP (solo se muestran si la familia es EPP)
  vida_util_dias: 365,
  dias_alerta_vencimiento: 30,
  requiere_talla: false,
  tallas_disponibles: ''
})

// Opciones (se cargan desde API)
const familias = ref([])
const unidades = ref([])

// Cargar datos desde API
const loadProductos = async () => {
  loading.value = true
  try {
    const response = await api.get('/inventario/productos')
    if (response.data.success) {
      // La API paginada devuelve items directamente en response.data.data
      const items = response.data.data || []
      // Transformar datos para la tabla
      productos.value = items.map(p => {
        const stockPorAlmacen = p.stock_por_almacen || []
        const stockEnAlmacenAsignado = (esAlmacenero.value && almacenAsignado.value)
          ? stockPorAlmacen.find(s => Number(s.almacen_id) === Number(almacenAsignado.value))
          : null
        const tieneStockEnAlmacen = esAlmacenero.value
          ? !!stockEnAlmacenAsignado
          : stockPorAlmacen.length > 0
        const stockActual = esAlmacenero.value
          ? Number(stockEnAlmacenAsignado?.stock_actual || 0)
          : Number(p.stock_total || 0)

        return {
        id: p.id,
        codigo: p.codigo,
        nombre: p.nombre,
        descripcion: p.descripcion,
        familia: p.familia?.nombre || 'Sin familia',
        familia_id: p.familia?.id,
        familia_es_epp: p.familia?.es_epp || false,
        unidad: p.unidad_medida,
        stockActual,
        stockMinimo: p.stock_minimo,
        stockMaximo: p.stock_maximo,
        precioPromedio: p.costo_promedio || 0,
        almacen: stockPorAlmacen[0]?.almacen_nombre || 'Sin almacén',
        tieneStockEnAlmacen,
        marca: p.marca,
        modelo: p.modelo,
        ubicacionFisica: p.ubicacion_fisica,
        activo: p.activo,
        // Campos EPP
        vida_util_dias: p.vida_util_dias,
        dias_alerta_vencimiento: p.dias_alerta_vencimiento,
        requiere_talla: p.requiere_talla,
        tallas_disponibles: p.tallas_disponibles
      }})
    }
  } catch (err) {
    console.error('Error al cargar productos:', err)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'No se pudieron cargar los productos',
      life: 5000
    })
  } finally {
    loading.value = false
  }
}

const loadFamilias = async () => {
  try {
    // Usar all=true para obtener todas las familias sin paginación
    const response = await api.get('/inventario/familias', { params: { all: true } })
    if (response.data.success && response.data.data) {
      familias.value = response.data.data.map(f => ({
        label: f.nombre,
        value: f.id,
        es_epp: f.es_epp,
        categoria_epp: f.categoria_epp
      }))
    }
  } catch (err) {
    console.error('Error al cargar familias:', err)
  }
}

const loadUnidades = async () => {
  try {
    const response = await api.get('/inventario/unidades')
    if (response.data.success && response.data.data) {
      unidades.value = response.data.data.map(u => ({
        label: `${u.nombre} (${u.codigo})`,
        value: u.codigo
      }))
    }
  } catch (err) {
    console.error('Error al cargar unidades:', err)
  }
}

// Computed para verificar si la familia seleccionada es EPP
const familiaSeleccionadaEsEpp = computed(() => {
  if (!formData.value.familia) return false
  const familia = familias.value.find(f => f.value === formData.value.familia)
  return familia?.es_epp || false
})

const categoriaEppSeleccionada = computed(() => {
  if (!formData.value.familia) return null
  const familia = familias.value.find(f => f.value === formData.value.familia)
  return familia?.categoria_epp || null
})

onMounted(() => {
  loadProductos()
  loadFamilias()
  loadUnidades()
})

// Computed
const filteredProductos = computed(() => {
  let result = productos.value

  // Filtrar por búsqueda (código o nombre)
  if (searchQuery.value) {
    const search = searchQuery.value.toLowerCase().trim()
    result = result.filter(p =>
      p.codigo.toLowerCase().includes(search) ||
      p.nombre.toLowerCase().includes(search)
    )
  }

  // Filtrar por familia (comparar por ID)
  if (selectedFamilia.value) {
    result = result.filter(p => p.familia_id === selectedFamilia.value)
  }

  // Filtrar por stock bajo
  if (showLowStock.value) {
    result = result.filter(p => p.tieneStockEnAlmacen && p.stockActual <= p.stockMinimo)
  }

  return result
})

const stockBajoCount = computed(() => {
  return productos.value.filter(p => p.tieneStockEnAlmacen && p.stockActual <= p.stockMinimo && p.activo).length
})

// Métodos
const getStockSeverity = (producto) => {
  if (producto.stockActual === 0) return 'danger'
  if (producto.stockActual <= producto.stockMinimo) return 'warn'
  return 'success'
}

const getStockLabel = (producto) => {
  if (producto.stockActual === 0) return 'Sin Stock'
  if (producto.stockActual <= producto.stockMinimo) return 'Stock Bajo'
  return 'Normal'
}

const formatCurrency = (value) => {
  return `S/ ${value.toFixed(2)}`
}

const openNew = () => {
  formData.value = {
    codigo: '',
    nombre: '',
    descripcion: '',
    familia: null,
    unidad: null,
    stockMinimo: 0,
    stockMaximo: 0,
    ubicacionFisica: '',
    marca: '',
    modelo: '',
    activo: true,
    // Campos EPP con valores por defecto
    vida_util_dias: 365,
    dias_alerta_vencimiento: 30,
    requiere_talla: false,
    tallas_disponibles: ''
  }
  isEditing.value = false
  dialogVisible.value = true
}

const editProducto = (producto) => {
  formData.value = {
    id: producto.id,
    codigo: producto.codigo,
    nombre: producto.nombre,
    descripcion: producto.descripcion || '',
    familia: producto.familia_id,  // Usar familia_id para el Dropdown
    unidad: producto.unidad,
    stockMinimo: producto.stockMinimo,
    stockMaximo: producto.stockMaximo || 0,
    ubicacionFisica: producto.ubicacionFisica || '',
    marca: producto.marca || '',
    modelo: producto.modelo || '',
    activo: producto.activo,
    // Campos EPP
    vida_util_dias: producto.vida_util_dias || 365,
    dias_alerta_vencimiento: producto.dias_alerta_vencimiento || 30,
    requiere_talla: producto.requiere_talla || false,
    tallas_disponibles: producto.tallas_disponibles || ''
  }
  isEditing.value = true
  dialogVisible.value = true
}

const viewProducto = async (producto) => {
  selectedProducto.value = producto
  viewDialogVisible.value = true
  productImages.value = []

  // Cargar imágenes del producto desde API
  try {
    const response = await productosService.getImagenes(producto.id)
    if (response.success) {
      productImages.value = response.data || []
    }
  } catch (err) {
    console.error('Error al cargar imágenes:', err)
    // Si falla la API, mostrar array vacío
    productImages.value = []
  }
}

const onImageSelect = async (event) => {
  const files = Array.from(event.files)
  const remainingSlots = MAX_IMAGES - productImages.value.length

  if (files.length > remainingSlots) {
    toast.add({
      severity: 'warn',
      summary: 'Límite de imágenes',
      detail: `Solo puede agregar ${remainingSlots} imagen(es) más. Máximo ${MAX_IMAGES} imágenes por producto.`,
      life: 3000
    })
    return
  }

  isUploading.value = true
  uploadProgress.value = 0

  try {
    // Simular progreso visual
    const progressInterval = setInterval(() => {
      if (uploadProgress.value < 90) {
        uploadProgress.value += 10
      }
    }, 100)

    const response = await productosService.subirImagenes(selectedProducto.value.id, files)

    clearInterval(progressInterval)
    uploadProgress.value = 100

    if (response.success) {
      // Recargar imágenes del producto
      const imagenesResponse = await productosService.getImagenes(selectedProducto.value.id)
      if (imagenesResponse.success) {
        productImages.value = imagenesResponse.data || []
      }

      toast.add({
        severity: 'success',
        summary: 'Imágenes subidas',
        detail: 'Las imágenes se subieron correctamente',
        life: 3000
      })
    }
  } catch (err) {
    console.error('Error al subir imágenes:', err)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: err.response?.data?.message || 'Error al subir las imágenes',
      life: 5000
    })
  } finally {
    isUploading.value = false
    uploadProgress.value = 0
  }
}

const removeImage = async (index) => {
  const imagen = productImages.value[index]
  if (!imagen) return

  try {
    const response = await productosService.eliminarImagen(selectedProducto.value.id, imagen.id)

    if (response.success) {
      productImages.value.splice(index, 1)
      toast.add({
        severity: 'success',
        summary: 'Imagen eliminada',
        detail: 'La imagen se eliminó correctamente',
        life: 3000
      })
    }
  } catch (err) {
    console.error('Error al eliminar imagen:', err)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: err.response?.data?.message || 'Error al eliminar la imagen',
      life: 5000
    })
  }
}

// Manejar error al cargar imagen
const handleImageError = (event, imagen) => {
  console.error('Error al cargar imagen:', imagen.url)
  imagen.loadError = true
}

// Abrir galería en pantalla completa
const openGallery = (index) => {
  galleryActiveIndex.value = index
  galleryVisible.value = true
  // Agregar listener de teclado
  document.addEventListener('keydown', handleGalleryKeydown)
}

// Cerrar galería
const closeGallery = () => {
  galleryVisible.value = false
  document.removeEventListener('keydown', handleGalleryKeydown)
}

// Manejar teclas en la galería
const handleGalleryKeydown = (e) => {
  if (!galleryVisible.value) return

  switch (e.key) {
    case 'Escape':
      closeGallery()
      break
    case 'ArrowLeft':
      galleryActiveIndex.value = (galleryActiveIndex.value - 1 + productImages.value.length) % productImages.value.length
      break
    case 'ArrowRight':
      galleryActiveIndex.value = (galleryActiveIndex.value + 1) % productImages.value.length
      break
  }
}

// Abrir selector de archivo para reemplazar
const triggerReplace = (imagenId) => {
  document.getElementById('replace-' + imagenId)?.click()
}

// Reemplazar imagen
const replaceImage = async (event, index) => {
  const file = event.target.files?.[0]
  if (!file) return

  const imagen = productImages.value[index]
  if (!imagen) return

  isUploading.value = true
  uploadProgress.value = 0

  try {
    // Primero eliminar la imagen actual
    await productosService.eliminarImagen(selectedProducto.value.id, imagen.id)

    // Subir la nueva
    const progressInterval = setInterval(() => {
      if (uploadProgress.value < 90) uploadProgress.value += 10
    }, 100)

    const response = await productosService.subirImagenes(selectedProducto.value.id, [file])

    clearInterval(progressInterval)
    uploadProgress.value = 100

    if (response.success) {
      // Recargar imágenes
      const imagenesResponse = await productosService.getImagenes(selectedProducto.value.id)
      if (imagenesResponse.success) {
        productImages.value = imagenesResponse.data || []
      }
      toast.add({
        severity: 'success',
        summary: 'Imagen reemplazada',
        detail: 'La imagen se reemplazó correctamente',
        life: 3000
      })
    }
  } catch (err) {
    console.error('Error al reemplazar imagen:', err)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: err.response?.data?.message || 'Error al reemplazar la imagen',
      life: 5000
    })
  } finally {
    isUploading.value = false
    uploadProgress.value = 0
    event.target.value = '' // Reset input
  }
}

const canAddMoreImages = computed(() => productImages.value.length < MAX_IMAGES)

const confirmDelete = (producto) => {
  selectedProducto.value = producto
  deleteDialogVisible.value = true
}

const saveProducto = async () => {
  // Validaciones básicas
  if (!formData.value.nombre || !formData.value.familia || !formData.value.unidad) {
    toast.add({
      severity: 'warn',
      summary: 'Campos requeridos',
      detail: 'Complete todos los campos obligatorios (nombre, familia, unidad)',
      life: 5000
    })
    return
  }

  loading.value = true
  try {
    // Preparar datos para la API (convertir nombres de campos)
    const dataToSend = {
      nombre: formData.value.nombre,
      descripcion: formData.value.descripcion || null,
      familia_id: formData.value.familia,
      unidad_medida: formData.value.unidad,
      stock_minimo: formData.value.stockMinimo || 0,
      stock_maximo: formData.value.stockMaximo || 0,
      ubicacion_fisica: formData.value.ubicacionFisica || null,
      marca: formData.value.marca || null,
      modelo: formData.value.modelo || null,
      activo: formData.value.activo,
      // Campos EPP (solo si la familia es EPP)
      vida_util_dias: familiaSeleccionadaEsEpp.value ? formData.value.vida_util_dias : null,
      dias_alerta_vencimiento: familiaSeleccionadaEsEpp.value ? formData.value.dias_alerta_vencimiento : null,
      requiere_talla: familiaSeleccionadaEsEpp.value ? formData.value.requiere_talla : false,
      tallas_disponibles: familiaSeleccionadaEsEpp.value ? formData.value.tallas_disponibles : null
    }
    if (isEditing.value) {
      dataToSend.codigo = formData.value.codigo
    }

    let response
    if (isEditing.value) {
      // Actualizar producto existente
      response = await api.put(`/inventario/productos/${formData.value.id}`, dataToSend)
    } else {
      // Crear nuevo producto
      response = await api.post('/inventario/productos', dataToSend)
    }

    if (response.data.success) {
      toast.add({
        severity: 'success',
        summary: isEditing.value ? 'Producto actualizado' : 'Producto creado',
        detail: `El producto "${formData.value.nombre}" se ${isEditing.value ? 'actualizó' : 'creó'} correctamente`,
        life: 3000
      })
      dialogVisible.value = false
      // Recargar lista de productos
      await loadProductos()
    } else {
      throw new Error(response.data.message || 'Error al guardar')
    }
  } catch (err) {
    console.error('Error al guardar producto:', err)
    const errorMessage = err.response?.data?.message || err.message || 'Error al guardar el producto'
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: errorMessage,
      life: 5000
    })
  } finally {
    loading.value = false
  }
}

const deleteProducto = async () => {
  if (!selectedProducto.value) return

  loading.value = true
  try {
    const response = await api.delete(`/inventario/productos/${selectedProducto.value.id}`)

    if (response.data.success) {
      toast.add({
        severity: 'success',
        summary: 'Producto eliminado',
        detail: `El producto "${selectedProducto.value.nombre}" se eliminó correctamente`,
        life: 3000
      })
      deleteDialogVisible.value = false
      // Recargar lista de productos
      await loadProductos()
    } else {
      throw new Error(response.data.message || 'Error al eliminar')
    }
  } catch (err) {
    console.error('Error al eliminar producto:', err)
    const errorMessage = err.response?.data?.message || err.message || 'Error al eliminar el producto'
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: errorMessage,
      life: 5000
    })
  } finally {
    loading.value = false
    selectedProducto.value = null
  }
}
</script>

<template>
  <div class="space-y-4">
    <Toast />

    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold text-gray-800">Productos</h2>
        <p class="text-gray-500 text-sm">Catálogo de productos e insumos</p>
      </div>
      <div class="flex gap-2">
        <Button
          label="Exportar"
          icon="pi pi-download"
          severity="secondary"
          outlined
        />
        <Button
          label="Nuevo Producto"
          icon="pi pi-plus"
          @click="openNew"
          class="!bg-amber-600 !border-amber-600 hover:!bg-amber-700"
        />
      </div>
    </div>

    <!-- Cards de resumen -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <div class="bg-white rounded-lg p-4 shadow-sm border-l-4 border-gray-400">
        <p class="text-sm text-gray-500">Total Productos</p>
        <p class="text-2xl font-bold text-gray-800">{{ productos.length }}</p>
      </div>
      <div class="bg-white rounded-lg p-4 shadow-sm border-l-4 border-gray-400">
        <p class="text-sm text-gray-500">Productos Activos</p>
        <p class="text-2xl font-bold text-gray-800">{{ productos.filter(p => p.activo).length }}</p>
      </div>
      <div class="bg-white rounded-lg p-4 shadow-sm border-l-4 border-amber-500">
        <p class="text-sm text-gray-500">Stock Bajo</p>
        <p class="text-2xl font-bold text-amber-600">{{ stockBajoCount }}</p>
      </div>
      <div class="bg-white rounded-lg p-4 shadow-sm border-l-4 border-gray-400">
        <p class="text-sm text-gray-500">Sin Stock</p>
        <p class="text-2xl font-bold text-gray-700">{{ productos.filter(p => p.tieneStockEnAlmacen && p.stockActual === 0).length }}</p>
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
            placeholder="Buscar por código o nombre..."
            class="w-full pl-10"
          />
        </div>
        <Select
          v-model="selectedFamilia"
          :options="familias"
          optionLabel="label"
          optionValue="value"
          placeholder="Todas las familias"
          class="w-48"
          showClear
        >
          <template #value="slotProps">
            <span v-if="slotProps.value">{{ familias.find(f => f.value === slotProps.value)?.label }}</span>
            <span v-else class="text-gray-400">{{ slotProps.placeholder }}</span>
          </template>
        </Select>
        <div class="flex items-center gap-2">
          <ToggleSwitch v-model="showLowStock" />
          <span class="text-sm text-gray-600">Solo stock bajo</span>
        </div>
      </div>

      <DataTable
        :value="filteredProductos"
        :loading="loading"
        paginator
        :rows="10"
        stripedRows
        :rowClass="(data) => data.tieneStockEnAlmacen && data.stockActual <= data.stockMinimo ? 'bg-amber-50' : ''"
      >
        <Column field="codigo" header="Código" sortable style="width: 100px">
          <template #body="{ data }">
            <span class="font-mono text-sm font-medium text-gray-700">{{ data.codigo }}</span>
          </template>
        </Column>
        <Column field="nombre" header="Producto" sortable>
          <template #body="{ data }">
            <div>
              <p class="font-medium text-gray-800">{{ data.nombre }}</p>
              <p v-if="esAlmacenero" class="text-xs text-gray-500">{{ data.familia }}</p>
              <p v-else class="text-xs text-gray-500">{{ data.familia }} • {{ data.almacen }}</p>
            </div>
          </template>
        </Column>
        <Column field="unidad" header="Unidad" style="width: 80px" />
        <Column field="stockActual" header="Stock" sortable style="width: 120px">
          <template #body="{ data }">
            <div class="text-center">
              <p class="font-bold text-gray-800">{{ data.tieneStockEnAlmacen ? data.stockActual : '*' }}</p>
              <Tag
                v-if="data.tieneStockEnAlmacen"
                :value="getStockLabel(data)"
                :severity="getStockSeverity(data)"
                class="text-xs"
              />
            </div>
          </template>
        </Column>
        <Column field="stockMinimo" header="Mín." style="width: 70px" class="text-center" />
        <Column field="precioPromedio" header="Precio Prom." sortable style="width: 120px">
          <template #body="{ data }">
            <span class="font-medium">{{ formatCurrency(data.precioPromedio) }}</span>
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
        <Column header="Acciones" style="width: 130px">
          <template #body="{ data }">
            <div class="flex gap-1">
              <Button icon="pi pi-eye" severity="info" text rounded @click="viewProducto(data)" />
              <Button icon="pi pi-pencil" severity="secondary" text rounded @click="editProducto(data)" />
              <Button icon="pi pi-trash" severity="danger" text rounded @click="confirmDelete(data)" />
            </div>
          </template>
        </Column>
      </DataTable>
    </div>

    <!-- Dialog Crear/Editar -->
    <Dialog
      v-model:visible="dialogVisible"
      :header="isEditing ? 'Editar Producto' : 'Nuevo Producto'"
      modal
      :style="{ width: '650px', maxHeight: '90vh' }"
      :contentStyle="{ overflow: 'auto' }"
    >
      <Tabs value="0">
        <TabList>
          <Tab value="0">Información General</Tab>
          <Tab value="1">Ubicación</Tab>
        </TabList>
        <TabPanels>
          <TabPanel value="0">
          <div class="space-y-4 pt-2">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ isEditing ? 'Código *' : 'Código' }}</label>
                <InputText v-if="isEditing" v-model="formData.codigo" class="w-full" placeholder="PRD-001" />
                <InputText v-else modelValue="Se genera automáticamente al guardar" class="w-full" disabled />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Familia *</label>
                <Select
                  v-model="formData.familia"
                  :options="familias"
                  optionLabel="label"
                  optionValue="value"
                  placeholder="Seleccione"
                  class="w-full"
                  appendTo="body"
                >
                  <template #value="slotProps">
                    <span v-if="slotProps.value">{{ familias.find(f => f.value === slotProps.value)?.label }}</span>
                    <span v-else class="text-gray-400">{{ slotProps.placeholder }}</span>
                  </template>
                </Select>
              </div>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Nombre del producto *</label>
              <InputText v-model="formData.nombre" class="w-full" placeholder="Nombre descriptivo del producto" />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
              <Textarea v-model="formData.descripcion" rows="2" class="w-full" placeholder="Descripción detallada (opcional)" />
            </div>

            <div class="grid grid-cols-3 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Unidad *</label>
                <Select
                  v-model="formData.unidad"
                  :options="unidades"
                  optionLabel="label"
                  optionValue="value"
                  placeholder="Seleccione"
                  class="w-full"
                  appendTo="body"
                >
                  <template #value="slotProps">
                    <span v-if="slotProps.value">{{ unidades.find(u => u.value === slotProps.value)?.label }}</span>
                    <span v-else class="text-gray-400">{{ slotProps.placeholder }}</span>
                  </template>
                </Select>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Marca</label>
                <InputText v-model="formData.marca" class="w-full" placeholder="Ej: 3M, CAT, Stanley" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Modelo</label>
                <InputText v-model="formData.modelo" class="w-full" placeholder="Modelo o referencia" />
              </div>
            </div>

            <div class="grid grid-cols-3 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Stock Mínimo</label>
                <InputNumber v-model="formData.stockMinimo" class="w-full" :min="0" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Stock Máximo</label>
                <InputNumber v-model="formData.stockMaximo" class="w-full" :min="0" />
              </div>
              <div></div>
            </div>

            <!-- Campos EPP (solo si la familia es EPP) -->
            <div v-if="familiaSeleccionadaEsEpp" class="border rounded-lg p-4 bg-amber-50 mt-4">
              <div class="flex items-center gap-2 mb-3">
                <i class="pi pi-shield text-amber-600"></i>
                <span class="font-medium text-amber-800">Configuración de EPP</span>
                <Tag :value="categoriaEppSeleccionada" severity="warn" class="ml-2" />
              </div>

              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Vida útil (días) *</label>
                  <InputNumber v-model="formData.vida_util_dias" class="w-full" :min="1" />
                  <p class="text-xs text-gray-500 mt-1">Duración estimada del EPP desde su entrega</p>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Días para alerta *</label>
                  <InputNumber v-model="formData.dias_alerta_vencimiento" class="w-full" :min="1" />
                  <p class="text-xs text-gray-500 mt-1">Días antes de vencer para generar alerta</p>
                </div>
              </div>

              <div class="mt-4">
                <div class="flex items-center gap-2">
                  <ToggleSwitch v-model="formData.requiere_talla" />
                  <label class="text-sm text-gray-700">Este EPP requiere talla</label>
                </div>
              </div>

              <div v-if="formData.requiere_talla" class="mt-3">
                <label class="block text-sm font-medium text-gray-700 mb-1">Tallas disponibles</label>
                <InputText v-model="formData.tallas_disponibles" class="w-full" placeholder="Ej: S, M, L, XL o 38, 39, 40, 41" />
                <p class="text-xs text-gray-500 mt-1">Separar tallas con comas</p>
              </div>
            </div>
          </div>
          </TabPanel>

          <TabPanel value="1">
          <div class="space-y-4 pt-2">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Ubicación física en almacén</label>
              <InputText v-model="formData.ubicacionFisica" class="w-full" placeholder="Ej: Estante A - Fila 3 - Posición 5" />
            </div>

            <div class="flex items-center gap-2">
              <ToggleSwitch v-model="formData.activo" />
              <label class="text-sm text-gray-700">Producto activo</label>
            </div>
          </div>
          </TabPanel>
        </TabPanels>
      </Tabs>

      <template #footer>
        <Button label="Cancelar" severity="secondary" @click="dialogVisible = false" />
        <Button :label="isEditing ? 'Actualizar' : 'Guardar'" @click="saveProducto" class="!bg-amber-600 !border-amber-600" />
      </template>
    </Dialog>

    <!-- Dialog Confirmar Eliminación -->
    <Dialog v-model:visible="deleteDialogVisible" header="Confirmar Eliminación" modal :style="{ width: '400px' }">
      <div class="flex items-center gap-4">
        <i class="pi pi-exclamation-triangle text-4xl text-amber-500"></i>
        <p>¿Está seguro que desea eliminar el producto <strong>{{ selectedProducto?.nombre }}</strong>?</p>
      </div>
      <template #footer>
        <Button label="Cancelar" severity="secondary" @click="deleteDialogVisible = false" />
        <Button label="Eliminar" severity="danger" @click="deleteProducto" />
      </template>
    </Dialog>

    <!-- Dialog Ver Producto con Imágenes -->
    <Dialog
      v-model:visible="viewDialogVisible"
      header="Detalle del Producto"
      modal
      :style="{ width: '750px', maxHeight: '90vh' }"
      :contentStyle="{ overflow: 'auto', maxHeight: 'calc(90vh - 120px)' }"
    >
      <div v-if="selectedProducto" class="space-y-6">
        <!-- Información del producto -->
        <div class="grid grid-cols-2 gap-4 bg-gray-50 p-4 rounded-lg">
          <div>
            <p class="text-sm text-gray-500">Código</p>
            <p class="font-mono font-medium text-gray-800">{{ selectedProducto.codigo }}</p>
          </div>
          <div>
            <p class="text-sm text-gray-500">Familia</p>
            <p class="font-medium text-gray-800">{{ selectedProducto.familia }}</p>
          </div>
          <div class="col-span-2">
            <p class="text-sm text-gray-500">Nombre</p>
            <p class="font-semibold text-gray-800">{{ selectedProducto.nombre }}</p>
          </div>
          <div>
            <p class="text-sm text-gray-500">Stock Actual</p>
            <p class="font-bold text-xl" :class="selectedProducto.stockActual <= selectedProducto.stockMinimo ? 'text-amber-600' : 'text-gray-800'">
              {{ selectedProducto.stockActual }} {{ selectedProducto.unidad }}
            </p>
          </div>
          <div>
            <p class="text-sm text-gray-500">Precio Promedio</p>
            <p class="font-bold text-xl text-gray-800">{{ formatCurrency(selectedProducto.precioPromedio) }}</p>
          </div>
        </div>

        <!-- Sección de imágenes -->
        <div class="border-t pt-4">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-800">
              <i class="pi pi-images mr-2"></i>
              Imágenes del Producto
            </h3>
            <span class="text-sm text-gray-500">{{ productImages.length }}/{{ MAX_IMAGES }} imágenes</span>
          </div>

          <!-- Galería de imágenes - Grid 2x2 uniforme -->
          <div v-if="productImages.length > 0" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; margin-bottom: 16px;">
            <div
              v-for="(imagen, index) in productImages"
              :key="imagen.id"
              class="border rounded-lg overflow-hidden bg-white shadow-sm"
            >
              <!-- Contenedor con altura fija para uniformidad - Clickeable para ver en grande -->
              <div
                class="bg-gray-50 flex items-center justify-center cursor-pointer hover:bg-gray-100 transition-colors relative group"
                style="height: 140px;"
                @click="openGallery(index)"
              >
                <img
                  :src="imagen.url"
                  :alt="imagen.nombre"
                  class="max-w-full max-h-full object-contain"
                  style="max-height: 130px;"
                  @error="handleImageError($event, imagen)"
                />
                <!-- Overlay con icono de zoom -->
                <div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 flex items-center justify-center transition-all">
                  <i class="pi pi-search-plus text-white text-2xl opacity-0 group-hover:opacity-100 transition-opacity"></i>
                </div>
                <!-- Indicador si la imagen no carga -->
                <div v-if="imagen.loadError" class="absolute inset-0 flex items-center justify-center bg-gray-100">
                  <div class="text-center text-gray-400">
                    <i class="pi pi-image text-xl"></i>
                    <p class="text-xs mt-1">Error</p>
                  </div>
                </div>
              </div>
              <!-- Info y acciones -->
              <div class="p-2 border-t bg-gray-50">
                <p class="text-xs text-gray-600 truncate" :title="imagen.nombre">{{ imagen.nombre }}</p>
                <div class="flex gap-1 mt-1">
                  <Button
                    icon="pi pi-trash"
                    severity="danger"
                    size="small"
                    text
                    class="flex-1 !p-1 !text-xs"
                    @click="removeImage(index)"
                    v-tooltip.top="'Eliminar'"
                  />
                  <input
                    type="file"
                    :id="'replace-' + imagen.id"
                    accept="image/*"
                    class="hidden"
                    @change="replaceImage($event, index)"
                  />
                  <Button
                    icon="pi pi-sync"
                    severity="info"
                    size="small"
                    text
                    class="flex-1 !p-1 !text-xs"
                    @click="triggerReplace(imagen.id)"
                    v-tooltip.top="'Cambiar'"
                  />
                </div>
              </div>
            </div>
          </div>

          <!-- Mensaje sin imágenes -->
          <div v-else class="text-center py-8 bg-gray-50 rounded-lg border-2 border-dashed border-gray-300 mb-4">
            <i class="pi pi-image text-4xl text-gray-300 mb-2"></i>
            <p class="text-gray-500">No hay imágenes cargadas</p>
          </div>

          <!-- Progreso de carga -->
          <div v-if="isUploading" class="mb-4">
            <p class="text-sm text-gray-600 mb-1">Subiendo imágenes...</p>
            <ProgressBar :value="uploadProgress" :showValue="true" />
          </div>

          <!-- Zona de carga -->
          <div v-if="canAddMoreImages" class="border-2 border-dashed border-gray-300 rounded-lg p-4 hover:border-amber-400 transition-colors">
            <FileUpload
              mode="basic"
              name="imagenes[]"
              accept="image/*"
              :multiple="true"
              :maxFileSize="5000000"
              :auto="false"
              chooseLabel="Seleccionar Imágenes"
              class="w-full"
              @select="onImageSelect"
              :disabled="isUploading"
            />
            <p class="text-xs text-gray-500 mt-2 text-center">
              Formatos: JPG, PNG, GIF. Máximo 5MB por imagen. Puede agregar hasta {{ MAX_IMAGES - productImages.length }} imagen(es) más.
            </p>
          </div>

          <div v-else class="bg-amber-50 border border-amber-200 rounded-lg p-3 text-center">
            <i class="pi pi-info-circle text-amber-600 mr-2"></i>
            <span class="text-amber-700 text-sm">Ha alcanzado el límite máximo de {{ MAX_IMAGES }} imágenes</span>
          </div>
        </div>
      </div>

      <template #footer>
        <Button label="Cerrar" severity="secondary" @click="viewDialogVisible = false" />
        <Button label="Guardar Imágenes" icon="pi pi-save" @click="viewDialogVisible = false" class="!bg-amber-600 !border-amber-600" />
      </template>
    </Dialog>

    <!-- Galería Fullscreen - Teleport para evitar problemas de z-index -->
    <Teleport to="body">
      <div v-if="galleryVisible" class="gallery-fullscreen-overlay" @click.self="closeGallery">
        <div class="gallery-fullscreen-container">
          <!-- Botón cerrar -->
          <button
            class="gallery-close-btn"
            @click="closeGallery"
          >
            <i class="pi pi-times"></i>
          </button>

          <!-- Imagen principal -->
          <div class="gallery-main-image">
            <img
              v-if="productImages[galleryActiveIndex]"
              :src="productImages[galleryActiveIndex].url"
              :alt="productImages[galleryActiveIndex].nombre"
            />
          </div>

          <!-- Navegación -->
          <button
            v-if="productImages.length > 1"
            class="gallery-nav-btn gallery-nav-prev"
            @click="galleryActiveIndex = (galleryActiveIndex - 1 + productImages.length) % productImages.length"
          >
            <i class="pi pi-chevron-left"></i>
          </button>
          <button
            v-if="productImages.length > 1"
            class="gallery-nav-btn gallery-nav-next"
            @click="galleryActiveIndex = (galleryActiveIndex + 1) % productImages.length"
          >
            <i class="pi pi-chevron-right"></i>
          </button>

          <!-- Indicador de posición -->
          <div class="gallery-counter">
            {{ galleryActiveIndex + 1 }} / {{ productImages.length }}
          </div>

          <!-- Nombre del archivo -->
          <div class="gallery-caption">
            {{ productImages[galleryActiveIndex]?.nombre }}
          </div>

          <!-- Miniaturas -->
          <div v-if="productImages.length > 1" class="gallery-thumbnails">
            <div
              v-for="(img, idx) in productImages"
              :key="img.id"
              class="gallery-thumb"
              :class="{ active: idx === galleryActiveIndex }"
              @click="galleryActiveIndex = idx"
            >
              <img :src="img.url" :alt="img.nombre" />
            </div>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<style scoped>
.gallery-fullscreen-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background: rgba(0, 0, 0, 0.95);
  z-index: 9999;
  display: flex;
  align-items: center;
  justify-content: center;
}

.gallery-fullscreen-container {
  position: relative;
  width: 100%;
  height: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
}

.gallery-close-btn {
  position: absolute;
  top: 20px;
  right: 20px;
  width: 50px;
  height: 50px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.1);
  border: none;
  color: white;
  font-size: 24px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background 0.3s;
  z-index: 10;
}

.gallery-close-btn:hover {
  background: rgba(255, 255, 255, 0.3);
}

.gallery-main-image {
  max-width: 85vw;
  max-height: 75vh;
  display: flex;
  align-items: center;
  justify-content: center;
}

.gallery-main-image img {
  max-width: 100%;
  max-height: 75vh;
  object-fit: contain;
  border-radius: 8px;
  box-shadow: 0 10px 50px rgba(0, 0, 0, 0.5);
}

.gallery-nav-btn {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  width: 60px;
  height: 60px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.1);
  border: none;
  color: white;
  font-size: 28px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background 0.3s, transform 0.2s;
}

.gallery-nav-btn:hover {
  background: rgba(255, 255, 255, 0.3);
  transform: translateY(-50%) scale(1.1);
}

.gallery-nav-prev {
  left: 30px;
}

.gallery-nav-next {
  right: 30px;
}

.gallery-counter {
  position: absolute;
  top: 20px;
  left: 50%;
  transform: translateX(-50%);
  color: white;
  font-size: 16px;
  background: rgba(0, 0, 0, 0.5);
  padding: 8px 20px;
  border-radius: 20px;
}

.gallery-caption {
  position: absolute;
  bottom: 100px;
  left: 50%;
  transform: translateX(-50%);
  color: white;
  font-size: 14px;
  background: rgba(0, 0, 0, 0.5);
  padding: 8px 20px;
  border-radius: 8px;
  max-width: 80%;
  text-align: center;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.gallery-thumbnails {
  position: absolute;
  bottom: 20px;
  left: 50%;
  transform: translateX(-50%);
  display: flex;
  gap: 10px;
  background: rgba(0, 0, 0, 0.5);
  padding: 10px 15px;
  border-radius: 10px;
}

.gallery-thumb {
  width: 70px;
  height: 50px;
  border-radius: 6px;
  overflow: hidden;
  cursor: pointer;
  border: 2px solid transparent;
  opacity: 0.6;
  transition: all 0.3s;
}

.gallery-thumb:hover {
  opacity: 1;
}

.gallery-thumb.active {
  border-color: #f59e0b;
  opacity: 1;
}

.gallery-thumb img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
</style>
