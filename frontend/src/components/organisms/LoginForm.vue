<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuth } from '@/composables/useAuth.js'
import AppInput from '@/components/atoms/AppInput.vue'
import AppButton from '@/components/atoms/AppButton.vue'
import AlertMessage from '@/components/atoms/AlertMessage.vue'

const router = useRouter()
const { login } = useAuth()

const username = ref('')
const password = ref('')
const error = ref('')
const loading = ref(false)

async function handleLogin() {
  error.value = ''
  loading.value = true
  try {
    await login(username.value, password.value)
    router.push('/dashboard')
  } catch (err) {
    error.value = err.response?.data?.error?.message || 'Login failed'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <form @submit.prevent="handleLogin" class="space-y-4">
    <AppInput v-model="username" label="Username" placeholder="Enter username" required />
    <AppInput v-model="password" label="Password" type="password" placeholder="Enter password" required />
    <AlertMessage :message="error" />
    <AppButton type="submit" :loading="loading" full-width>
      {{ loading ? 'Logging in...' : 'Log In' }}
    </AppButton>
  </form>
  <p class="text-center text-body text-sm mt-4">
    Don't have an account?
    <router-link to="/register" class="text-link hover:text-link-hover">Register</router-link>
  </p>
</template>
