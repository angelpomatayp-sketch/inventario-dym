import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

// Layouts
import MainLayout from '@/components/layout/MainLayout.vue'

// Views - Lazy loading
const Dashboard = () => import('@/views/dashboard/DashboardView.vue')
const Productos = () => import('@/views/inventario/ProductosView.vue')
const Movimientos = () => import('@/views/inventario/MovimientosView.vue')
const Kardex = () => import('@/views/inventario/KardexView.vue')
const Familias = () => import('@/views/inventario/FamiliasView.vue')
const Unidades = () => import('@/views/inventario/UnidadesView.vue')
const Requisiciones = () => import('@/views/requisiciones/RequisicionesView.vue')
const ValesSalida = () => import('@/views/requisiciones/ValesSalidaView.vue')
const OrdenesCompra = () => import('@/views/compras/OrdenesCompraView.vue')
const Cotizaciones = () => import('@/views/compras/CotizacionesView.vue')
const Proveedores = () => import('@/views/proveedores/ProveedoresView.vue')
const EPPs = () => import('@/views/epps/EppsView.vue')
const Prestamos = () => import('@/views/prestamos/PrestamosView.vue')
const Reportes = () => import('@/views/reportes/ReportesView.vue')
const Usuarios = () => import('@/views/admin/UsuariosView.vue')
const Trabajadores = () => import('@/views/admin/TrabajadoresView.vue')
const Roles = () => import('@/views/admin/RolesView.vue')
const Almacenes = () => import('@/views/admin/AlmacenesView.vue')
const CentrosCosto = () => import('@/views/admin/CentrosCostoView.vue')

