<script setup>
// Organism: ChangePasswordForm lets an authenticated user change their own password.
import { ref } from 'vue'
import AppInput from '@/components/atoms/AppInput.vue'
import AppButton from '@/components/atoms/AppButton.vue'
import AlertMessage from '@/components/atoms/AlertMessage.vue'

defineProps({
  saving: { type: Boolean, default: false },
  error: { type: String, default: '' },
})

const emit = defineEmits(['change'])

const currentPassword = ref('')
const newPassword = ref('')
const confirmPassword = ref('')
const localError = ref('')

function handleSubmit() {
  localError.value = ''

  if (!currentPassword.value) {
    localError.value = 'Current password is required'
    return
  }

  if (!newPassword.value) {
    localError.value = 'New password is required'
    return
  }

  if (newPassword.value.length < 8) {
    localError.value = 'New password must be at least 8 characters'
    return
  }

  if (newPassword.value !== confirmPassword.value) {
    localError.value = 'Passwords do not match'
    return
  }

  emit('change', currentPassword.value, newPassword.value)
  currentPassword.value = ''
  newPassword.value = ''
  confirmPassword.value = ''
}
</script>

<template>
  <div class="bg-surface border border-line rounded-xl p-4 md:p-5 lg:p-6 space-y-4">
    <h2 class="text-sm font-semibold text-heading">Change Password</h2>

    <AppInput
      v-model="currentPassword"
      label="Current Password"
      type="password"
      placeholder="Enter current password"
      @input="localError = ''"
    />
    <AppInput
      v-model="newPassword"
      label="New Password"
      type="password"
      placeholder="At least 8 characters"
      @input="localError = ''"
    />
    <AppInput
      v-model="confirmPassword"
      label="Confirm New Password"
      type="password"
      placeholder="Repeat new password"
      @input="localError = ''"
    />

    <AlertMessage :message="localError || error" />

    <AppButton :loading="saving" :disabled="saving" @click="handleSubmit">
      {{ saving ? 'Saving...' : 'Change Password' }}
    </AppButton>
  </div>
</template>
