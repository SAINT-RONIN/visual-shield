<script setup>
import AppInput from '@/components/atoms/AppInput.vue'
import AppButton from '@/components/atoms/AppButton.vue'
import AlertMessage from '@/components/atoms/AlertMessage.vue'

defineProps({
  loading: { type: Boolean, default: false },
  error: { type: String, default: '' },
})

const emit = defineEmits(['submit'])

import { ref } from 'vue'

const username = ref('')
const password = ref('')

function handleSubmit() {
  emit('submit', { username: username.value, password: password.value })
}
</script>

<template>
  <form @submit.prevent="handleSubmit" class="space-y-4">
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
