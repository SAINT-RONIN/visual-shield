<script setup>
// Page: ProfilePage loads the current user profile and handles profile update requests.
import { ref, onMounted } from 'vue'
import { getProfile, updateProfile, changePassword } from '@/api/users.js'
import { useAuth } from '@/composables/useAuth.js'
import { useToast } from '@/composables/useToast.js'
import PageTemplate from '@/components/templates/PageTemplate.vue'
import ProfileForm from '@/components/organisms/ProfileForm.vue'
import ChangePasswordForm from '@/components/organisms/ChangePasswordForm.vue'

const { user, setUser } = useAuth()
const { showToast } = useToast()

const loading = ref(true)
const saving = ref(false)
const profileError = ref('')

const changingPassword = ref(false)
const passwordError = ref('')

onMounted(async () => {
  try {
    const data = await getProfile()
    setUser(data)
  } catch (err) {
    profileError.value = err.response?.data?.error?.message || 'Failed to load profile'
  } finally {
    loading.value = false
  }
})

async function handleSave(displayName) {
  saving.value = true
  profileError.value = ''
  try {
    const data = await updateProfile(displayName)
    setUser(data)
    showToast('Profile updated', 'success')
  } catch (err) {
    profileError.value = err.response?.data?.error?.message || 'Failed to update profile'
  } finally {
    saving.value = false
  }
}

async function handleChangePassword(currentPassword, newPassword) {
  changingPassword.value = true
  passwordError.value = ''
  try {
    await changePassword(currentPassword, newPassword)
    showToast('Password changed', 'success')
  } catch (err) {
    passwordError.value = err.response?.data?.error?.message || 'Failed to change password'
  } finally {
    changingPassword.value = false
  }
}
</script>

<template>
  <PageTemplate title="Profile" max-width="max-w-lg">
    <ProfileForm
      :user="user"
      :loading="loading"
      :saving="saving"
      :error="profileError"
      @save="handleSave"
    />
    <ChangePasswordForm
      v-if="!loading"
      :saving="changingPassword"
      :error="passwordError"
      class="mt-6"
      @change="handleChangePassword"
    />
  </PageTemplate>
</template>
