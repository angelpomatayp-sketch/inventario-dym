import api from './api'

const proveedoresService = {
  /**
   * Obtener lista de proveedores
   */
  async getAll(params = {}) {
    const response = await api.get('/proveedores', { params })
    return response.data
  },

  /**
   * Obtener un proveedor por ID
   */
  async getById(id) {
    const response = await api.get(`/proveedores/${id}`)
    return response.data
  },

  /**
   * Buscar proveedor por RUC
   */
  async getByRuc(ruc) {
    const response = await api.get(`/proveedores/ruc/${ruc}`)
    return response.data
  },

  /**
   * Crear nuevo proveedor
   */
  async create(data) {
    const response = await api.post('/proveedores', data)
    return response.data
  },

  /**
   * Actualizar proveedor
   */
  async update(id, data) {
    const response = await api.put(`/proveedores/${id}`, data)
    return response.data
  },

  /**
   * Eliminar proveedor
   */
  async delete(id) {
    const response = await api.delete(`/proveedores/${id}`)
    return response.data
  },

  /**
   * Validar RUC en SUNAT
   */
  async validarRuc(ruc) {
    const response = await api.get(`/proveedores/validar-ruc/${ruc}`)
    return response.data
  },

  /**
   * Obtener órdenes de compra del proveedor
   */
  async getOrdenesCompra(id, params = {}) {
    const response = await api.get(`/proveedores/${id}/ordenes-compra`, { params })
    return response.data
  },

  /**
   * Calificar proveedor
   */
  async calificar(id, data) {
    const response = await api.post(`/proveedores/${id}/calificacion`, data)
    return response.data
  },

  /**
   * Obtener historial de calificaciones
   */
  async getCalificaciones(id) {
    const response = await api.get(`/proveedores/${id}/calificaciones`)
    return response.data
  },

  /**
   * Exportar proveedores a Excel
   */
  async exportExcel(params = {}) {
    const response = await api.get('/proveedores/export', {
      params,
      responseType: 'blob'
    })
    return response.data
  }
}

export default proveedoresService
