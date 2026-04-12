<script setup>
import { computed, onMounted } from 'vue'
import { RouterView, useRouter, useRoute } from 'vue-router'
import { fetchConfig } from '@/api/config.js'
import { logout } from '@/api/auth.js'
import { useAuth } from '@/composables/useAuth.js'
import { useConfig } from '@/composables/useConfig.js'
import { setUnauthorizedHandler } from '@/utils/api.js'
import Header from '@/components/organisms/Header.vue'
import ToastContainer from '@/components/molecules/ToastContainer.vue'
import SiteFooter from '@/components/molecules/SiteFooter.vue'

const router = useRouter()
const route = useRoute()
const { user, isLoggedIn, isAdmin, clearAuth, hydrateAuthUser } = useAuth()
const { setConfig } = useConfig()

setUnauthorizedHandler(() => {
  if (route.name !== 'login') {
    clearAuth()
    router.push({ name: 'login' })
  }
})

const displayName = computed(() => user.value?.displayName || user.value?.username || 'User')
const currentRoute = computed(() => route.name || '')

onMounted(async () => {
  const startupTasks = [fetchConfig().then((data) => setConfig(data.data))]

  if (isLoggedIn.value) {
    startupTasks.push(hydrateAuthUser())
  }

  await Promise.allSettled(startupTasks)
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
  <SiteFooter />
  <ToastContainer />
</template>
