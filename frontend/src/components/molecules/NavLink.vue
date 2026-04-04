<script setup>
// Molecule: NavLink wraps a router link with shared active-state styling for the main navigation.
defineProps({
  to: { type: String, required: true },
  active: { type: Boolean, default: false },
})

defineEmits(['navigate'])
</script>

<template>
  <router-link
    :to="to"
    class="flex items-center gap-2 rounded-lg px-4 py-2 text-sm transition-colors"
    :class="active
      ? 'bg-primary/10 text-primary'
      : 'text-body hover:bg-surface-alt hover:text-heading'"
    @click="$emit('navigate')"
  >
    <slot />
  </router-link>
</template>
