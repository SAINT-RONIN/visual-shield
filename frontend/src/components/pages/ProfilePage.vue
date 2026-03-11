<script setup>
import { ref, onMounted } from 'vue'
import { useAuth } from '@/composables/useAuth.js'
import { useToast } from '@/composables/useToast.js'
import PageTemplate from '@/components/templates/PageTemplate.vue'
import ProfileForm from '@/components/organisms/ProfileForm.vue'

const { user, fetchProfile, updateProfile } = useAuth()
const { showToast } = useToast()

const loading = ref(true)
const saving = ref(false)
const error = ref('')

onMounted(async () => {
  try {
    await fetchProfile()
  } catch (err) {
    error.value = err.response?.data?.error?.message || 'Failed to load profile'
  } finally {
    loading.value = false
  }
})

async function handleSave(displayName) {
  saving.value = true
  error.value = ''
  try {
    await updateProfile(displayName)
    showToast('Profile updated', 'success')
  } catch (err) {
    error.value = err.response?.data?.error?.message || 'Failed to update profile'
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <PageTemplate title="Profile" max-width="max-w-lg">
    <ProfileForm
      :user="user"
      :loading="loading"
      :saving="saving"
      :error="error"
      @save="handleSave"
    />
  </PageTemplate>
</template>
