<script setup>
defineProps({
  to: { type: String, required: true },
  active: { type: Boolean, default: false },
})

defineEmits(['navigate'])
</script>

<template>
  <router-link
    :to="to"
    class="px-3 py-2 text-sm rounded-lg transition-colors"
    :class="active ? 'text-heading border-b-2 border-primary' : 'text-body hover:text-heading'"
    @click="$emit('navigate')"
  >
    <slot />
  </router-link>
</template>
