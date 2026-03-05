<script setup>
defineProps({
  title: { type: String, required: true },
  maxWidth: { type: String, default: '' },
})
</script>

<template>
  <div class="min-h-screen bg-page px-4 md:px-6 lg:px-8 xl:px-10 py-6 md:py-8 lg:py-8 xl:py-10">
    <div :class="maxWidth ? [maxWidth, 'mx-auto'] : 'max-w-screen-2xl mx-auto'">
      <h1 class="text-xl md:text-2xl lg:text-3xl xl:text-4xl font-bold text-heading mb-6">{{ title }}</h1>
      <slot />
    </div>
  </div>
</template>
