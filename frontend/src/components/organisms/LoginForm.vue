<script setup>
// Organism: LoginForm handles the login fields, local validation, and submit event for the login page.
import { ref, reactive } from 'vue'
import AppInput from '@/components/atoms/AppInput.vue'
import AppButton from '@/components/atoms/AppButton.vue'
import AlertMessage from '@/components/atoms/AlertMessage.vue'

defineProps({
  loading: { type: Boolean, default: false },
  error: { type: String, default: '' },
})

const emit = defineEmits(['submit'])

const username = ref('')
const password = ref('')

const errors = reactive({
  username: '',
  password: '',
})

function handleSubmit() {
  errors.username = ''
  errors.password = ''

  let valid = true
  if (!username.value.trim()) {
    errors.username = 'Username is required'
    valid = false
  }
  if (!password.value) {
    errors.password = 'Password is required'
    valid = false
  }
  if (!valid) return

  emit('submit', { username: username.value, password: password.value })
}
</script>

<template>
  <form @submit.prevent="handleSubmit" class="space-y-4">
    <div>
      <AppInput
        v-model="username"
        label="Username"
        placeholder="Enter username"
        @input="errors.username = ''"
      />
      <span v-if="errors.username" class="text-error text-sm mt-1 block">{{ errors.username }}</span>
    </div>
    <div>
      <AppInput
        v-model="password"
        label="Password"
        type="password"
        placeholder="Enter password"
        @input="errors.password = ''"
      />
      <span v-if="errors.password" class="text-error text-sm mt-1 block">{{ errors.password }}</span>
    </div>
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
