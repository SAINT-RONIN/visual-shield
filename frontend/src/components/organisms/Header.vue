<script setup>
import { ref } from 'vue'
import NavLink from '@/components/molecules/NavLink.vue'
import UserMenuDropdown from '@/components/molecules/UserMenuDropdown.vue'
import AppButton from '@/components/atoms/AppButton.vue'
import ThemeToggle from '@/components/atoms/ThemeToggle.vue'

defineProps({
  isLoggedIn: { type: Boolean, default: false },
  isAdmin: { type: Boolean, default: false },
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
    <div class="px-4 md:px-6 lg:px-8 xl:px-10 h-14 flex items-center justify-between">
      <!-- Logo -->
      <router-link to="/dashboard" class="text-lg font-bold text-heading">
        Visual Shield
      </router-link>

      <!-- Desktop Nav -->
      <nav v-if="isLoggedIn" class="hidden md:flex items-center gap-1">
        <NavLink to="/dashboard" :active="currentRoute === 'dashboard'">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1" /><rect x="14" y="3" width="7" height="7" rx="1" /><rect x="3" y="14" width="7" height="7" rx="1" /><rect x="14" y="14" width="7" height="7" rx="1" /></svg>
          Dashboard
        </NavLink>
        <NavLink to="/upload" :active="currentRoute === 'upload'">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M12 4v12M7 9l5-5 5 5" /></svg>
          Upload
        </NavLink>
        <NavLink v-if="isAdmin" to="/admin" :active="currentRoute === 'admin'">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" /></svg>
          Admin
        </NavLink>
      </nav>

      <!-- User Menu (logged in) -->
      <div v-if="isLoggedIn" class="hidden md:flex items-center gap-2">
        <ThemeToggle />
        <UserMenuDropdown
          :display-name="displayName"
          @logout="handleLogout"
        />
      </div>

      <!-- Auth Links (logged out) -->
      <div v-if="!isLoggedIn" class="hidden md:flex items-center gap-2">
        <ThemeToggle />
        <router-link to="/login" class="flex items-center gap-2 text-sm text-body hover:text-heading transition-colors">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 3h4a2 2 0 012 2v14a2 2 0 01-2 2h-4M10 17l5-5-5-5M15 12H3" /></svg>
          Log In
        </router-link>
        <router-link to="/register">
          <AppButton variant="primary" class="text-sm py-1.5 flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2" /><circle cx="9" cy="7" r="4" /><line x1="19" y1="8" x2="19" y2="14" /><line x1="22" y1="11" x2="16" y2="11" /></svg>
            Register
          </AppButton>
        </router-link>
      </div>

      <!-- Mobile: theme toggle + hamburger -->
      <div class="md:hidden flex items-center gap-1">
        <ThemeToggle />
        <button
          @click="mobileMenuOpen = !mobileMenuOpen"
          class="text-body hover:text-heading"
        >
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path v-if="!mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
          <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>
    </div>

    <!-- Mobile Menu -->
    <div v-if="mobileMenuOpen" class="md:hidden border-t border-line bg-surface px-4 py-3 space-y-2">
      <template v-if="isLoggedIn">
        <NavLink to="/dashboard" :active="currentRoute === 'dashboard'" class="block" @navigate="mobileMenuOpen = false">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1" /><rect x="14" y="3" width="7" height="7" rx="1" /><rect x="3" y="14" width="7" height="7" rx="1" /><rect x="14" y="14" width="7" height="7" rx="1" /></svg>
          Dashboard
        </NavLink>
        <NavLink to="/upload" :active="currentRoute === 'upload'" class="block" @navigate="mobileMenuOpen = false">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M12 4v12M7 9l5-5 5 5" /></svg>
          Upload
        </NavLink>
        <NavLink v-if="isAdmin" to="/admin" :active="currentRoute === 'admin'" class="block" @navigate="mobileMenuOpen = false">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" /></svg>
          Admin
        </NavLink>
        <NavLink to="/profile" :active="currentRoute === 'profile'" class="block" @navigate="mobileMenuOpen = false">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2" /><circle cx="12" cy="7" r="4" /></svg>
          Profile
        </NavLink>
        <button
          @click="handleLogout"
          class="flex items-center gap-2 w-full text-left px-4 py-2 text-sm text-body hover:bg-surface-alt hover:text-heading rounded-lg transition-colors"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4M16 17l5-5-5-5M21 12H9" /></svg>
          Logout
        </button>
      </template>
      <template v-else>
        <NavLink to="/login" :active="currentRoute === 'login'" class="block" @navigate="mobileMenuOpen = false">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 3h4a2 2 0 012 2v14a2 2 0 01-2 2h-4M10 17l5-5-5-5M15 12H3" /></svg>
          Log In
        </NavLink>
        <NavLink to="/register" :active="currentRoute === 'register'" class="block" @navigate="mobileMenuOpen = false">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2" /><circle cx="9" cy="7" r="4" /><line x1="19" y1="8" x2="19" y2="14" /><line x1="22" y1="11" x2="16" y2="11" /></svg>
          Register
        </NavLink>
      </template>
    </div>
  </header>
</template>
