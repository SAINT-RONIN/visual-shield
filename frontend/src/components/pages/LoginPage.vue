<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/utils/api.js'
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
    const { data } = await api.post('/auth/login', { username, password })
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
