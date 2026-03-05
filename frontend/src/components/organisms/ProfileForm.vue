<script setup>
import { ref, watch } from 'vue'
import AppInput from '@/components/atoms/AppInput.vue'
import AppButton from '@/components/atoms/AppButton.vue'
import AlertMessage from '@/components/atoms/AlertMessage.vue'

const props = defineProps({
  user: { type: Object, default: null },
  loading: { type: Boolean, default: false },
  saving: { type: Boolean, default: false },
  error: { type: String, default: '' },
  success: { type: String, default: '' },
})

const emit = defineEmits(['save'])

const displayName = ref('')

watch(() => props.user, (u) => {
  if (u) displayName.value = u.displayName || ''
}, { immediate: true })

function handleSave() {
  emit('save', displayName.value)
}
</script>

<template>
  <div v-if="loading" class="text-body">Loading profile...</div>

  <div v-else class="bg-surface border border-line rounded-xl p-4 md:p-5 lg:p-6 space-y-6">
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
