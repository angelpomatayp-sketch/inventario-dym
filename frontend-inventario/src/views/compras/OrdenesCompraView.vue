<script setup>
import { ref, computed, onMounted } from 'vue'
import { useToast } from 'primevue/usetoast'
import api from '@/services/api'
import { useAuthStore } from '@/stores/auth'

import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Textarea from 'primevue/textarea'
import Select from 'primevue/select'
import AutoComplete from 'primevue/autocomplete'
import DatePicker from 'primevue/datepicker'
import Card from 'primevue/card'
import Dialog from 'primevue/dialog'
import Tag from 'primevue/tag'
import Divider from 'primevue/divider'
import Message from 'primevue/message'
import Checkbox from 'primevue/checkbox'

const toast = useToast()
const authStore = useAuthStore()
const almacenAsignado = computed(() => authStore.user?.almacen_id || null)

const loading = ref(false)
const ordenes = ref([])
const estadisticas = ref({})
const searchQuery = ref('')
const selectedEstado = ref(null)

const dialogVisible = ref(false)
const viewDialogVisible = ref(false)
const recibirDialogVisible = ref(false)
const selectedOrden = ref(null)

const proveedores = ref([])
const almacenes = ref([])
const productoSuggestions = ref([])
const recepcionData = ref([])

const formData = ref({
  proveedor_id: null,
  almacen_destino_id: null,
  fecha_emision: new Date(),
  fecha_entrega_esperada: null,
  condiciones_pago: '',
  observaciones: '',
  detalles: []
})

const estados = [
  { label: 'Todos', value: null },
  { label: 'Borrador', value: 'BORRADOR' },
  { label: 'Pendiente', value: 'PENDIENTE' },
  { label: 'Aprobada', value: 'APROBADA' },
  { label: 'Enviada', value: 'ENVIADA' },
  { label: 'Parcial', value: 'PARCIAL' },
  { label: 'Recibida', value: 'RECIBIDA' },
  { label: 'Anulada', value: 'ANULADA' }
]

const loadOrdenes = async () => {
  loading.value = true
  try {
    const params = {}
    if (searchQuery.value) params.search = searchQuery.value
    if (selectedEstado.value) params.estado = selectedEstado.value
    const response = await api.get('/ordenes-compra', { params })
    ordenes.value = response.data.data || []
  } catch (err) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudieron cargar las órdenes', life: 4000 })
  } finally {
    loading.value = false
  }
}

const loadEstadisticas = async () => {
  try {
    const response = await api.get('/ordenes-compra/estadisticas')
    estadisticas.value = response.data.data
  } catch (err) { console.error(err) }
}

const loadProveedores = async () => {
  try {
    const response = await api.get('/proveedores', { params: { all: true, activo: true } })
    proveedores.value = response.data.data?.map(p => ({ label: p.razon_social, value: p.id })) || []
  } catch (err) { console.error(err) }
}

const loadAlmacenes = async () => {
  try {
    const response = await api.get('/administracion/almacenes', { params: { all: true } })
    almacenes.value = response.data.data?.map(a => ({ label: a.nombre, value: a.id })) || []
  } catch (err) { console.error(err) }
}

const searchProductos = async (event) => {
  try {
    const almacenId = almacenAsignado.value || formData.value.almacen_destino_id || null
    if (!almacenId) {
      productoSuggestions.value = []
      return
    }

    const response = await api.get('/inventario/productos', {
      params: {
        search: event.query,
        per_page: 10,
        almacen_id: almacenId,
        solo_con_stock: true
      }
    })
    productoSuggestions.value = response.data.data?.map(p => ({
      id: p.id, codigo: p.codigo, nombre: p.nombre,
      label: `${p.codigo} - ${p.nombre}`
    })) || []
  } catch (err) { productoSuggestions.value = [] }
}

const openNewDialog = () => {
  formData.value = {
    proveedor_id: null, almacen_destino_id: almacenAsignado.value || null,
    fecha_emision: new Date(), fecha_entrega_esperada: null,
    condiciones_pago: '', observaciones: '', detalles: []
  }
  dialogVisible.value = true
}

const viewOrden = async (orden) => {
  try {
    const response = await api.get(`/ordenes-compra/${orden.id}`)
    selectedOrden.value = response.data.data
    viewDialogVisible.value = true
  } catch (err) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudo cargar', life: 4000 })
  }
}

