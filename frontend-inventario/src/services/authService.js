import api from './api'

const authService = {
  /**
   * Iniciar sesión (Token-based authentication)
   */
  async login(credentials) {
    const response = await api.post('/auth/login', credentials)
    return response.data
  },

  /**
   * Cerrar sesión
   */
  async logout() {
    const response = await api.post('/auth/logout')
    localStorage.removeItem('token')
    localStorage.removeItem('user')
    return response.data
  },

  /**
   * Obtener usuario autenticado
   */
  async getUser() {
    const response = await api.get('/auth/me')
    return response.data
  },

  /**
   * Verificar si el token es válido
   */
  async checkAuth() {
    try {
      const response = await api.get('/auth/me')
      return { authenticated: true, user: response.data }
    } catch (error) {
      return { authenticated: false, user: null }
    }
  },

  /**
   * Cambiar contraseña
   */
  async changePassword(data) {
    const response = await api.post('/auth/change-password', data)
    return response.data
  },

  /**
   * Solicitar recuperación de contraseña
   */
  async forgotPassword(email) {
    const response = await api.post('/auth/forgot-password', { email })
    return response.data
  },

  /**
   * Resetear contraseña
   */
  async resetPassword(data) {
    const response = await api.post('/auth/reset-password', data)
    return response.data
  }
}

export default authService
