import api from '@/utils/api'

export async function fetchUsers() {
  const { data } = await api.get('/users')
  if (Array.isArray(data)) {
    return {
      users: data,
      adminCount: data.filter((user) => user.role === 'admin').length,
    }
  }

  return {
    users: data.data ?? [],
    adminCount: data.summary?.adminCount ?? 0,
  }
}

export async function updateUserRole(userId, role) {
  const { data } = await api.patch(`/users/${userId}`, { role })
  return data.data ?? data
}

export async function deactivateUser(userId) {
  const { data } = await api.patch(`/admin/users/${userId}/deactivate`)
  return data.data ?? data
}

export async function activateUser(userId) {
  const { data } = await api.patch(`/admin/users/${userId}/activate`)
  return data.data ?? data
}

export async function createUser(username, password, displayName, role) {
  const { data } = await api.post('/admin/users', { username, password, displayName, role })
  return data.data ?? data
}

export async function resetUserPassword(userId, newPassword) {
  await api.patch(`/admin/users/${userId}/password`, { newPassword })
}
