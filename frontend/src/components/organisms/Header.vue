<script setup>
import { ref } from 'vue'
import NavLink from '@/components/molecules/NavLink.vue'
import UserMenuDropdown from '@/components/molecules/UserMenuDropdown.vue'
import AppButton from '@/components/atoms/AppButton.vue'

defineProps({
  isLoggedIn: { type: Boolean, default: false },
  displayName: { type: String, default: 'User' },
  currentRoute: { type: String, default: '' },
})

const emit = defineEmits(['logout'])

const mobileMenuOpen = ref(false)

function handleLogout() {
  mobileMenuOpen.value = false
  emit('logout')
}
</script>

<template>
  <header class="fixed top-0 left-0 right-0 z-50 bg-surface border-b border-line">
    <div class="max-w-screen-2xl mx-auto px-4 md:px-6 lg:px-8 xl:px-10 h-14 flex items-center justify-between">
      <!-- Logo -->
      <router-link to="/dashboard" class="text-lg font-bold text-heading">
        Visual Shield
      </router-link>

      <!-- Desktop Nav -->
      <nav v-if="isLoggedIn" class="hidden md:flex items-center gap-1">
        <NavLink to="/dashboard" :active="currentRoute === 'dashboard'">Dashboard</NavLink>
        <NavLink to="/upload" :active="currentRoute === 'upload'">Upload</NavLink>
      </nav>

      <!-- User Menu (logged in) -->
      <div v-if="isLoggedIn" class="hidden md:block">
        <UserMenuDropdown
          :display-name="displayName"
          @logout="handleLogout"
        />
      </div>

      <!-- Auth Links (logged out) -->
      <div v-if="!isLoggedIn" class="hidden md:flex items-center gap-2">
        <router-link to="/login" class="text-sm text-body hover:text-heading transition-colors">
          Log In
        </router-link>
        <router-link to="/register">
          <AppButton variant="primary" class="text-sm py-1.5">Register</AppButton>
        </router-link>
      </div>

      <!-- Mobile Hamburger -->
      <button
        @click="mobileMenuOpen = !mobileMenuOpen"
        class="md:hidden text-body hover:text-heading"
      >
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path v-if="!mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
          <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>

    <!-- Mobile Menu -->
    <div v-if="mobileMenuOpen" class="md:hidden border-t border-line bg-surface px-4 py-3 space-y-2">
      <template v-if="isLoggedIn">
        <NavLink to="/dashboard" :active="currentRoute === 'dashboard'" class="block" @navigate="mobileMenuOpen = false">Dashboard</NavLink>
        <NavLink to="/upload" :active="currentRoute === 'upload'" class="block" @navigate="mobileMenuOpen = false">Upload</NavLink>
        <NavLink to="/profile" :active="currentRoute === 'profile'" class="block" @navigate="mobileMenuOpen = false">Profile</NavLink>
        <button
          @click="handleLogout"
          class="w-full text-left px-3 py-2 text-sm text-body hover:text-heading rounded-lg"
        >
          Logout
        </button>
      </template>
      <template v-else>
        <NavLink to="/login" :active="currentRoute === 'login'" class="block" @navigate="mobileMenuOpen = false">Log In</NavLink>
        <NavLink to="/register" :active="currentRoute === 'register'" class="block" @navigate="mobileMenuOpen = false">Register</NavLink>
      </template>
    </div>
  </header>
</template>
