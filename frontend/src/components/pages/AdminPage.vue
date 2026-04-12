<script setup>
// Page: AdminPage is the route-level view for listing users and changing their roles.
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { fetchUsers, updateUserRole, deactivateUser, activateUser, createUser, resetUserPassword } from '@/api/admin.js'
import { formatDateShort } from '@/utils/formatters.js'
import { useAuth } from '@/composables/useAuth.js'
import PageTemplate from '@/components/templates/PageTemplate.vue'
import AppButton from '@/components/atoms/AppButton.vue'
import AppInput from '@/components/atoms/AppInput.vue'
import AppSelect from '@/components/atoms/AppSelect.vue'
import Spinner from '@/components/atoms/Spinner.vue'
import AlertMessage from '@/components/atoms/AlertMessage.vue'
import RoleBadge from '@/components/atoms/RoleBadge.vue'
import UserStatusBadge from '@/components/atoms/UserStatusBadge.vue'

const router = useRouter()
const { user: authUser, setUser } = useAuth()
const users = ref([])
const loading = ref(true)
const error = ref('')
const accessDenied = ref(false)
const updatingRole = ref(null)
const updatingStatus = ref(null)
const adminCount = ref(0)

const roleOptions = ['admin', 'member']

// Create user modal state
const showCreateModal = ref(false)
const creating = ref(false)
const createError = ref('')
const createForm = ref({ username: '', password: '', displayName: '', role: 'member' })

// Reset password modal state
const showResetModal = ref(false)
const resettingPassword = ref(false)
const resetError = ref('')
const resetTargetUser = ref(null)
const resetForm = ref({ newPassword: '', confirmPassword: '' })

function openResetModal(user) {
  resetTargetUser.value = user
  resetForm.value = { newPassword: '', confirmPassword: '' }
  resetError.value = ''
  showResetModal.value = true
}

function closeResetModal() {
  showResetModal.value = false
  resetTargetUser.value = null
}

async function submitResetPassword() {
  resetError.value = ''

  if (!resetForm.value.newPassword) {
    resetError.value = 'New password is required'
    return
  }

  if (resetForm.value.newPassword.length < 8) {
    resetError.value = 'Password must be at least 8 characters'
    return
  }

  if (resetForm.value.newPassword !== resetForm.value.confirmPassword) {
    resetError.value = 'Passwords do not match'
    return
  }

  resettingPassword.value = true

  try {
    await resetUserPassword(resetTargetUser.value.id, resetForm.value.newPassword)
    closeResetModal()
  } catch (err) {
    resetError.value = err.response?.data?.error?.message || 'Failed to reset password'
  } finally {
    resettingPassword.value = false
  }
}

const roleSelectOptions = [
  { value: 'member', label: 'Member' },
  { value: 'admin', label: 'Admin' },
]

function openCreateModal() {
  createForm.value = { username: '', password: '', displayName: '', role: 'member' }
  createError.value = ''
  showCreateModal.value = true
}

function closeCreateModal() {
  showCreateModal.value = false
}

async function submitCreateUser() {
  createError.value = ''

  if (!createForm.value.username.trim()) {
    createError.value = 'Username is required'
    return
  }

  if (!createForm.value.password) {
    createError.value = 'Password is required'
    return
  }

  creating.value = true

  try {
    const newUser = await createUser(
      createForm.value.username.trim(),
      createForm.value.password,
      createForm.value.displayName.trim() || null,
      createForm.value.role,
    )
    users.value.push(newUser)
    if (newUser.role === 'admin') {
      adminCount.value += 1
    }
    closeCreateModal()
  } catch (err) {
    createError.value = err.response?.data?.error?.message || 'Failed to create user'
  } finally {
    creating.value = false
  }
}

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

function isLastActiveAdmin(user) {
  return user.role === 'admin' && user.isActive && users.value.filter((u) => u.role === 'admin' && u.isActive).length <= 1
}

