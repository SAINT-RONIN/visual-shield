<script setup>
// Page: AdminPage is the route-level view for listing users and changing their roles.
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { fetchUsers, updateUserRole } from '@/api/admin.js'
import { formatDateShort } from '@/utils/formatters.js'
import { useAuth } from '@/composables/useAuth.js'
import PageTemplate from '@/components/templates/PageTemplate.vue'
import AppButton from '@/components/atoms/AppButton.vue'
import Spinner from '@/components/atoms/Spinner.vue'
import AlertMessage from '@/components/atoms/AlertMessage.vue'
import RoleBadge from '@/components/atoms/RoleBadge.vue'

const router = useRouter()
const { user: authUser, setUser } = useAuth()
const users = ref([])
const loading = ref(true)
const error = ref('')
const accessDenied = ref(false)
const updatingRole = ref(null)
const adminCount = ref(0)

const roleOptions = ['admin', 'viewer']

onMounted(async () => {
  try {
    const result = await fetchUsers()
    users.value = result.users
    adminCount.value = result.adminCount || result.users.filter((user) => user.role === 'admin').length
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

function isLastAdmin(user) {
  return user.role === 'admin' && adminCount.value <= 1
}

async function changeRole(userId, newRole) {
  const user = users.value.find((candidate) => candidate.id === userId)

  if (!user || (user.role === 'admin' && newRole !== 'admin' && adminCount.value <= 1)) {
    error.value = 'At least one admin account must remain'
    return
  }

  const previousRole = user.role
  updatingRole.value = userId
  error.value = ''

  try {
    const updatedUser = await updateUserRole(userId, newRole)
    Object.assign(user, updatedUser)

    if (previousRole === 'admin' && updatedUser.role !== 'admin') {
      adminCount.value = Math.max(0, adminCount.value - 1)
    } else if (previousRole !== 'admin' && updatedUser.role === 'admin') {
      adminCount.value += 1
    }

    if (authUser.value?.id === userId) {
      setUser({ ...authUser.value, role: updatedUser.role })

      if (updatedUser.role !== 'admin') {
        router.replace({ name: 'dashboard' })
      }
    }
  } catch (err) {
    error.value = err.response?.data?.error?.message || 'Failed to update role'
  } finally {
    updatingRole.value = null
  }
}


</script>

<template>
  <PageTemplate title="Admin Panel">
    <div v-if="loading" class="flex items-center justify-center py-20">
      <Spinner size="lg" />
    </div>

    <div v-else-if="accessDenied" class="text-center py-16">
      <svg class="w-16 h-16 mx-auto mb-4 text-error" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
      </svg>
      <p class="text-heading text-lg font-semibold mb-2">Access Denied</p>
      <p class="text-muted text-sm">You do not have permission to view this page.</p>
    </div>

    <AlertMessage v-else-if="error && users.length === 0" type="error" :message="error" />

    <div v-else-if="users.length === 0" class="text-center py-12">
      <p class="text-muted text-sm">No users found.</p>
    </div>

    <div v-else>
      <AlertMessage v-if="error" type="error" :message="error" />

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
              v-for="user in users"
              :key="user.id"
              class="border-b border-line last:border-0 transition-colors hover:bg-surface-alt/50"
            >
              <td class="px-3 sm:px-5 py-3.5 text-muted font-mono text-xs">{{ user.id }}</td>
              <td class="px-3 sm:px-5 py-3.5 text-heading font-medium">{{ user.username }}</td>
              <td class="px-3 sm:px-5 py-3.5 text-body hidden sm:table-cell">{{ user.displayName || '--' }}</td>
              <td class="px-3 sm:px-5 py-3.5">
                <RoleBadge :role="user.role" />
              </td>
              <td class="px-3 sm:px-5 py-3.5 text-muted text-xs hidden md:table-cell">{{ formatDateShort(user.createdAt) }}</td>
              <td class="px-3 sm:px-5 py-3.5">
                <div class="flex gap-1 flex-wrap">
                  <AppButton
                    v-for="role in roleOptions.filter(option => option !== user.role)"
                    :key="role"
                    variant="secondary"
                    size="sm"
                    :disabled="updatingRole === user.id || (role === 'viewer' && isLastAdmin(user))"
                    @click="changeRole(user.id, role)"
                  >
                    {{ updatingRole === user.id ? '...' : `Make ${role}` }}
                  </AppButton>
                </div>
                <p v-if="isLastAdmin(user)" class="mt-2 text-xs text-muted">
                  At least one admin account must remain.
                </p>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </PageTemplate>
</template>
