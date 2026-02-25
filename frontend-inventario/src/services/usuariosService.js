import api from './api'

const BASE_URL = '/administracion'

const usuariosService = {
  /**
   * Obtener lista de usuarios
   */
  async getAll(params = {}) {
    const response = await api.get(`${BASE_URL}/usuarios`, { params })
    return response.data
  },

  /**
   * Obtener un usuario por ID
   */
  async getById(id) {
    const response = await api.get(`${BASE_URL}/usuarios/${id}`)
    return response.data
  },

  /**
   * Crear nuevo usuario
   */
  async create(data) {
    const response = await api.post(`${BASE_URL}/usuarios`, data)
    return response.data
  },

  /**
   * Actualizar usuario
   */
  async update(id, data) {
    const response = await api.put(`${BASE_URL}/usuarios/${id}`, data)
    return response.data
  },

  /**
   * Eliminar usuario
   */
  async delete(id) {
    const response = await api.delete(`${BASE_URL}/usuarios/${id}`)
    return response.data
  },

  /**
   * Cambiar estado activo/inactivo
   */
  async toggleActive(id) {
    const response = await api.put(`${BASE_URL}/usuarios/${id}/toggle-activo`)
    return response.data
  },

  /**
   * Cambiar contraseña de usuario
   */
  async cambiarPassword(id, data) {
    const response = await api.put(`${BASE_URL}/usuarios/${id}/cambiar-password`, data)
    return response.data
  },

  /**
   * Obtener roles disponibles
   */
  async getRoles() {
    const response = await api.get(`${BASE_URL}/roles`)
    return response.data
  },

  /**
   * Asignar roles a usuario
   */
  async assignRoles(id, roles) {
    const response = await api.post(`${BASE_URL}/usuarios/${id}/roles`, { roles })
    return response.data
  }
}

export default usuariosService
