<script setup>
// Page: LoginPage coordinates the login route state and hands credentials to the auth flow.
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { login } from '@/api/auth.js'
import { useAuth } from '@/composables/useAuth.js'
import AuthTemplate from '@/components/templates/AuthTemplate.vue'
import LoginForm from '@/components/organisms/LoginForm.vue'

const router = useRouter()
const { setAuth } = useAuth()

const loading = ref(false)
const error = ref('')

async function handleSubmit({ username, password }) {
  loading.value = true
  error.value = ''
  try {
    const data = await login(username, password)
    setAuth(data.token, data.user)
    router.push('/dashboard')
  } catch (err) {
    error.value = err.response?.data?.error?.message || 'Login failed'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <AuthTemplate>
    <LoginForm :loading="loading" :error="error" @submit="handleSubmit" />
  </AuthTemplate>
</template>
