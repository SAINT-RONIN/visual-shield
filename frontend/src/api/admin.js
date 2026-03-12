import api from '@/utils/api'

export async function fetchUsers() {
  const { data } = await api.get('/admin/users')
  return Array.isArray(data) ? data : data.data ?? []
}

export async function updateUserRole(userId, role) {
  const { data } = await api.patch(`/admin/users/${userId}/role`, { role })
  return data
}
