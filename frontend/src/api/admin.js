import api from '@/utils/api'

export async function fetchUsers() {
  const { data } = await api.get('/users')
  if (Array.isArray(data)) return data
  return data.data ?? []
}

export async function updateUserRole(userId, role) {
  const { data } = await api.patch(`/users/${userId}`, { role })
  return data
}
