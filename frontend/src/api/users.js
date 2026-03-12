import api from '@/utils/api'

export async function getProfile() {
  const { data } = await api.get('/users/me')
  return data
}

export async function updateProfile(displayName) {
  const { data } = await api.put('/users/me', { displayName })
  return data
}
