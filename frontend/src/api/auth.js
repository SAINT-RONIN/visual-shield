import api from '@/utils/api'

export async function login(username, password) {
  const { data } = await api.post('/auth/login', { username, password })
  return data
}

export async function register(username, password, displayName) {
  const { data } = await api.post('/auth/register', { username, password, displayName })
  return data
}

export async function logout() {
  await api.post('/auth/logout')
}
