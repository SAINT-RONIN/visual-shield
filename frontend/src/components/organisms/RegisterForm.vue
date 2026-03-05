<script setup>
import { ref } from 'vue'
import AppInput from '@/components/atoms/AppInput.vue'
import AppButton from '@/components/atoms/AppButton.vue'
import AlertMessage from '@/components/atoms/AlertMessage.vue'

defineProps({
  loading: { type: Boolean, default: false },
  error: { type: String, default: '' },
})

const emit = defineEmits(['submit'])

const username = ref('')
const displayName = ref('')
const password = ref('')
const confirmPassword = ref('')
const localError = ref('')

function handleSubmit() {
  localError.value = ''
  if (password.value !== confirmPassword.value) {
    localError.value = 'Passwords do not match'
    return
  }
  emit('submit', {
    username: username.value,
    password: password.value,
    displayName: displayName.value || null,
  })
}
</script>

<template>
  <form @submit.prevent="handleSubmit" class="space-y-4">
    <AppInput v-model="username" label="Username" placeholder="Choose a username" required />
    <AppInput v-model="displayName" label="Display Name" placeholder="Your display name (optional)" />
    <AppInput v-model="password" label="Password" type="password" placeholder="Create a password" required />
    <AppInput v-model="confirmPassword" label="Confirm Password" type="password" placeholder="Confirm your password" required />
    <AlertMessage :message="localError || error" />
    <AppButton type="submit" :loading="loading" full-width>
      {{ loading ? 'Creating account...' : 'Register' }}
    </AppButton>
  </form>
  <p class="text-center text-body text-sm mt-4">
    Already have an account?
    <router-link to="/login" class="text-link hover:text-link-hover">Log In</router-link>
  </p>
</template>
