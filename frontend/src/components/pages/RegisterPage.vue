<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/utils/api.js'
import AuthTemplate from '@/components/templates/AuthTemplate.vue'
import RegisterForm from '@/components/organisms/RegisterForm.vue'

const router = useRouter()

const loading = ref(false)
const error = ref('')

async function handleSubmit({ username, password, displayName }) {
  loading.value = true
  error.value = ''
  try {
    await api.post('/auth/register', { username, password, displayName })
    router.push('/login')
  } catch (err) {
    error.value = err.response?.data?.error?.message || 'Registration failed'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <AuthTemplate>
    <RegisterForm :loading="loading" :error="error" @submit="handleSubmit" />
  </AuthTemplate>
</template>
