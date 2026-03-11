<script setup>
defineProps({
  title: { type: String, required: true },
})
</script>

<template>
  <div class="bg-surface rounded-xl p-4 md:p-6 border border-line">
    <h3 class="text-base md:text-lg font-semibold text-heading mb-4">{{ title }}</h3>
    <div class="h-48 sm:h-56 md:h-64">
      <slot />
    </div>
  </div>
</template>
