<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import api from '@/services/api'
import Button from 'primevue/button'
import Badge from 'primevue/badge'
import Popover from 'primevue/popover'
import { useRouter } from 'vue-router'

const router = useRouter()
const popover = ref()
const loading = ref(false)
const notificaciones = ref([])
const totalNoLeidas = ref(0)
const porTipo = ref({})

// Intervalo para actualizar notificaciones
let intervalo = null

const cargarNotificaciones = async () => {
  try {
    loading.value = true
    const response = await api.get('/notificaciones/resumen')
    const data = response.data.data
    notificaciones.value = data.ultimas || []
    totalNoLeidas.value = data.total_no_leidas || 0
    porTipo.value = data.por_tipo || {}
  } catch (error) {
    console.error('Error cargando notificaciones:', error)
  } finally {
    loading.value = false
  }
}

const togglePanel = (event) => {
  popover.value.toggle(event)
}

const normalizeNotificationUrl = (url) => {
  if (!url || typeof url !== 'string') return null

  // Ajustar rutas de detalle que no existen en el router actual
  if (/^\/inventario\/productos\/\d+/.test(url)) return '/inventario/productos'
  if (/^\/requisiciones\/\d+/.test(url)) return '/requisiciones'

  return url
}

const navigateFromNotification = async (url) => {
  const normalizedUrl = normalizeNotificationUrl(url)
  if (!normalizedUrl) return

  const resolved = router.resolve(normalizedUrl)
  if (!resolved?.matched?.length) {
    console.warn('Ruta de notificación no válida:', normalizedUrl)
    return
  }

  try {
    await router.push(normalizedUrl)
    popover.value.hide()
  } catch (error) {
    console.error('Error al navegar desde notificación:', error)
  }
}

const marcarLeida = async (notificacion) => {
  try {
    await api.post(`/notificaciones/${notificacion.id}/marcar-leida`)
    notificacion.leida_en = new Date().toISOString()
    totalNoLeidas.value = Math.max(0, totalNoLeidas.value - 1)

    // Navegar si tiene URL
    if (notificacion.url) {
      await navigateFromNotification(notificacion.url)
    }
  } catch (error) {
    console.error('Error marcando notificación:', error)
  }
}

const marcarTodasLeidas = async () => {
  try {
    await api.post('/notificaciones/marcar-todas-leidas')
    notificaciones.value.forEach(n => n.leida_en = new Date().toISOString())
    totalNoLeidas.value = 0
  } catch (error) {
    console.error('Error marcando todas las notificaciones:', error)
  }
}

const getSeverityClass = (severidad) => {
  switch (severidad) {
    case 'danger': return 'bg-red-50 border-red-200 text-red-700'
    case 'warn': return 'bg-yellow-50 border-yellow-200 text-yellow-700'
    case 'success': return 'bg-green-50 border-green-200 text-green-700'
    default: return 'bg-blue-50 border-blue-200 text-blue-700'
  }
}

const getIconClass = (icono, severidad) => {
  const colorMap = {
    danger: 'text-red-500',
    warn: 'text-yellow-600',
    success: 'text-green-500',
    info: 'text-blue-500'
  }
  // Asegurar formato correcto: pi pi-xxx
  const iconName = icono?.startsWith('pi ') ? icono : `pi ${icono || 'pi-bell'}`
  return `${iconName} ${colorMap[severidad] || colorMap.info}`
}

const formatTiempo = (fecha) => {
  const now = new Date()
  const diff = now - new Date(fecha)
  const minutos = Math.floor(diff / 60000)
  const horas = Math.floor(diff / 3600000)
  const dias = Math.floor(diff / 86400000)

  if (minutos < 1) return 'Ahora'
  if (minutos < 60) return `Hace ${minutos} min`
  if (horas < 24) return `Hace ${horas}h`
  if (dias < 7) return `Hace ${dias}d`
  return new Date(fecha).toLocaleDateString('es-PE')
}

onMounted(() => {
  cargarNotificaciones()
  // Actualizar cada 2 minutos
  intervalo = setInterval(cargarNotificaciones, 120000)
})

onUnmounted(() => {
  if (intervalo) clearInterval(intervalo)
})

// Exponer para uso externo
defineExpose({ cargarNotificaciones, totalNoLeidas })
</script>

