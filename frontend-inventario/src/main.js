import { createApp } from 'vue'
import { createPinia } from 'pinia'
import PrimeVue from 'primevue/config'
import Aura from '@primevue/themes/aura'
import ToastService from 'primevue/toastservice'
import ConfirmationService from 'primevue/confirmationservice'
import Tooltip from 'primevue/tooltip'

// Estilos
import './style.css'
import 'primeicons/primeicons.css'
import 'primeflex/primeflex.css'

// App y Router
import App from './App.vue'
import router from './router'

// Crear aplicación
const app = createApp(App)

// Pinia (estado global)
const pinia = createPinia()
app.use(pinia)

// Vue Router
app.use(router)

// PrimeVue con tema Aura
app.use(PrimeVue, {
  theme: {
    preset: Aura,
    options: {
      prefix: 'p',
      darkModeSelector: '.dark-mode',
      cssLayer: false
    }
  },
  zIndex: {
    modal: 1100,
    overlay: 1200,
    menu: 1200,
    tooltip: 1300
  },
  locale: {
    // Español
    startsWith: 'Empieza con',
    contains: 'Contiene',
    notContains: 'No contiene',
    endsWith: 'Termina con',
    equals: 'Igual',
    notEquals: 'No igual',
    noFilter: 'Sin filtro',
    lt: 'Menor que',
    lte: 'Menor o igual que',
    gt: 'Mayor que',
    gte: 'Mayor o igual que',
    dateIs: 'Fecha es',
    dateIsNot: 'Fecha no es',
    dateBefore: 'Fecha antes de',
    dateAfter: 'Fecha después de',
    clear: 'Limpiar',
    apply: 'Aplicar',
    matchAll: 'Coincidir todo',
    matchAny: 'Coincidir cualquiera',
    addRule: 'Agregar regla',
    removeRule: 'Eliminar regla',
    accept: 'Sí',
    reject: 'No',
    choose: 'Elegir',
    upload: 'Subir',
    cancel: 'Cancelar',
    dayNames: ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'],
    dayNamesShort: ['Dom', 'Lun', 'Mar', 'Mie', 'Jue', 'Vie', 'Sab'],
    // Usar siglas de 1 letra evita traducciones automáticas raras del navegador (ej. "Do" => "Hacer")
    dayNamesMin: ['D', 'L', 'M', 'X', 'J', 'V', 'S'],
    monthNames: ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'],
    monthNamesShort: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'],
    today: 'Hoy',
    weekHeader: 'Sem',
    firstDayOfWeek: 1,
    dateFormat: 'dd/mm/yy',
    weak: 'Débil',
    medium: 'Medio',
    strong: 'Fuerte',
    passwordPrompt: 'Ingrese contraseña',
    emptyFilterMessage: 'Sin resultados',
    emptyMessage: 'Sin opciones disponibles',
    aria: {
      trueLabel: 'Verdadero',
      falseLabel: 'Falso',
      nullLabel: 'No seleccionado',
      pageLabel: 'Página {page}',
      firstPageLabel: 'Primera página',
      lastPageLabel: 'Última página',
      nextPageLabel: 'Siguiente página',
      previousPageLabel: 'Página anterior',
    }
  }
})

// Servicios de PrimeVue
app.use(ToastService)
app.use(ConfirmationService)

// Directivas de PrimeVue
app.directive('tooltip', Tooltip)

// Hardening: capturar errores globales de Vue para evitar caidas silenciosas
app.config.errorHandler = (err, instance, info) => {
  console.error('Error de interfaz capturado:', { err, info, instance })
}

window.addEventListener('unhandledrejection', (event) => {
  console.error('Promesa no manejada:', event.reason)
})

// Montar aplicación
app.mount('#app')
