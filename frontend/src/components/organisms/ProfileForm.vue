<script setup>
import { ref, onMounted } from 'vue'
import { useAuth } from '@/composables/useAuth.js'
import AppInput from '@/components/atoms/AppInput.vue'
import AppButton from '@/components/atoms/AppButton.vue'
import AlertMessage from '@/components/atoms/AlertMessage.vue'

const { user, fetchProfile, updateProfile } = useAuth()

const displayName = ref('')
const loading = ref(false)
const saving = ref(false)
const error = ref('')
const success = ref('')

onMounted(async () => {
  loading.value = true
  try {
    await fetchProfile()
    displayName.value = user.value?.displayName || ''
  } catch {
    error.value = 'Failed to load profile'
  } finally {
    loading.value = false
  }
})

async function handleSave() {
  error.value = ''
  success.value = ''
  saving.value = true
  try {
    await updateProfile(displayName.value)
    success.value = 'Profile updated successfully'
  } catch (err) {
    error.value = err.response?.data?.error?.message || 'Failed to update profile'
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <div v-if="loading" class="text-body">Loading profile...</div>

  <div v-else class="bg-surface border border-line rounded-xl p-6 space-y-6">
    <div>
      <label class="block text-sm text-body mb-1">Username</label>
      <p class="text-heading">{{ user?.username }}</p>
    </div>
    <AppInput v-model="displayName" label="Display Name" placeholder="Enter display name" />
    <AlertMessage :message="error" />
    <AlertMessage type="success" :message="success" />
    <AppButton :loading="saving" @click="handleSave">
      {{ saving ? 'Saving...' : 'Save Changes' }}
    </AppButton>
  </div>

  <div class="mt-4 text-sm text-muted">
    <p>Member since {{ user?.createdAt }}</p>
  </div>
</template>
