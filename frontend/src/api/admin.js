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
