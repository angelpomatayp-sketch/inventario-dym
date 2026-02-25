import axios from 'axios'

// Configuración base de Axios
const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || 'http://localhost:8000/api',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
})

// Interceptor de request - agregar token
api.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('token')
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }
    return config
  },
  (error) => {
    return Promise.reject(error)
  }
)

// Interceptor de response - manejar errores
api.interceptors.response.use(
  (response) => response,
  (error) => {
    const { response } = error

    if (response) {
      const requestUrl = error.config?.url || ''
      const isLoginRequest = requestUrl.includes('/auth/login')

      switch (response.status) {
        case 401:
          // Token expirado o no autenticado
          // En login fallido no redirigir, dejar que el formulario muestre el mensaje.
          if (!isLoginRequest) {
            localStorage.removeItem('token')
            localStorage.removeItem('user')
            window.location.href = '/login'
          }
          break
        case 403:
          // Sin permisos
          console.error('No tiene permisos para esta acción')
          break
        case 404:
          console.error('Recurso no encontrado')
          break
        case 422:
          // Errores de validación
          console.error('Error de validación:', response.data.errors)
          break
        case 500:
          console.error('Error del servidor')
          break
      }
    }

    return Promise.reject(error)
  }
)

export default api
