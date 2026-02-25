<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import InputText from 'primevue/inputtext'
import InputGroup from 'primevue/inputgroup'
import InputGroupAddon from 'primevue/inputgroupaddon'
import Password from 'primevue/password'
import Button from 'primevue/button'
import Checkbox from 'primevue/checkbox'
import Message from 'primevue/message'

const route = useRoute()
const authStore = useAuthStore()

const email = ref('')
const password = ref('')
const rememberMe = ref(false)
const localError = ref('')

const loading = computed(() => authStore.loading)
const error = computed(() => localError.value || authStore.error)

const handleLogin = async () => {
  localError.value = ''

  // Validación básica
  if (!email.value || !password.value) {
    localError.value = 'Por favor ingrese email y contraseña'
    return
  }

  const result = await authStore.login({
    email: email.value,
    password: password.value,
    remember: rememberMe.value
  })

  if (result.success) {
    // Redirigir a la página solicitada o al dashboard
    const redirectTo = route.query.redirect || '/'
    // La redirección se maneja en el store
  }
}

const clearError = () => {
  localError.value = ''
  authStore.clearError()
}

onMounted(() => {
  const expiredMessage = sessionStorage.getItem('session_expired_message')
  if (expiredMessage) {
    localError.value = expiredMessage
    sessionStorage.removeItem('session_expired_message')
  }
})
</script>

<template>
  <div class="h-screen bg-gradient-to-br from-gray-800 to-gray-900 flex flex-col items-center justify-center p-4">
    <div class="w-full max-w-md">
      <!-- Logo y título -->
      <div class="text-center mb-6">
        <div class="inline-flex items-center justify-center mb-3">
          <img src="/logo-dym.png" alt="DYM SAC" class="h-20 w-auto" />
        </div>
        <h1 class="text-2xl font-bold text-white">Sistema de Inventario</h1>
        <p class="text-gray-400 mt-1">KardexOne</p>
      </div>

      <!-- Card de login -->
      <div class="bg-white rounded-2xl shadow-xl p-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-5">Iniciar Sesión</h2>

        <!-- Mensaje de error -->
        <Message v-if="error" severity="error" :closable="true" @close="clearError" class="mb-4">
          {{ error }}
        </Message>

        <form @submit.prevent="handleLogin" class="space-y-4">
          <!-- Email -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Correo electrónico
            </label>
            <InputGroup>
              <InputGroupAddon>
                <i class="pi pi-envelope text-gray-400"></i>
              </InputGroupAddon>
              <InputText
                v-model="email"
                type="email"
                placeholder="usuario@empresa.com"
                class="w-full"
                :disabled="loading"
                @input="clearError"
              />
            </InputGroup>
          </div>

          <!-- Password -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Contraseña
            </label>
            <Password
              v-model="password"
              placeholder="Ingrese su contraseña"
              :feedback="false"
              toggleMask
              class="w-full"
              inputClass="w-full"
              :disabled="loading"
              @input="clearError"
            />
          </div>

          <!-- Remember me -->
          <div class="flex items-center">
            <div class="flex items-center gap-2">
              <Checkbox v-model="rememberMe" :binary="true" inputId="remember" :disabled="loading" />
              <label for="remember" class="text-sm text-gray-600 cursor-pointer">
                Recordarme
              </label>
            </div>
          </div>

          <!-- Submit button -->
          <Button
            type="submit"
            label="Iniciar Sesión"
            :loading="loading"
            :disabled="loading"
            class="w-full justify-center !bg-amber-600 !border-amber-600 hover:!bg-amber-700"
          />
        </form>
      </div>

      <!-- Footer -->
      <p class="text-center text-gray-500 text-sm mt-4">
        © 2024 DYM SAC. Todos los derechos reservados.
      </p>
    </div>
  </div>
</template>