<template>
  <div class="notification-panel">
    <!-- Botón de campana -->
    <button
      @click="togglePanel"
      class="relative p-2 rounded-lg hover:bg-gray-100 transition-colors"
    >
      <i class="pi pi-bell text-gray-600 text-xl"></i>
      <Badge
        v-if="totalNoLeidas > 0"
        :value="totalNoLeidas > 99 ? '99+' : totalNoLeidas"
        severity="danger"
        class="absolute -top-1 -right-1"
      />
    </button>

    <!-- Panel de notificaciones -->
    <Popover ref="popover" class="notification-popover">
      <div class="notification-container">
        <!-- Header -->
        <div class="notification-header">
          <div class="flex items-center gap-2">
            <i class="pi pi-bell" style="color: var(--primary-color)"></i>
            <span class="font-semibold text-gray-800">Notificaciones</span>
            <Badge
              v-if="totalNoLeidas > 0"
              :value="totalNoLeidas"
              severity="danger"
            />
          </div>
          <Button
            v-if="totalNoLeidas > 0"
            label="Marcar todas"
            text
            size="small"
            @click="marcarTodasLeidas"
          />
        </div>

        <!-- Lista de notificaciones -->
        <div class="notification-list">
          <div v-if="loading" class="p-4 text-center">
            <i class="pi pi-spin pi-spinner text-2xl text-gray-400"></i>
          </div>

          <div v-else-if="notificaciones.length === 0" class="p-6 text-center">
            <i class="pi pi-inbox text-4xl text-gray-300 block mb-2"></i>
            <p class="text-gray-500 text-sm">No hay notificaciones</p>
          </div>

          <div v-else>
            <div
              v-for="notif in notificaciones"
              :key="notif.id"
              @click="marcarLeida(notif)"
              class="notification-item"
              :class="{ 'unread': !notif.leida_en }"
            >
              <div class="notification-icon" :class="getSeverityClass(notif.severidad)">
                <i :class="getIconClass(notif.icono, notif.severidad)"></i>
              </div>

              <div class="notification-content">
                <p class="notification-title" :class="{ 'font-bold': !notif.leida_en }">
                  {{ notif.titulo }}
                </p>
                <p class="notification-message">
                  {{ notif.mensaje }}
                </p>
                <p class="notification-time">
                  {{ formatTiempo(notif.created_at) }}
                </p>
              </div>

              <div v-if="!notif.leida_en" class="notification-dot"></div>
            </div>
          </div>
        </div>

        <!-- Footer con resumen por tipo -->
        <div v-if="Object.keys(porTipo).length > 0" class="notification-footer">
          <div class="flex flex-wrap gap-2">
            <span v-if="porTipo.STOCK_BAJO" class="badge-stock">
              <i class="pi pi-box"></i> {{ porTipo.STOCK_BAJO }} Stock bajo
            </span>
            <span v-if="porTipo.EPP_VENCIMIENTO || porTipo.EPP_POR_VENCER" class="badge-epp">
              <i class="pi pi-shield"></i> {{ (porTipo.EPP_VENCIMIENTO || 0) + (porTipo.EPP_POR_VENCER || 0) }} EPPs
            </span>
            <span v-if="porTipo.REQUISICION_PENDIENTE" class="badge-req">
              <i class="pi pi-file-edit"></i> {{ porTipo.REQUISICION_PENDIENTE }} Pendientes
            </span>
          </div>
        </div>
      </div>
    </Popover>
  </div>
</template>

<style scoped>
.notification-container {
  width: 360px;
  max-height: 450px;
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.notification-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 16px;
  border-bottom: 1px solid #e5e7eb;
  background: #f9fafb;
}

.notification-list {
  flex: 1;
  overflow-y: auto;
  max-height: 350px;
}

.notification-item {
  display: flex;
  gap: 12px;
  padding: 12px 16px;
  border-bottom: 1px solid #f3f4f6;
  cursor: pointer;
  transition: background-color 0.2s;
}

.notification-item:hover {
  background-color: #f9fafb;
}

.notification-item.unread {
  background-color: #eff6ff;
}

.notification-icon {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  font-size: 1rem;
}

.notification-content {
  flex: 1;
  min-width: 0;
}

.notification-title {
  font-size: 0.875rem;
  color: #1f2937;
  margin: 0 0 4px 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.notification-message {
  font-size: 0.75rem;
  color: #6b7280;
  margin: 0 0 4px 0;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  line-height: 1.4;
}

.notification-time {
  font-size: 0.7rem;
  color: #9ca3af;
  margin: 0;
}

.notification-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background-color: #3b82f6;
  flex-shrink: 0;
  margin-top: 4px;
}

.notification-footer {
  padding: 10px 16px;
  border-top: 1px solid #e5e7eb;
  background: #f9fafb;
}

.badge-stock,
.badge-epp,
.badge-req {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 0.7rem;
  font-weight: 500;
}

.badge-stock {
  background: #fef3c7;
  color: #92400e;
}

.badge-epp {
  background: #fee2e2;
  color: #991b1b;
}

.badge-req {
  background: #dbeafe;
  color: #1e40af;
}

:deep(.notification-popover) {
  padding: 0 !important;
}

:deep(.p-popover-content) {
  padding: 0 !important;
}
</style>
