<script setup>
import { RouterView, useRouter } from 'vue-router'
import { useAuth } from '@/composables/useAuth.js'
import Header from '@/components/organisms/Header.vue'

const router = useRouter()
const { logout } = useAuth()

async function handleLogout() {
  await logout()
  router.push('/login')
}
</script>

<template>
  <Header @logout="handleLogout" />
  <main class="pt-14">
    <RouterView />
  </main>
</template>
