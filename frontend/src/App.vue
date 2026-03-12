<script setup>
import { computed, onMounted } from 'vue'
import { RouterView, useRouter, useRoute } from 'vue-router'
import { fetchConfig } from '@/api/config.js'
import { logout } from '@/api/auth.js'
import { useAuth } from '@/composables/useAuth.js'
import { useConfig } from '@/composables/useConfig.js'
import Header from '@/components/organisms/Header.vue'
import ToastContainer from '@/components/atoms/ToastContainer.vue'

const router = useRouter()
const route = useRoute()
const { user, isLoggedIn, isAdmin, clearAuth } = useAuth()
const { setConfig } = useConfig()

const displayName = computed(() => user.value?.displayName || user.value?.username || 'User')
const currentRoute = computed(() => route.name || '')

onMounted(async () => {
  try {
    const data = await fetchConfig()
    setConfig(data.data)
  } catch {
    // Config load failed silently
  }
})

async function handleLogout() {
  try {
    await logout()
  } catch {
    // Ignore logout errors
  }
  clearAuth()
  router.push('/login')
}
</script>

<template>
  <Header
    :is-logged-in="isLoggedIn"
    :is-admin="isAdmin"
    :display-name="displayName"
    :current-route="currentRoute"
    @logout="handleLogout"
  />
  <main class="pt-14">
    <RouterView />
  </main>
  <ToastContainer />
</template>
