<script setup>
// Atom: EmptyState shows a friendly no-data message and an optional next action when a screen has nothing to display.
import { RouterLink } from 'vue-router'

defineProps({
  title: { type: String, required: true },
  description: { type: String, default: '' },
  actionLabel: { type: String, default: '' },
  actionTo: { type: String, default: '' },
})
</script>

<template>
  <section class="flex flex-col items-center justify-center py-16 px-4 text-center">
    <h3 class="text-lg font-semibold text-heading mb-2">{{ title }}</h3>
    <p v-if="description" class="text-sm text-muted mb-6 max-w-sm">{{ description }}</p>
    <RouterLink
      v-if="actionLabel && actionTo"
      :to="actionTo"
      class="inline-flex items-center px-4 py-2 rounded-lg bg-primary text-white font-medium text-sm transition-colors hover:bg-primary-hover"
    >
      {{ actionLabel }}
    </RouterLink>
  </section>
</template>
