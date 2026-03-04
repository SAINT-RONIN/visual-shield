<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuth } from '@/composables/useAuth.js'
import AppInput from '@/components/atoms/AppInput.vue'
import AppButton from '@/components/atoms/AppButton.vue'
import AlertMessage from '@/components/atoms/AlertMessage.vue'

const router = useRouter()
const { register } = useAuth()

const username = ref('')
const displayName = ref('')
const password = ref('')
const confirmPassword = ref('')
const error = ref('')
const loading = ref(false)

async function handleRegister() {
  error.value = ''
  if (password.value !== confirmPassword.value) {
    error.value = 'Passwords do not match'
    return
  }
  loading.value = true
  try {
    await register(username.value, password.value, displayName.value || null)
    router.push('/login')
  } catch (err) {
    error.value = err.response?.data?.error?.message || 'Registration failed'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <form @submit.prevent="handleRegister" class="space-y-4">
    <AppInput v-model="username" label="Username" placeholder="Choose a username" required />
    <AppInput v-model="displayName" label="Display Name" placeholder="Your display name (optional)" />
    <AppInput v-model="password" label="Password" type="password" placeholder="Create a password" required />
    <AppInput v-model="confirmPassword" label="Confirm Password" type="password" placeholder="Confirm your password" required />
    <AlertMessage :message="error" />
    <AppButton type="submit" :loading="loading" full-width>
      {{ loading ? 'Creating account...' : 'Register' }}
    </AppButton>
  </form>
  <p class="text-center text-body text-sm mt-4">
    Already have an account?
    <router-link to="/login" class="text-link hover:text-link-hover">Log In</router-link>
  </p>
</template>
