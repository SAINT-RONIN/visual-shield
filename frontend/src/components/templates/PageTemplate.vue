<script setup>
defineProps({
  title: { type: String, required: true },
  maxWidth: { type: String, default: 'max-w-4xl' },
})
</script>

<template>
  <div class="min-h-screen bg-page px-4 py-8">
    <div class="mx-auto" :class="maxWidth">
      <h1 class="text-xl md:text-2xl lg:text-3xl font-bold text-heading mb-6">{{ title }}</h1>
      <slot />
    </div>
  </div>
</template>
