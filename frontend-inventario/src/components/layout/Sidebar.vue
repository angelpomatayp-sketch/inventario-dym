<script setup>
import { ref, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const props = defineProps({
  collapsed: {
    type: Boolean,
    default: false
  },
  isMobile: {
    type: Boolean,
    default: false
  },
  mobileOpen: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['toggle', 'navigate'])

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

// Verificar si tiene permiso
const hasPermission = (permission) => {
  if (!permission) return true // Sin restricción
  return authStore.hasPermission(permission)
}

// Menú de navegación con permisos
const menuItemsBase = [
  {
    label: 'Dashboard',
    icon: 'pi-home',
    to: '/'
  },
  {
    label: 'Inventario',
    icon: 'pi-box',
    children: [
      { label: 'Productos', icon: 'pi-list', to: '/inventario/productos', permission: 'productos.ver' },
      { label: 'Movimientos', icon: 'pi-arrow-right-arrow-left', to: '/inventario/movimientos', permission: 'movimientos.ver' },
      { label: 'Kardex', icon: 'pi-book', to: '/inventario/kardex', permission: 'kardex.ver' },
      { label: 'Familias', icon: 'pi-folder', to: '/inventario/familias', permission: 'familias.ver' },
      { label: 'Unidades', icon: 'pi-calculator', to: '/inventario/unidades', permission: 'unidades.ver' }
    ]
  },
  {
    label: 'Requisiciones',
    icon: 'pi-file-edit',
    children: [
      { label: 'Solicitudes', icon: 'pi-file-edit', to: '/requisiciones', permission: 'requisiciones.ver' },
      { label: 'Vales de Salida', icon: 'pi-sign-out', to: '/requisiciones/vales-salida', permission: 'vales_salida.ver' }
    ]
  },
  {
    label: 'Compras',
    icon: 'pi-shopping-cart',
    children: [
      { label: 'Órdenes de Compra', icon: 'pi-shopping-cart', to: '/compras/ordenes', permission: 'ordenes_compra.ver' },
      { label: 'Cotizaciones', icon: 'pi-file', to: '/compras/cotizaciones', permission: 'cotizaciones.ver' }
    ]
  },
  {
    label: 'Proveedores',
    icon: 'pi-truck',
    to: '/proveedores',
    permission: 'proveedores.ver'
  },
  {
    label: 'EPPs',
    icon: 'pi-shield',
    to: '/epps',
    permission: 'epps.ver'
  },
  {
    label: 'Préstamos',
    icon: 'pi-sync',
    to: '/prestamos',
    permission: 'prestamos.ver'
  },
  {
    label: 'Reportes',
    icon: 'pi-chart-bar',
    to: '/reportes',
    permission: 'reportes.dashboard'
  },
  {
    label: 'Administración',
    icon: 'pi-cog',
    children: [
      { label: 'Usuarios', icon: 'pi-users', to: '/admin/usuarios', permission: 'usuarios.ver' },
      { label: 'Trabajadores', icon: 'pi-id-card', to: '/admin/trabajadores', permission: 'trabajadores.ver' },
      { label: 'Roles', icon: 'pi-key', to: '/admin/roles', permission: 'roles.ver' },
      { label: 'Almacenes', icon: 'pi-warehouse', to: '/admin/almacenes', permission: 'almacenes.crear' },
      { label: 'Centros de Costo', icon: 'pi-sitemap', to: '/admin/centros-costo', permission: 'centros_costo.crear' }
    ]
  }
]

// Filtrar menú según permisos
const menuItems = computed(() => {
  return menuItemsBase
    .map(item => {
      if (item.children) {
        // Filtrar hijos según permisos
        const filteredChildren = item.children.filter(child => hasPermission(child.permission))
        // Solo mostrar el padre si tiene hijos visibles
        if (filteredChildren.length === 0) return null
        return { ...item, children: filteredChildren }
      }
      // Item sin hijos - verificar permiso
      if (!hasPermission(item.permission)) return null
      return item
    })
    .filter(item => item !== null)
})

// Estado de submenús abiertos
const openMenus = ref({})

const toggleSubmenu = (label) => {
  openMenus.value[label] = !openMenus.value[label]
}

const isActive = (path) => {
  return route.path === path
}

const isParentActive = (item) => {
  if (item.children) {
    return item.children.some(child => route.path === child.to)
  }
  return false
}

const navigateTo = (path) => {
  router.push(path)
}
</script>

<template>
  <aside
    class="fixed left-0 top-0 h-full bg-gray-800 text-white transition-all duration-300 z-50 flex flex-col"
    :class="[
      isMobile ? 'w-72' : (collapsed ? 'w-16' : 'w-64'),
      isMobile ? (mobileOpen ? 'translate-x-0' : '-translate-x-full') : 'translate-x-0'
    ]"
  >
    <!-- Logo -->
    <div class="h-16 flex items-center justify-center border-b border-gray-700 px-2">
      <div v-if="!collapsed" class="flex items-center gap-2">
        <img src="/logo-dym.png" alt="DYM SAC" class="h-12 w-auto" />
      </div>
      <img v-else src="/logo-dym.png" alt="DYM SAC" class="h-10 w-auto" />
    </div>

    <!-- Menu -->
    <nav class="flex-1 overflow-y-auto py-4 px-2">
      <ul class="space-y-1">
        <li v-for="item in menuItems" :key="item.label">
          <!-- Item con hijos (submenu) -->
          <template v-if="item.children">
            <button
              @click="toggleSubmenu(item.label)"
              class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-300 hover:bg-gray-700 hover:text-white transition-colors"
              :class="{
                'bg-gray-700 text-white': isParentActive(item),
                'justify-center': collapsed
              }"
            >
              <i :class="['pi', item.icon, 'text-lg']"></i>
              <span v-if="!collapsed" class="flex-1 text-left text-sm">{{ item.label }}</span>
              <i
                v-if="!collapsed"
                :class="['pi text-xs transition-transform', openMenus[item.label] ? 'pi-chevron-down' : 'pi-chevron-right']"
              ></i>
            </button>

            <!-- Submenu -->
            <ul
              v-if="!collapsed && openMenus[item.label]"
              class="mt-1 ml-4 space-y-1"
            >
              <li v-for="child in item.children" :key="child.label">
                <router-link
                  :to="child.to"
                  class="flex items-center gap-3 px-3 py-2 rounded-lg text-gray-400 hover:bg-gray-700 hover:text-white transition-colors text-sm"
                  :class="{ 'bg-amber-600 text-white': isActive(child.to) }"
                  @click="emit('navigate')"
                >
                  <i :class="['pi', child.icon, 'text-sm']"></i>
                  <span>{{ child.label }}</span>
                </router-link>
              </li>
            </ul>
          </template>

          <!-- Item sin hijos -->
          <template v-else>
            <router-link
              :to="item.to"
              class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-gray-300 hover:bg-gray-700 hover:text-white transition-colors"
              :class="{
                'bg-amber-600 text-white': isActive(item.to),
                'justify-center': collapsed
              }"
              @click="emit('navigate')"
            >
              <i :class="['pi', item.icon, 'text-lg']"></i>
              <span v-if="!collapsed" class="text-sm">{{ item.label }}</span>
            </router-link>
          </template>
        </li>
      </ul>
    </nav>

    <!-- Footer del sidebar -->
    <div class="p-3 border-t border-gray-700">
      <button
        @click="emit('toggle')"
        class="w-full flex items-center justify-center gap-2 px-3 py-2 rounded-lg text-gray-400 hover:bg-gray-700 hover:text-white transition-colors"
      >
        <i :class="['pi', collapsed ? 'pi-angle-double-right' : 'pi-angle-double-left']"></i>
        <span v-if="!collapsed" class="text-sm">Colapsar</span>
      </button>
    </div>
  </aside>
</template>
