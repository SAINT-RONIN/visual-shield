import { ref, computed } from 'vue'
import { setAuthToken } from '@/utils/api.js'

const user = ref(null)
const token = ref(localStorage.getItem('auth_token'))

if (token.value) {
  setAuthToken(token.value)
}

const isLoggedIn = computed(() => !!token.value)
const isAdmin = computed(() => user.value?.role === 'admin')

function setAuth(newToken, newUser) {
  token.value = newToken
  user.value = newUser
  localStorage.setItem('auth_token', newToken)
  setAuthToken(newToken)
}

function clearAuth() {
  token.value = null
  user.value = null
  localStorage.removeItem('auth_token')
  setAuthToken(null)
}

function setUser(newUser) {
  user.value = newUser
}

export function useAuth() {
  return { user, token, isLoggedIn, isAdmin, setAuth, clearAuth, setUser }
}
