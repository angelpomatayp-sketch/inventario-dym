<script setup>
defineProps({
  title: {
    type: String,
    required: true
  },
  value: {
    type: [Number, String],
    required: true
  },
  icon: {
    type: String,
    default: 'pi-chart-line'
  },
  accentColor: {
    type: String,
    default: 'border-gray-400'
  },
  iconColor: {
    type: String,
    default: 'text-gray-500'
  }
})
</script>

<template>
  <div
    class="bg-white rounded-lg p-4 border-l-4 shadow-sm transition-all hover:shadow-md cursor-pointer"
    :class="accentColor"
  >
    <div class="flex items-center justify-between">
      <div>
        <p class="text-3xl font-bold text-gray-800">{{ value }}</p>
        <p class="text-sm text-gray-500 mt-1">{{ title }}</p>
      </div>
      <div class="w-12 h-12 rounded-full bg-gray-100 flex items-center justify-center">
        <i :class="['pi', icon, 'text-xl', iconColor]"></i>
      </div>
    </div>
  </div>
</template>
