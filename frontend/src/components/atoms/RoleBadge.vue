<script setup>
defineProps({
  role: { type: String, required: true }
})
</script>

<template>
  <span
    class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
    :class="role === 'admin' ? 'bg-primary/15 text-primary' : 'bg-surface-alt text-muted'"
  >
    {{ role }}
  </span>
</template>
