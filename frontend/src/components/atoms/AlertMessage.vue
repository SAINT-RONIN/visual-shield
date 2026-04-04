<script setup>
// Atom: AlertMessage renders a short inline success or error message near a form or page section.
defineProps({
  type: { type: String, default: 'error' },
  message: { type: String, default: '' },
})
</script>

<template>
  <p v-if="message" class="text-sm" :class="type === 'success' ? 'text-success' : 'text-error'">
    {{ message }}
  </p>
</template>
