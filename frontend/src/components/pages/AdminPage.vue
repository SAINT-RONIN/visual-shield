<script setup>
import { ref, onMounted } from 'vue'
import api from '@/utils/api.js'
import { formatDateShort } from '@/utils/formatters.js'
import PageTemplate from '@/components/templates/PageTemplate.vue'
import AppButton from '@/components/atoms/AppButton.vue'

const users = ref([])
const loading = ref(true)
const error = ref('')
const accessDenied = ref(false)
const updatingRole = ref(null)

const roleOptions = ['admin', 'viewer']

onMounted(async () => {
  try {
    const { data } = await api.get('/admin/users')
    users.value = Array.isArray(data) ? data : data.data ?? []
  } catch (err) {
    if (err.response?.status === 403) {
      accessDenied.value = true
    } else {
      error.value = err.response?.data?.error?.message || 'Failed to load users'
    }
  } finally {
    loading.value = false
  }
})

async function changeRole(userId, newRole) {
  updatingRole.value = userId
  error.value = ''
  try {
    await api.patch(`/admin/users/${userId}/role`, { role: newRole })
    const user = users.value.find((u) => u.id === userId)
    if (user) user.role = newRole
  } catch (err) {
    error.value = err.response?.data?.error?.message || 'Failed to update role'
  } finally {
    updatingRole.value = null
  }
}


</script>

<template>
  <PageTemplate title="Admin Panel">
    <div v-if="loading" class="text-body text-center py-12">Loading users...</div>

    <div v-else-if="accessDenied" class="text-center py-16">
      <svg class="w-16 h-16 mx-auto mb-4 text-error" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
      </svg>
      <p class="text-heading text-lg font-semibold mb-2">Access Denied</p>
      <p class="text-muted text-sm">You do not have permission to view this page.</p>
    </div>

    <div v-else-if="error && users.length === 0" class="text-error text-center py-12">{{ error }}</div>

    <div v-else-if="users.length === 0" class="text-center py-12">
      <p class="text-muted text-sm">No users found.</p>
    </div>

    <div v-else>
      <div v-if="error" class="text-error text-sm mb-4">{{ error }}</div>

      <div class="overflow-x-auto rounded-2xl border border-line bg-surface">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b border-line text-left text-muted">
              <th class="px-3 sm:px-5 py-3 font-medium text-xs">ID</th>
              <th class="px-3 sm:px-5 py-3 font-medium text-xs">Username</th>
              <th class="px-3 sm:px-5 py-3 font-medium text-xs hidden sm:table-cell">Display Name</th>
              <th class="px-3 sm:px-5 py-3 font-medium text-xs">Role</th>
              <th class="px-3 sm:px-5 py-3 font-medium text-xs hidden md:table-cell">Created</th>
              <th class="px-3 sm:px-5 py-3 font-medium text-xs">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="u in users"
              :key="u.id"
              class="border-b border-line last:border-0 transition-colors hover:bg-surface-alt/50"
            >
              <td class="px-3 sm:px-5 py-3.5 text-muted font-mono text-xs">{{ u.id }}</td>
              <td class="px-3 sm:px-5 py-3.5 text-heading font-medium">{{ u.username }}</td>
              <td class="px-3 sm:px-5 py-3.5 text-body hidden sm:table-cell">{{ u.displayName || '--' }}</td>
              <td class="px-3 sm:px-5 py-3.5">
                <span
                  class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                  :class="u.role === 'admin'
                    ? 'bg-primary/15 text-primary'
                    : 'bg-surface-alt text-muted'"
                >
                  {{ u.role }}
                </span>
              </td>
              <td class="px-3 sm:px-5 py-3.5 text-muted text-xs hidden md:table-cell">{{ formatDateShort(u.createdAt) }}</td>
              <td class="px-3 sm:px-5 py-3.5">
                <div class="flex gap-1 flex-wrap">
                  <AppButton
                    v-for="role in roleOptions.filter(r => r !== u.role)"
                    :key="role"
                    variant="secondary"
                    size="sm"
                    :disabled="updatingRole === u.id"
                    @click="changeRole(u.id, role)"
                  >
                    {{ updatingRole === u.id ? '...' : `Make ${role}` }}
                  </AppButton>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </PageTemplate>
</template>
