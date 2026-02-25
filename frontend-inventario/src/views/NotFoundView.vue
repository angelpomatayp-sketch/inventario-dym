<script setup>
import { useRouter } from 'vue-router'
import Button from 'primevue/button'

const router = useRouter()

const goHome = () => {
  router.push('/')
}
</script>

<template>
  <div class="min-h-screen bg-gray-100 flex items-center justify-center p-4">
    <div class="text-center">
      <div class="mb-6">
        <span class="text-9xl font-bold text-gray-300">404</span>
      </div>
      <h1 class="text-3xl font-bold text-gray-800 mb-2">Página no encontrada</h1>
      <p class="text-gray-500 mb-8 max-w-md">
        Lo sentimos, la página que estás buscando no existe o ha sido movida.
      </p>
      <Button
        label="Volver al Inicio"
        icon="pi pi-home"
        @click="goHome"
        severity="primary"
      />
    </div>
  </div>
</template>
