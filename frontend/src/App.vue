<script setup>
import { computed } from 'vue'
import { RouterView, useRouter, useRoute } from 'vue-router'
import { useAuth } from '@/composables/useAuth.js'
import Header from '@/components/organisms/Header.vue'

const router = useRouter()
const route = useRoute()
const { user, isLoggedIn, logout } = useAuth()

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
    :display-name="displayName"
    :current-route="currentRoute"
    @logout="handleLogout"
  />
  <main class="pt-14">
    <RouterView />
  </main>
</template>
