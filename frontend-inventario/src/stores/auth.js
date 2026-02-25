import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import authService from '@/services/authService'
import router from '@/router'

// Función helper para parsear JSON de localStorage de forma segura
const getStoredUser = () => {
  try {
    const stored = localStorage.getItem('user')
    if (stored && stored !== 'undefined' && stored !== 'null') {
      return JSON.parse(stored)
    }
    return null
  } catch {
    localStorage.removeItem('user')
    return null
  }
}

const getStoredToken = () => {
  const stored = localStorage.getItem('token')
  if (stored && stored !== 'undefined' && stored !== 'null') {
    return stored
  }
  return null
}

export const useAuthStore = defineStore('auth', () => {
  // Estado
  const user = ref(getStoredUser())
  const token = ref(getStoredToken())
  const loading = ref(false)
  const error = ref(null)

  // Getters
  const isAuthenticated = computed(() => !!token.value && !!user.value)
  const userName = computed(() => user.value?.nombre || '')
  const userEmail = computed(() => user.value?.email || '')
  const userRoles = computed(() => user.value?.roles || [])
  const userPermissions = computed(() => user.value?.permissions || [])

  // Verificar si tiene un rol específico
  const hasRole = (role) => {
    return userRoles.value.includes(role)
  }

  // Verificar si tiene un permiso específico
  const hasPermission = (permission) => {
    return userPermissions.value.includes(permission)
  }

  // Actions
  const login = async (credentials) => {
    loading.value = true
    error.value = null

    try {
      const response = await authService.login(credentials)

      // La respuesta viene como { success, message, data: { token, user } }
      if (response.success && response.data) {
        token.value = response.data.token
        user.value = response.data.user

        localStorage.setItem('token', response.data.token)
        localStorage.setItem('user', JSON.stringify(response.data.user))

        router.push('/')
        return { success: true }
      } else {
        error.value = response.message || 'Error al iniciar sesión'
        return { success: false, error: error.value }
      }
    } catch (err) {
      error.value = err.response?.data?.message || 'Error al iniciar sesión'
      return { success: false, error: error.value }
    } finally {
      loading.value = false
    }
  }

  const logout = async () => {
    loading.value = true

    try {
      await authService.logout()
    } catch (err) {
      console.error('Error al cerrar sesión:', err)
    } finally {
      // Limpiar estado aunque falle el logout en el servidor
      token.value = null
      user.value = null
      localStorage.removeItem('token')
      localStorage.removeItem('user')
      loading.value = false
      router.push('/login')
    }
  }

  const checkAuth = async () => {
    if (!token.value) return false

    try {
      const response = await authService.checkAuth()
      if (response.authenticated && response.user) {
        user.value = response.user
        localStorage.setItem('user', JSON.stringify(response.user))
        return true
      }
      // También manejar respuesta del formato { success, data }
      if (response.success && response.data) {
        user.value = response.data
        localStorage.setItem('user', JSON.stringify(response.data))
        return true
      }
      return false
    } catch (err) {
      token.value = null
      user.value = null
      localStorage.removeItem('token')
      localStorage.removeItem('user')
      return false
    }
  }

  const updateUser = (userData) => {
    user.value = { ...user.value, ...userData }
    localStorage.setItem('user', JSON.stringify(user.value))
  }

  const clearError = () => {
    error.value = null
  }

  return {
    // Estado
    user,
    token,
    loading,
    error,
    // Getters
    isAuthenticated,
    userName,
    userEmail,
    userRoles,
    userPermissions,
    // Métodos
    hasRole,
    hasPermission,
    login,
    logout,
    checkAuth,
    updateUser,
    clearError
  }
})
