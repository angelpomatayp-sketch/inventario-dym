import api from './api'

const BASE_URL = '/administracion'

const almacenesService = {
  /**
   * Obtener lista de almacenes
   */
  async getAll(params = {}) {
    const response = await api.get(`${BASE_URL}/almacenes`, { params })
    return response.data
  },

  /**
   * Obtener almacenes activos (para dropdowns)
   */
  async getActivos() {
    const response = await api.get(`${BASE_URL}/almacenes`, { params: { activo: true } })
    return response.data
  },

  /**
   * Obtener un almacén por ID
   */
  async getById(id) {
    const response = await api.get(`${BASE_URL}/almacenes/${id}`)
    return response.data
  },

  /**
   * Crear nuevo almacén
   */
  async create(data) {
    const response = await api.post(`${BASE_URL}/almacenes`, data)
    return response.data
  },

  /**
   * Actualizar almacén
   */
  async update(id, data) {
    const response = await api.put(`${BASE_URL}/almacenes/${id}`, data)
    return response.data
  },

  /**
   * Eliminar almacén
   */
  async delete(id) {
    const response = await api.delete(`${BASE_URL}/almacenes/${id}`)
    return response.data
  },

  /**
   * Obtener productos del almacén
   */
  async getProductos(id, params = {}) {
    const response = await api.get(`${BASE_URL}/almacenes/${id}/productos`, { params })
    return response.data
  },

  /**
   * Obtener tipos de almacén
   */
  getTipos() {
    return [
      { label: 'Principal', value: 'PRINCIPAL' },
      { label: 'Campamento', value: 'CAMPAMENTO' },
      { label: 'Satélite', value: 'SATELITE' }
    ]
  }
}

export default almacenesService