async function changeStatus(userId, activate) {
  const user = users.value.find((candidate) => candidate.id === userId)
  if (!user) return

  if (!activate && isLastActiveAdmin(user)) {
    error.value = 'At least one active admin account must remain'
    return
  }

  updatingStatus.value = userId
  error.value = ''

  try {
    const updatedUser = activate ? await activateUser(userId) : await deactivateUser(userId)
    Object.assign(user, updatedUser)
  } catch (err) {
    error.value = err.response?.data?.error?.message || 'Failed to update status'
  } finally {
    updatingStatus.value = null
  }
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

    <!-- Reset Password Modal -->
    <Teleport to="body">
      <div
        v-if="showResetModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4"
        @click.self="closeResetModal"
      >
        <dialog open class="bg-surface border border-line rounded-2xl w-full max-w-md p-6 shadow-xl m-0">
          <h2 class="text-lg font-semibold text-heading mb-1">Reset Password</h2>
          <p class="text-sm text-muted mb-5">
            Setting a new password for <span class="text-heading font-medium">{{ resetTargetUser?.username }}</span>
          </p>

          <form class="flex flex-col gap-4" @submit.prevent="submitResetPassword">
            <AppInput
              v-model="resetForm.newPassword"
              label="New Password"
              type="password"
              placeholder="At least 8 characters"
              required
            />
            <AppInput
              v-model="resetForm.confirmPassword"
              label="Confirm New Password"
              type="password"
              placeholder="Repeat new password"
              required
            />

            <AlertMessage type="error" :message="resetError" />

            <div class="flex gap-3 pt-1">
              <AppButton
                type="button"
                variant="secondary"
                fullWidth
                :disabled="resettingPassword"
                @click="closeResetModal"
              >
                Cancel
              </AppButton>
              <AppButton
                type="submit"
                variant="primary"
                fullWidth
                :loading="resettingPassword"
                :disabled="resettingPassword"
              >
                {{ resettingPassword ? 'Resetting…' : 'Reset Password' }}
              </AppButton>
            </div>
          </form>
        </dialog>
      </div>
    </Teleport>

    <!-- Create User Modal -->
    <Teleport to="body">
      <div
        v-if="showCreateModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4"
        @click.self="closeCreateModal"
      >
        <dialog open class="bg-surface border border-line rounded-2xl w-full max-w-md p-6 shadow-xl m-0">
          <h2 class="text-lg font-semibold text-heading mb-5">Create User</h2>

          <form class="flex flex-col gap-4" @submit.prevent="submitCreateUser">
            <AppInput
              v-model="createForm.username"
              label="Username"
              placeholder="e.g. jdoe"
              required
            />
            <AppInput
              v-model="createForm.displayName"
              label="Display Name"
              placeholder="e.g. Jane Doe (optional)"
            />
            <AppInput
              v-model="createForm.password"
              label="Password"
              type="password"
              placeholder="Enter a password"
              required
            />
            <AppSelect
              v-model="createForm.role"
              label="Role"
              :options="roleSelectOptions"
            />

            <AlertMessage type="error" :message="createError" />

            <div class="flex gap-3 pt-1">
              <AppButton
                type="button"
                variant="secondary"
                fullWidth
                :disabled="creating"
                @click="closeCreateModal"
              >
                Cancel
              </AppButton>
              <AppButton
                type="submit"
                variant="primary"
                fullWidth
                :loading="creating"
                :disabled="creating"
              >
                {{ creating ? 'Creating…' : 'Create User' }}
              </AppButton>
            </div>
          </form>
        </dialog>
      </div>
    </Teleport>

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

    <template v-else>
      <header class="flex items-center justify-end mb-4">
        <AppButton variant="primary" @click="openCreateModal">Create User</AppButton>
      </header>

      <AlertMessage v-if="error" type="error" :message="error" class="mb-4" />

      <p v-if="users.length === 0" class="text-center py-12 text-muted text-sm">No users found.</p>

      <div v-else class="overflow-x-auto rounded-2xl border border-line bg-surface">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b border-line text-left text-muted">
              <th class="px-3 sm:px-5 py-3 font-medium text-xs">ID</th>
              <th class="px-3 sm:px-5 py-3 font-medium text-xs">Username</th>
              <th class="px-3 sm:px-5 py-3 font-medium text-xs hidden sm:table-cell">Display Name</th>
              <th class="px-3 sm:px-5 py-3 font-medium text-xs">Role</th>
              <th class="px-3 sm:px-5 py-3 font-medium text-xs hidden sm:table-cell">Status</th>
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
              <td class="px-3 sm:px-5 py-3.5 hidden sm:table-cell">
                <UserStatusBadge :isActive="user.isActive" />
              </td>
              <td class="px-3 sm:px-5 py-3.5 text-muted text-xs hidden md:table-cell">{{ formatDateShort(user.createdAt) }}</td>
              <td class="px-3 sm:px-5 py-3.5">
                <div class="flex gap-1 flex-wrap">
                  <AppButton
                    v-for="role in roleOptions.filter(option => option !== user.role)"
                    :key="role"
                    variant="secondary"
                    size="sm"
                    :disabled="updatingRole === user.id || updatingStatus === user.id || (role === 'member' && isLastAdmin(user))"
                    @click="changeRole(user.id, role)"
                  >
                    {{ updatingRole === user.id ? '...' : `Make ${role}` }}
                  </AppButton>
                  <AppButton
                    v-if="user.isActive"
                    variant="danger"
                    size="sm"
                    :disabled="updatingStatus === user.id || updatingRole === user.id || isLastActiveAdmin(user)"
                    @click="changeStatus(user.id, false)"
                  >
                    {{ updatingStatus === user.id ? '...' : 'Deactivate' }}
                  </AppButton>
                  <AppButton
                    v-else
                    variant="secondary"
                    size="sm"
                    :disabled="updatingStatus === user.id || updatingRole === user.id"
                    @click="changeStatus(user.id, true)"
                  >
                    {{ updatingStatus === user.id ? '...' : 'Activate' }}
                  </AppButton>
                  <AppButton
                    variant="secondary"
                    size="sm"
                    :disabled="updatingRole === user.id || updatingStatus === user.id"
                    @click="openResetModal(user)"
                  >
                    Reset Password
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
    </template>

  </PageTemplate>
</template>
