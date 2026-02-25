<script setup>
import { ref, computed } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import InputText from 'primevue/inputtext'
import Menu from 'primevue/menu'
import Avatar from 'primevue/avatar'
import NotificationPanel from './NotificationPanel.vue'

const emit = defineEmits(['toggle-sidebar'])

const route = useRoute()
const authStore = useAuthStore()

// Datos del usuario
const userName = computed(() => authStore.userName || 'Usuario')
const userRole = computed(() => {
  const roles = authStore.userRoles
  return roles.length > 0 ? roles[0] : 'Usuario'
})

// Menu de usuario
const userMenu = ref()
const userMenuItems = computed(() => [
  {
    label: 'Cerrar Sesión',
    icon: 'pi pi-sign-out',
    command: () => handleLogout()
  }
])

const toggleUserMenu = (event) => {
  userMenu.value.toggle(event)
}

const handleLogout = async () => {
  await authStore.logout()
}
</script>

<template>
  <header class="h-16 bg-white border-b border-gray-200 flex items-center justify-between px-6 sticky top-0 z-40">
    <!-- Left side -->
    <div class="flex items-center gap-4">
      <!-- Toggle sidebar button (mobile) -->
      <button
        @click="emit('toggle-sidebar')"
        class="lg:hidden p-2 rounded-lg hover:bg-gray-100 transition-colors"
      >
        <i class="pi pi-bars text-gray-600"></i>
      </button>

      <!-- Breadcrumb / Page title -->
      <div>
        <h1 class="text-lg font-semibold text-gray-800">
          {{ route.meta.title || 'Dashboard' }}
        </h1>
        <p v-if="route.meta.parent" class="text-xs text-gray-500">
          {{ route.meta.parent }} / {{ route.meta.title }}
        </p>
      </div>
    </div>

    <!-- Right side -->
    <div class="flex items-center gap-4">
      <!-- Search -->
      <div class="hidden md:block relative">
        <i class="pi pi-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
        <InputText
          placeholder="Buscar..."
          class="pl-10 w-64 text-sm"
        />
      </div>

      <!-- Notifications -->
      <NotificationPanel />

      <!-- User menu -->
      <div class="flex items-center gap-3">
        <div class="hidden md:block text-right">
          <p class="text-sm font-medium text-gray-700">{{ userName }}</p>
          <p class="text-xs text-gray-500">{{ userRole }}</p>
        </div>
        <button
          @click="toggleUserMenu"
          class="flex items-center gap-2 p-1 rounded-lg hover:bg-gray-100 transition-colors"
        >
          <Avatar
            icon="pi pi-user"
            class="bg-amber-100 text-amber-600"
            shape="circle"
          />
          <i class="pi pi-chevron-down text-xs text-gray-400 hidden md:block"></i>
        </button>
        <Menu ref="userMenu" :model="userMenuItems" :popup="true" />
      </div>
    </div>
  </header>
</template>
