<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import Sidebar from './Sidebar.vue'
import Topbar from './Topbar.vue'
import { useAuthStore } from '@/stores/auth'

const sidebarCollapsed = ref(false)
const isMobile = ref(false)
const mobileSidebarOpen = ref(false)
const authStore = useAuthStore()
const IDLE_TIMEOUT_MS = 15 * 60 * 1000
let idleTimer = null

const updateViewport = () => {
  isMobile.value = window.innerWidth < 1024
  if (!isMobile.value) {
    mobileSidebarOpen.value = false
  }
}

const toggleSidebar = () => {
  if (isMobile.value) {
    mobileSidebarOpen.value = !mobileSidebarOpen.value
    return
  }
  sidebarCollapsed.value = !sidebarCollapsed.value
}

const closeMobileSidebar = () => {
  if (isMobile.value) {
    mobileSidebarOpen.value = false
  }
}

const mainContentClass = computed(() => {
  if (isMobile.value) return 'ml-0'
  return sidebarCollapsed.value ? 'ml-16' : 'ml-64'
})

const clearIdleTimer = () => {
  if (idleTimer) {
    clearTimeout(idleTimer)
    idleTimer = null
  }
}

const startIdleTimer = () => {
  clearIdleTimer()
  idleTimer = setTimeout(async () => {
    sessionStorage.setItem('session_expired_message', 'Sesión cerrada por 15 minutos de inactividad.')
    await authStore.logout()
  }, IDLE_TIMEOUT_MS)
}

const handleUserActivity = () => {
  startIdleTimer()
}

const registerActivityListeners = () => {
  const events = ['mousemove', 'mousedown', 'keydown', 'scroll', 'touchstart', 'click']
  events.forEach((event) => window.addEventListener(event, handleUserActivity, { passive: true }))
}

const unregisterActivityListeners = () => {
  const events = ['mousemove', 'mousedown', 'keydown', 'scroll', 'touchstart', 'click']
  events.forEach((event) => window.removeEventListener(event, handleUserActivity))
}

onMounted(() => {
  updateViewport()
  window.addEventListener('resize', updateViewport)
  registerActivityListeners()
  startIdleTimer()
})

onBeforeUnmount(() => {
  window.removeEventListener('resize', updateViewport)
  unregisterActivityListeners()
  clearIdleTimer()
})
</script>

<template>
  <div class="flex min-h-screen bg-slate-100">
    <!-- Sidebar -->
    <Sidebar
      :collapsed="sidebarCollapsed"
      :isMobile="isMobile"
      :mobileOpen="mobileSidebarOpen"
      @toggle="toggleSidebar"
      @navigate="closeMobileSidebar"
    />

    <!-- Mobile overlay -->
    <div
      v-if="isMobile && mobileSidebarOpen"
      class="fixed inset-0 bg-black/40 z-40"
      @click="closeMobileSidebar"
    />

    <!-- Main Content -->
    <div
      class="flex-1 flex flex-col transition-all duration-300"
      :class="mainContentClass"
    >
      <!-- Topbar -->
      <Topbar @toggle-sidebar="toggleSidebar" />

      <!-- Page Content -->
      <main class="flex-1 p-6 overflow-auto">
        <router-view />
      </main>
    </div>
  </div>
</template>