const openRecibirDialog = async (orden) => {
  try {
    const response = await api.get(`/ordenes-compra/${orden.id}`)
    selectedOrden.value = response.data.data
    recepcionData.value = response.data.data.detalles
      .filter(d => d.cantidad_solicitada > d.cantidad_recibida)
      .map(d => ({
        detalle_id: d.id, producto: d.producto,
        cantidad_solicitada: parseFloat(d.cantidad_solicitada),
        cantidad_recibida: parseFloat(d.cantidad_recibida),
        cantidad_recibir: parseFloat(d.cantidad_solicitada) - parseFloat(d.cantidad_recibida),
        lote: '', recibir: true
      }))
    recibirDialogVisible.value = true
  } catch (err) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'No se pudo cargar', life: 4000 })
  }
}

const addDetalle = () => {
  formData.value.detalles.push({ producto: null, cantidad_solicitada: 1, precio_unitario: 0, descuento: 0 })
}

const removeDetalle = (index) => { formData.value.detalles.splice(index, 1) }

const saveOrden = async () => {
  if (!formData.value.proveedor_id || !formData.value.almacen_destino_id || formData.value.detalles.length === 0) {
    toast.add({ severity: 'warn', summary: 'Validación', detail: 'Complete los campos requeridos', life: 4000 })
    return
  }
  loading.value = true
  try {
    await api.post('/ordenes-compra', {
      proveedor_id: formData.value.proveedor_id,
      almacen_destino_id: formData.value.almacen_destino_id,
      fecha_emision: formatDate(formData.value.fecha_emision),
      fecha_entrega_esperada: formData.value.fecha_entrega_esperada ? formatDate(formData.value.fecha_entrega_esperada) : null,
      condiciones_pago: formData.value.condiciones_pago,
      observaciones: formData.value.observaciones,
      detalles: formData.value.detalles.map(d => ({
        producto_id: d.producto.id,
        cantidad_solicitada: d.cantidad_solicitada,
        precio_unitario: d.precio_unitario,
        descuento: d.descuento || 0
      }))
    })
    toast.add({ severity: 'success', summary: 'Éxito', detail: 'Orden creada', life: 3000 })
    dialogVisible.value = false
    loadOrdenes()
    loadEstadisticas()
  } catch (err) {
    toast.add({ severity: 'error', summary: 'Error', detail: err.response?.data?.message || 'Error', life: 5000 })
  } finally { loading.value = false }
}

const enviarAprobacion = async (orden) => {
  try {
    await api.post(`/ordenes-compra/${orden.id}/enviar-aprobacion`)
    toast.add({ severity: 'success', summary: 'Éxito', detail: 'Enviada a aprobación', life: 3000 })
    loadOrdenes()
  } catch (err) {
    toast.add({ severity: 'error', summary: 'Error', detail: err.response?.data?.message || 'Error', life: 4000 })
  }
}

const aprobarOrden = async (orden) => {
  try {
    await api.post(`/ordenes-compra/${orden.id}/aprobar`)
    toast.add({ severity: 'success', summary: 'Éxito', detail: 'Orden aprobada', life: 3000 })
    loadOrdenes()
  } catch (err) {
    toast.add({ severity: 'error', summary: 'Error', detail: err.response?.data?.message || 'Error', life: 4000 })
  }
}

const enviarProveedor = async (orden) => {
  try {
    await api.post(`/ordenes-compra/${orden.id}/enviar-proveedor`)
    toast.add({ severity: 'success', summary: 'Éxito', detail: 'Enviada al proveedor', life: 3000 })
    loadOrdenes()
  } catch (err) {
    toast.add({ severity: 'error', summary: 'Error', detail: err.response?.data?.message || 'Error', life: 4000 })
  }
}

const procesarRecepcion = async () => {
  const recepciones = recepcionData.value.filter(r => r.recibir && r.cantidad_recibir > 0)
    .map(r => ({ detalle_id: r.detalle_id, cantidad: r.cantidad_recibir, lote: r.lote || null }))
  if (recepciones.length === 0) {
    toast.add({ severity: 'warn', summary: 'Validación', detail: 'Seleccione productos', life: 4000 })
    return
  }
  loading.value = true
  try {
    await api.post(`/ordenes-compra/${selectedOrden.value.id}/recibir`, { recepciones })
    toast.add({ severity: 'success', summary: 'Éxito', detail: 'Recepción procesada. Stock actualizado.', life: 3000 })
    recibirDialogVisible.value = false
    loadOrdenes()
    loadEstadisticas()
  } catch (err) {
    toast.add({ severity: 'error', summary: 'Error', detail: err.response?.data?.message || 'Error', life: 5000 })
  } finally { loading.value = false }
}

