<script setup>
import { ref } from 'vue'

defineProps({
  displayName: { type: String, default: 'User' },
})

defineEmits(['logout'])

const open = ref(false)
</script>

<template>
  <div class="relative">
    <button
      @click="open = !open"
      class="flex items-center gap-2 text-sm text-body hover:text-heading transition-colors"
    >
      {{ displayName }}
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
      </svg>
    </button>
    <div
      v-if="open"
      @click="open = false"
      class="absolute right-0 mt-2 w-48 bg-surface-alt border border-line-strong rounded-lg shadow-lg py-1"
    >
      <router-link
        to="/profile"
        class="block px-4 py-2 text-sm text-body hover:bg-surface-hover hover:text-heading"
      >
        Profile
      </router-link>
      <button
        @click="$emit('logout')"
        class="w-full text-left px-4 py-2 text-sm text-body hover:bg-surface-hover hover:text-heading"
      >
        Logout
      </button>
    </div>
  </div>
</template>
