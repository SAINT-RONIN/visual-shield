<script setup>
// Molecule: UserMenuDropdown groups profile and logout actions into the header user menu.
import { ref, onMounted, onUnmounted } from 'vue'

defineProps({
  displayName: { type: String, default: 'User' },
})

defineEmits(['logout'])

const open = ref(false)
const dropdownRef = ref(null)

function handleClickOutside(e) {
  if (dropdownRef.value && !dropdownRef.value.contains(e.target)) {
    open.value = false
  }
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})
</script>

<template>
  <nav ref="dropdownRef" class="relative">
    <button
      @click="open = !open"
      class="flex items-center gap-2 text-sm text-body hover:text-heading transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-1 rounded-lg"
    >
      {{ displayName }}
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
      </svg>
    </button>
    <ul
      v-if="open"
      @click="open = false"
      class="absolute right-0 mt-2 w-48 bg-surface-alt border border-line-strong rounded-xl shadow-xl p-1.5 list-none m-0"
    >
      <li>
        <router-link
          to="/profile"
          class="flex items-center gap-2.5 rounded-lg px-3 py-2.5 text-sm text-body transition-colors hover:bg-surface-hover hover:text-heading focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-inset"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2" /><circle cx="12" cy="7" r="4" /></svg>
          Profile
        </router-link>
      </li>
      <li>
        <button
          @click="$emit('logout')"
          class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2.5 text-left text-sm text-body transition-colors hover:bg-surface-hover hover:text-heading focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-inset"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4M16 17l5-5-5-5M21 12H9" /></svg>
          Logout
        </button>
      </li>
    </ul>
  </nav>
</template>