const anularOrden = async (orden) => {
  if (!confirm('¿Anular esta orden?')) return
  try {
    await api.post(`/ordenes-compra/${orden.id}/anular`)
    toast.add({ severity: 'warn', summary: 'Anulada', detail: 'Orden anulada', life: 3000 })
    loadOrdenes()
  } catch (err) {
    toast.add({ severity: 'error', summary: 'Error', detail: err.response?.data?.message || 'Error', life: 4000 })
  }
}

const formatDate = (date) => date ? new Date(date).toISOString().split('T')[0] : null
const formatDateDisplay = (d) => d ? new Date(d).toLocaleDateString('es-PE') : '-'
const formatCurrency = (v) => v ? new Intl.NumberFormat('es-PE', { style: 'currency', currency: 'PEN' }).format(v) : 'S/ 0.00'
const getEstadoSeverity = (e) => ({ BORRADOR: 'secondary', PENDIENTE: 'warn', APROBADA: 'info', ENVIADA: 'info', PARCIAL: 'warn', RECIBIDA: 'success', ANULADA: 'danger' }[e] || 'secondary')
const calcularSubtotal = (d) => d.producto && d.cantidad_solicitada && d.precio_unitario ? (d.cantidad_solicitada * d.precio_unitario * (1 - (d.descuento || 0) / 100)).toFixed(2) : '0.00'
const calcularTotal = () => formData.value.detalles.reduce((s, d) => s + parseFloat(calcularSubtotal(d)), 0).toFixed(2)

onMounted(() => { loadOrdenes(); loadEstadisticas(); loadProveedores(); loadAlmacenes() })
</script>

