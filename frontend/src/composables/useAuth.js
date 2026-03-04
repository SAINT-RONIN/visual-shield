import { ref, computed } from 'vue'
import api, { setAuthToken } from '@/utils/api.js'

const user = ref(null)
const token = ref(localStorage.getItem('auth_token'))

if (token.value) {
  setAuthToken(token.value)
}

const isLoggedIn = computed(() => !!token.value)

async function login(username, password) {
  const { data } = await api.post('/login', { username, password })
  token.value = data.token
  user.value = data.user
  localStorage.setItem('auth_token', data.token)
  setAuthToken(data.token)
  return data
}

async function register(username, password, displayName) {
  const { data } = await api.post('/register', { username, password, displayName })
  return data
}

async function fetchProfile() {
  const { data } = await api.get('/users/me')
  user.value = data
  return data
}

async function updateProfile(displayName) {
  const { data } = await api.put('/users/me', { displayName })
  user.value = data
  return data
}

async function logout() {
  try {
    await api.post('/logout')
  } catch {
    // Ignore logout errors
  }
  token.value = null
  user.value = null
  localStorage.removeItem('auth_token')
  setAuthToken(null)
}

export function useAuth() {
  return { user, token, isLoggedIn, login, register, fetchProfile, updateProfile, logout }
}
