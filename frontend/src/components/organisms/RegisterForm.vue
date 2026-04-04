<script setup>
// Organism: RegisterForm handles account creation fields, validation, and submit events.
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
const displayName = ref('')
const password = ref('')
const confirmPassword = ref('')

const errors = reactive({
  username: '',
  displayName: '',
  password: '',
  confirmPassword: '',
})

function handleSubmit() {
  errors.username = ''
  errors.displayName = ''
  errors.password = ''
  errors.confirmPassword = ''

  let valid = true

  if (!username.value.trim()) {
    errors.username = 'Username is required'
    valid = false
  }
  if (password.value.length < 8) {
    errors.password = 'Password must be at least 8 characters'
    valid = false
  }
  if (password.value !== confirmPassword.value) {
    errors.confirmPassword = 'Passwords do not match'
    valid = false
  }
  if (!valid) return

  emit('submit', {
    username: username.value,
    password: password.value,
    displayName: displayName.value || null,
  })
}
</script>

<template>
  <form @submit.prevent="handleSubmit" class="space-y-4">
    <div>
      <AppInput
        v-model="username"
        label="Username"
        placeholder="Choose a username"
        @input="errors.username = ''"
      />
      <span v-if="errors.username" class="text-error text-sm mt-1 block">{{ errors.username }}</span>
    </div>
    <div>
      <AppInput
        v-model="displayName"
        label="Display Name"
        placeholder="Your display name (optional)"
        @input="errors.displayName = ''"
      />
      <span v-if="errors.displayName" class="text-error text-sm mt-1 block">{{ errors.displayName }}</span>
    </div>
    <div>
      <AppInput
        v-model="password"
        label="Password"
        type="password"
        placeholder="Create a password"
        @input="errors.password = ''"
      />
      <span v-if="errors.password" class="text-error text-sm mt-1 block">{{ errors.password }}</span>
    </div>
    <div>
      <AppInput
        v-model="confirmPassword"
        label="Confirm Password"
        type="password"
        placeholder="Confirm your password"
        @input="errors.confirmPassword = ''"
      />
      <span v-if="errors.confirmPassword" class="text-error text-sm mt-1 block">{{ errors.confirmPassword }}</span>
    </div>
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
