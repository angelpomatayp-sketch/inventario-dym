import api from './api'

const BASE = '/requerimientos'

const requerimientosService = {

  async getAll(params = {}) {
    const response = await api.get(BASE, { params })
    return response.data
  },

  async getById(id) {
    const response = await api.get(`${BASE}/${id}`)
    return response.data
  },

  async getEstadisticas() {
    const response = await api.get(`${BASE}/estadisticas`)
    return response.data
  },

  async create(data) {
    const response = await api.post(BASE, data)
    return response.data
  },

  async update(id, data) {
    const response = await api.put(`${BASE}/${id}`, data)
    return response.data
  },

  async delete(id) {
    const response = await api.delete(`${BASE}/${id}`)
    return response.data
  },

  async enviarAprobacion(id) {
    const response = await api.post(`${BASE}/${id}/enviar-aprobacion`)
    return response.data
  },

  async aprobar(id, data = {}) {
    const response = await api.post(`${BASE}/${id}/aprobar`, data)
    return response.data
  },

  async rechazar(id, comentario) {
    const response = await api.post(`${BASE}/${id}/rechazar`, { comentario })
    return response.data
  },

  async anular(id) {
    const response = await api.post(`${BASE}/${id}/anular`)
    return response.data
  },

  async getPdf(id) {
    const response = await api.get(`${BASE}/${id}/pdf`, { responseType: 'blob' })
    return response
  },
}

export default requerimientosService
