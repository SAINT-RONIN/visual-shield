<script setup>
// Atom: UserStatusBadge displays the active/inactive state of a user account as a small labeled badge.
defineProps({
  isActive: { type: Boolean, required: true }
})
</script>

<template>
  <span
    class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
    :class="isActive ? 'bg-success/15 text-success' : 'bg-error/15 text-error'"
  >
    {{ isActive ? 'Active' : 'Inactive' }}
  </span>
</template>
