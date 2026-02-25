import api from './api'

const BASE_URL = '/inventario'

const productosService = {
  /**
   * Obtener lista de productos
   */
  async getAll(params = {}) {
    const response = await api.get(`${BASE_URL}/productos`, { params })
    return response.data
  },

  /**
   * Obtener un producto por ID
   */
  async getById(id) {
    const response = await api.get(`${BASE_URL}/productos/${id}`)
    return response.data
  },

  /**
   * Buscar producto por código
   */
  async getByCodigo(codigo) {
    const response = await api.get(`${BASE_URL}/productos/codigo/${codigo}`)
    return response.data
  },

  /**
   * Crear nuevo producto
   */
  async create(data) {
    const response = await api.post(`${BASE_URL}/productos`, data)
    return response.data
  },

  /**
   * Actualizar producto
   */
  async update(id, data) {
    const response = await api.put(`${BASE_URL}/productos/${id}`, data)
    return response.data
  },

  /**
   * Eliminar producto
   */
  async delete(id) {
    const response = await api.delete(`${BASE_URL}/productos/${id}`)
    return response.data
  },

  /**
   * Obtener productos con stock bajo
   */
  async getStockBajo() {
    const response = await api.get(`${BASE_URL}/productos-stock-bajo`)
    return response.data
  },

  /**
   * Obtener stock por almacenes
   */
  async getStockAlmacenes(productoId) {
    const response = await api.get(`${BASE_URL}/productos/${productoId}/stock-almacenes`)
    return response.data
  },

  /**
   * Obtener kardex del producto
   */
  async getKardex(productoId, params = {}) {
    const response = await api.get(`${BASE_URL}/productos/${productoId}/kardex`, { params })
    return response.data
  },

  /**
   * Obtener familias/categorías
   */
  async getFamilias() {
    const response = await api.get(`${BASE_URL}/familias`)
    return response.data
  },

  /**
   * Obtener unidades de medida (no existe en backend, usar familias por ahora)
   */
  async getUnidades() {
    // Las unidades están embebidas en los productos, no hay endpoint separado
    return { data: [] }
  },

  /**
   * Exportar productos a Excel
   */
  async exportExcel(params = {}) {
    const response = await api.get(`${BASE_URL}/productos/export`, {
      params,
      responseType: 'blob'
    })
    return response.data
  },

  // ==================== MÉTODOS PARA IMÁGENES ====================

  /**
   * Obtener imágenes de un producto
   */
  async getImagenes(productoId) {
    const response = await api.get(`/inventario/productos/${productoId}/imagenes`)
    return response.data
  },

  /**
   * Subir imágenes a un producto
   */
  async subirImagenes(productoId, archivos) {
    const formData = new FormData()
    archivos.forEach((archivo, index) => {
      formData.append(`imagenes[${index}]`, archivo)
    })

    const response = await api.post(`/inventario/productos/${productoId}/imagenes`, formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })
    return response.data
  },

  /**
   * Eliminar imagen de un producto
   */
  async eliminarImagen(productoId, imagenId) {
    const response = await api.delete(`/inventario/productos/${productoId}/imagenes/${imagenId}`)
    return response.data
  },

  /**
   * Establecer imagen principal
   */
  async setImagenPrincipal(productoId, imagenId) {
    const response = await api.put(`/inventario/productos/${productoId}/imagenes/${imagenId}/principal`)
    return response.data
  },

  /**
   * Reordenar imágenes
   */
  async reordenarImagenes(productoId, orden) {
    const response = await api.put(`/inventario/productos/${productoId}/imagenes/reordenar`, { orden })
    return response.data
  }
}

export default productosService
