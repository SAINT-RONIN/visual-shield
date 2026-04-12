<script setup>
// Organism: ProfileForm lets the user review and update their editable profile details.
import { ref, reactive, watch } from 'vue'
import AppInput from '@/components/atoms/AppInput.vue'
import AppButton from '@/components/atoms/AppButton.vue'
import AlertMessage from '@/components/atoms/AlertMessage.vue'
import Spinner from '@/components/atoms/Spinner.vue'
import { formatDateShort } from '@/utils/formatters.js'

const props = defineProps({
  user: { type: Object, default: null },
  loading: { type: Boolean, default: false },
  saving: { type: Boolean, default: false },
  error: { type: String, default: '' },
})

const emit = defineEmits(['save'])

const displayName = ref('')

const errors = reactive({
  displayName: '',
})

watch(() => props.user, (newUser) => {
  if (newUser) displayName.value = newUser.displayName || ''
}, { immediate: true })

function handleSave() {
  errors.displayName = ''
  if (!displayName.value.trim()) {
    errors.displayName = 'Display name is required'
    return
  }
  emit('save', displayName.value)
}
</script>

<template>
  <div v-if="loading" class="flex items-center justify-center py-20">
    <Spinner size="lg" />
  </div>

  <section v-else class="bg-surface border border-line rounded-xl p-4 md:p-5 lg:p-6 space-y-6">
    <div>
      <label class="block text-sm text-body mb-1">Username</label>
      <p class="text-heading">{{ user?.username }}</p>
    </div>
    <div>
      <AppInput
        v-model="displayName"
        label="Display Name"
        placeholder="Enter display name"
        @input="errors.displayName = ''"
      />
      <p v-if="errors.displayName" class="text-error text-sm mt-1">{{ errors.displayName }}</p>
    </div>
    <AlertMessage :message="error" />
    <AppButton :loading="saving" @click="handleSave">
      {{ saving ? 'Saving...' : 'Save Changes' }}
    </AppButton>
  </section>

  <footer class="mt-4 text-sm text-muted">
    <p>Member since {{ formatDateShort(user?.createdAt) }}</p>
  </footer>
</template>