const routes = [
  {
    path: '/',
    component: MainLayout,
    meta: { requiresAuth: true },
    children: [
      {
        path: '',
        name: 'dashboard',
        component: Dashboard,
        meta: { title: 'Dashboard', icon: 'pi-home' }
      },
      // Inventario
      {
        path: 'inventario/productos',
        name: 'productos',
        component: Productos,
        meta: { title: 'Productos', icon: 'pi-box', parent: 'Inventario', permission: 'productos.ver' }
      },
      {
        path: 'inventario/movimientos',
        name: 'movimientos',
        component: Movimientos,
        meta: { title: 'Movimientos', icon: 'pi-arrow-right-arrow-left', parent: 'Inventario', permission: 'movimientos.ver' }
      },
      {
        path: 'inventario/kardex',
        name: 'kardex',
        component: Kardex,
        meta: { title: 'Kardex', icon: 'pi-book', parent: 'Inventario', permission: 'kardex.ver' }
      },
      {
        path: 'inventario/familias',
        name: 'familias',
        component: Familias,
        meta: { title: 'Familias', icon: 'pi-folder', parent: 'Inventario' }
      },
      {
        path: 'inventario/unidades',
        name: 'unidades',
        component: Unidades,
        meta: { title: 'Unidades de Medida', icon: 'pi-calculator', parent: 'Inventario' }
      },
      // Requisiciones
      {
        path: 'requisiciones',
        name: 'requisiciones',
        component: Requisiciones,
        meta: { title: 'Requisiciones', icon: 'pi-file-edit', permission: 'requisiciones.ver' }
      },
      {
        path: 'requisiciones/vales-salida',
        name: 'vales-salida',
        component: ValesSalida,
        meta: { title: 'Vales de Salida', icon: 'pi-sign-out', parent: 'Requisiciones' }
      },
      // Compras
      {
        path: 'compras/ordenes',
        name: 'ordenes-compra',
        component: OrdenesCompra,
        meta: { title: 'Órdenes de Compra', icon: 'pi-shopping-cart', parent: 'Compras' }
      },
      {
        path: 'compras/cotizaciones',
        name: 'cotizaciones',
        component: Cotizaciones,
        meta: { title: 'Cotizaciones', icon: 'pi-dollar', parent: 'Compras' }
      },
      // Proveedores
      {
        path: 'proveedores',
        name: 'proveedores',
        component: Proveedores,
        meta: { title: 'Proveedores', icon: 'pi-truck', permission: 'proveedores.ver' }
      },
      // EPPs
      {
        path: 'epps',
        name: 'epps',
        component: EPPs,
        meta: { title: 'EPPs', icon: 'pi-shield' }
      },
      // Préstamos
      {
        path: 'prestamos',
        name: 'prestamos',
        component: Prestamos,
        meta: { title: 'Préstamos de Equipos', icon: 'pi-sync' }
      },
      // Reportes
      {
        path: 'reportes',
        name: 'reportes',
        component: Reportes,
        meta: { title: 'Reportes', icon: 'pi-chart-bar' }
      },
      // Admin
      {
        path: 'admin/usuarios',
        name: 'usuarios',
        component: Usuarios,
        meta: { title: 'Usuarios', icon: 'pi-users', parent: 'Administración', permission: 'usuarios.ver' }
      },
      {
        path: 'admin/trabajadores',
        name: 'trabajadores',
        component: Trabajadores,
        meta: { title: 'Trabajadores', icon: 'pi-id-card', parent: 'Administración', permission: 'trabajadores.ver' }
      },
      {
        path: 'admin/roles',
        name: 'roles',
        component: Roles,
        meta: { title: 'Roles y Permisos', icon: 'pi-key', parent: 'Administración', permission: 'roles.ver' }
      },
      {
        path: 'admin/almacenes',
        name: 'almacenes',
        component: Almacenes,
        meta: { title: 'Almacenes', icon: 'pi-building', parent: 'Administración', permission: 'almacenes.ver' }
      },
      {
        path: 'admin/centros-costo',
        name: 'centros-costo',
        component: CentrosCosto,
        meta: { title: 'Centros de Costo', icon: 'pi-briefcase', parent: 'Administración', permission: 'centros_costo.ver' }
      },
    ]
  },
  // Login (sin layout, sin autenticación)
  {
    path: '/login',
    name: 'login',
    component: () => import('@/views/auth/LoginView.vue'),
    meta: { title: 'Iniciar Sesión', requiresAuth: false, hideForAuth: true }
  },
  // 404
  {
    path: '/:pathMatch(.*)*',
    name: 'not-found',
    component: () => import('@/views/NotFoundView.vue'),
    meta: { title: 'Página no encontrada', requiresAuth: false }
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

// Guard de navegación
router.beforeEach(async (to, from, next) => {
  // Actualizar título de la página
  document.title = to.meta.title
    ? `${to.meta.title} | Sistema Inventario DYM`
    : 'Sistema Inventario DYM'

  // Verificar si la ruta requiere autenticación
  const requiresAuth = to.matched.some(record => record.meta.requiresAuth !== false)
  const hideForAuth = to.meta.hideForAuth

  // Obtener store de auth (lazy para evitar problemas de inicialización)
  const authStore = useAuthStore()

  // Si requiere autenticación y no está autenticado
  if (requiresAuth && !authStore.isAuthenticated) {
    // Intentar verificar token existente
    const isValid = await authStore.checkAuth()
    if (!isValid) {
      return next({ name: 'login', query: { redirect: to.fullPath } })
    }
  }

  // Si está autenticado y la ruta es solo para no autenticados (ej: login)
  if (hideForAuth && authStore.isAuthenticated) {
    return next({ name: 'dashboard' })
  }

  // Verificar permisos si la ruta lo requiere
  if (to.meta.permission && authStore.isAuthenticated) {
    if (!authStore.hasPermission(to.meta.permission)) {
      // Sin permisos, redirigir al dashboard con mensaje
      console.warn(`Sin permisos para acceder a: ${to.meta.permission}`)
      return next({ name: 'dashboard' })
    }
  }

  next()
})

export default router