<template>
  <div class="space-y-4">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Órdenes de Compra</h1>
        <p class="text-gray-600 text-sm">Gestión de compras a proveedores</p>
      </div>
      <Button label="Nueva Orden" icon="pi pi-plus" class="!bg-amber-600 !border-amber-600" @click="openNewDialog" />
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
      <Card class="!bg-blue-50"><template #content><div class="text-center"><p class="text-2xl font-bold text-blue-700">{{ estadisticas.total || 0 }}</p><p class="text-sm text-blue-600">Total</p></div></template></Card>
      <Card class="!bg-yellow-50"><template #content><div class="text-center"><p class="text-2xl font-bold text-yellow-700">{{ estadisticas.pendientes || 0 }}</p><p class="text-sm text-yellow-600">Pendientes</p></div></template></Card>
      <Card class="!bg-orange-50"><template #content><div class="text-center"><p class="text-2xl font-bold text-orange-700">{{ estadisticas.por_recibir || 0 }}</p><p class="text-sm text-orange-600">Por Recibir</p></div></template></Card>
      <Card class="!bg-green-50"><template #content><div class="text-center"><p class="text-2xl font-bold text-green-700">{{ formatCurrency(estadisticas.valor_mes) }}</p><p class="text-sm text-green-600">Valor Mes</p></div></template></Card>
    </div>

    <Card>
      <template #content>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
          <InputText v-model="searchQuery" placeholder="Buscar..." class="w-full" @keyup.enter="loadOrdenes" />
          <Select v-model="selectedEstado" :options="estados" optionLabel="label" optionValue="value" placeholder="Estado" class="w-full" @change="loadOrdenes" />
          <div class="md:col-span-2 flex gap-2">
            <Button label="Buscar" icon="pi pi-search" class="!bg-amber-600 !border-amber-600" @click="loadOrdenes" />
            <Button icon="pi pi-refresh" severity="secondary" outlined @click="searchQuery='';selectedEstado=null;loadOrdenes()" />
          </div>
        </div>
        <DataTable :value="ordenes" :loading="loading" paginator :rows="15" stripedRows>
          <template #empty><div class="text-center py-8 text-gray-500"><i class="pi pi-shopping-cart text-4xl mb-2"></i><p>Sin órdenes</p></div></template>
          <Column field="numero" header="Número" style="width:130px"><template #body="{data}"><span class="font-mono text-blue-600">{{ data.numero }}</span></template></Column>
          <Column field="fecha_emision" header="Fecha" style="width:100px"><template #body="{data}">{{ formatDateDisplay(data.fecha_emision) }}</template></Column>
          <Column header="Proveedor"><template #body="{data}"><p class="font-medium">{{ data.proveedor?.razon_social }}</p></template></Column>
          <Column field="almacen_destino.nombre" header="Almacén" style="width:120px" />
          <Column field="estado" header="Estado" style="width:100px"><template #body="{data}"><Tag :value="data.estado" :severity="getEstadoSeverity(data.estado)" /></template></Column>
          <Column field="total" header="Total" style="width:110px"><template #body="{data}"><span class="font-medium">{{ formatCurrency(data.total) }}</span></template></Column>
          <Column header="Acciones" style="width:160px">
            <template #body="{data}">
              <div class="flex gap-1">
                <Button icon="pi pi-eye" severity="info" text rounded size="small" @click="viewOrden(data)" />
                <Button v-if="data.estado==='BORRADOR'" icon="pi pi-send" severity="secondary" text rounded size="small" @click="enviarAprobacion(data)" v-tooltip.top="'Enviar'" />
                <Button v-if="data.estado==='PENDIENTE'" icon="pi pi-check" severity="success" text rounded size="small" @click="aprobarOrden(data)" v-tooltip.top="'Aprobar'" />
                <Button v-if="data.estado==='APROBADA'" icon="pi pi-truck" severity="info" text rounded size="small" @click="enviarProveedor(data)" v-tooltip.top="'Enviar proveedor'" />
                <Button v-if="['ENVIADA','PARCIAL'].includes(data.estado)" icon="pi pi-download" severity="success" text rounded size="small" @click="openRecibirDialog(data)" v-tooltip.top="'Recibir'" />
                <Button v-if="!['RECIBIDA','ANULADA'].includes(data.estado)" icon="pi pi-ban" severity="danger" text rounded size="small" @click="anularOrden(data)" v-tooltip.top="'Anular'" />
              </div>
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>

    <!-- Dialog Nueva Orden -->
    <Dialog v-model:visible="dialogVisible" header="Nueva Orden de Compra" modal :style="{width:'90vw',maxWidth:'850px',maxHeight:'90vh'}" :contentStyle="{overflow:'auto',maxHeight:'calc(90vh - 120px)'}">
      <div class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div><label class="block text-sm font-medium mb-1">Proveedor *</label><Select v-model="formData.proveedor_id" :options="proveedores" optionLabel="label" optionValue="value" placeholder="Seleccione" class="w-full" filter /></div>
          <div><label class="block text-sm font-medium mb-1">Almacén Destino *</label><Select v-model="formData.almacen_destino_id" :options="almacenes" optionLabel="label" optionValue="value" placeholder="Seleccione" class="w-full" /></div>
          <div><label class="block text-sm font-medium mb-1">Fecha Emisión</label><DatePicker v-model="formData.fecha_emision" dateFormat="dd/mm/yy" class="w-full" /></div>
          <div><label class="block text-sm font-medium mb-1">Entrega Esperada</label><DatePicker v-model="formData.fecha_entrega_esperada" dateFormat="dd/mm/yy" class="w-full" /></div>
          <div class="md:col-span-2"><label class="block text-sm font-medium mb-1">Condiciones Pago</label><InputText v-model="formData.condiciones_pago" class="w-full" placeholder="Ej: Crédito 30 días" /></div>
        </div>
        <Divider />
        <div class="flex justify-between mb-2"><span class="font-medium">Productos *</span><Button label="Agregar" icon="pi pi-plus" size="small" severity="secondary" @click="addDetalle" /></div>
        <div v-if="formData.detalles.length===0" class="text-center py-4 text-gray-500 border rounded">Sin productos</div>
        <div v-else class="space-y-2">
          <div v-for="(d,i) in formData.detalles" :key="i" class="grid grid-cols-12 gap-2 items-end p-3 bg-gray-50 rounded">
            <div class="col-span-5"><label class="text-xs text-gray-500">Producto</label><AutoComplete v-model="d.producto" :suggestions="productoSuggestions" optionLabel="label" placeholder="Buscar..." class="w-full" @complete="searchProductos" /></div>
            <div class="col-span-2"><label class="text-xs text-gray-500">Cantidad</label><InputNumber v-model="d.cantidad_solicitada" :min="0.01" class="w-full" /></div>
            <div class="col-span-2"><label class="text-xs text-gray-500">P.Unit</label><InputNumber v-model="d.precio_unitario" :min="0" mode="currency" currency="PEN" locale="es-PE" class="w-full" /></div>
            <div class="col-span-2 text-center"><label class="text-xs text-gray-500">Subtotal</label><p class="font-medium">{{ formatCurrency(calcularSubtotal(d)) }}</p></div>
            <div class="col-span-1"><Button icon="pi pi-trash" severity="danger" text rounded @click="removeDetalle(i)" /></div>
          </div>
          <div class="text-right pt-2 border-t"><span class="text-lg font-bold">Total: {{ formatCurrency(calcularTotal()) }}</span><p class="text-xs text-gray-500">+ IGV 18%</p></div>
        </div>
        <div><label class="block text-sm font-medium mb-1">Observaciones</label><Textarea v-model="formData.observaciones" rows="2" class="w-full" /></div>
      </div>
      <template #footer>
        <Button label="Cancelar" severity="secondary" @click="dialogVisible=false" />
        <Button label="Crear Orden" icon="pi pi-check" class="!bg-amber-600 !border-amber-600" @click="saveOrden" :loading="loading" />
      </template>
    </Dialog>

    <!-- Dialog Ver -->
    <Dialog v-model:visible="viewDialogVisible" header="Detalle de Orden" modal :style="{width:'90vw',maxWidth:'700px',maxHeight:'90vh'}" :contentStyle="{overflow:'auto',maxHeight:'calc(90vh - 120px)'}">
      <div v-if="selectedOrden" class="space-y-4">
        <div class="grid grid-cols-4 gap-4 bg-gray-50 p-4 rounded-lg">
          <div><p class="text-xs text-gray-500">Número</p><p class="font-mono font-bold text-blue-600">{{ selectedOrden.numero }}</p></div>
          <div><p class="text-xs text-gray-500">Estado</p><Tag :value="selectedOrden.estado" :severity="getEstadoSeverity(selectedOrden.estado)" /></div>
          <div><p class="text-xs text-gray-500">Fecha</p><p>{{ formatDateDisplay(selectedOrden.fecha_emision) }}</p></div>
          <div><p class="text-xs text-gray-500">Total</p><p class="font-bold text-green-600">{{ formatCurrency(selectedOrden.total) }}</p></div>
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div><p class="text-xs text-gray-500">Proveedor</p><p class="font-medium">{{ selectedOrden.proveedor?.razon_social }}</p></div>
          <div><p class="text-xs text-gray-500">Almacén</p><p>{{ selectedOrden.almacen_destino?.nombre }}</p></div>
        </div>
        <Divider />
        <DataTable :value="selectedOrden.detalles" size="small" stripedRows>
          <Column field="producto.codigo" header="Código" style="width:90px" />
          <Column field="producto.nombre" header="Producto" />
          <Column header="Solicit." style="width:80px"><template #body="{data}">{{ data.cantidad_solicitada }}</template></Column>
          <Column header="Recib." style="width:80px"><template #body="{data}"><span :class="data.cantidad_recibida>=data.cantidad_solicitada?'text-green-600':'text-yellow-600'">{{ data.cantidad_recibida }}</span></template></Column>
          <Column header="Subtotal" style="width:100px"><template #body="{data}">{{ formatCurrency(data.subtotal) }}</template></Column>
        </DataTable>
      </div>
      <template #footer><Button label="Cerrar" severity="secondary" @click="viewDialogVisible=false" /></template>
    </Dialog>

    <!-- Dialog Recibir -->
    <Dialog v-model:visible="recibirDialogVisible" header="Recibir Mercancía" modal :style="{width:'90vw',maxWidth:'750px',maxHeight:'90vh'}" :contentStyle="{overflow:'auto',maxHeight:'calc(90vh - 120px)'}">
      <Message severity="info" :closable="false">Confirme cantidades. Esto actualiza el stock.</Message>
      <DataTable :value="recepcionData" size="small" class="mt-4">
        <Column style="width:50px"><template #body="{data}"><Checkbox v-model="data.recibir" :binary="true" /></template></Column>
        <Column field="producto.codigo" header="Código" style="width:90px" />
        <Column field="producto.nombre" header="Producto" />
        <Column header="Pend." style="width:80px"><template #body="{data}">{{ data.cantidad_solicitada - data.cantidad_recibida }}</template></Column>
        <Column header="Recibir" style="width:100px"><template #body="{data}"><InputNumber v-model="data.cantidad_recibir" :min="0" :max="data.cantidad_solicitada-data.cantidad_recibida" :disabled="!data.recibir" class="w-full" size="small" /></template></Column>
        <Column header="Lote" style="width:100px"><template #body="{data}"><InputText v-model="data.lote" :disabled="!data.recibir" class="w-full" size="small" /></template></Column>
      </DataTable>
      <template #footer>
        <Button label="Cancelar" severity="secondary" @click="recibirDialogVisible=false" />
        <Button label="Confirmar" icon="pi pi-check" severity="success" @click="procesarRecepcion" :loading="loading" />
      </template>
    </Dialog>
  </div>
</template>
