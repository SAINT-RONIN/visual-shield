<script setup>
import { computed } from 'vue'
import { RouterView, useRouter, useRoute } from 'vue-router'
import { useAuth } from '@/composables/useAuth.js'
import { useConfig } from '@/composables/useConfig.js'
import Header from '@/components/organisms/Header.vue'

const router = useRouter()
const route = useRoute()
const { user, isLoggedIn, isAdmin, logout } = useAuth()
const { loadConfig } = useConfig()
loadConfig()

const displayName = computed(() => user.value?.displayName || user.value?.username || 'User')
const currentRoute = computed(() => route.name || '')

async function handleLogout() {
  await logout()
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
</template>
