<script setup>
import { useRoute } from 'vue-router'

const props = defineProps({
  to: { type: String, required: true },
  routeName: { type: String, default: '' },
})

const route = useRoute()

defineEmits(['navigate'])
</script>

<template>
  <router-link
    :to="to"
    class="px-3 py-2 text-sm rounded-lg transition-colors"
    :class="route.name === routeName ? 'text-heading border-b-2 border-primary' : 'text-body hover:text-heading'"
    @click="$emit('navigate')"
  >
    <slot />
  </router-link>
</template>
