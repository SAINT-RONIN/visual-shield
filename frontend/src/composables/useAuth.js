import { ref, computed } from 'vue'
import { getProfile } from '@/api/users.js'
import { setAuthToken } from '@/utils/api.js'

const TOKEN_STORAGE_KEY = 'auth_token'
const USER_STORAGE_KEY = 'auth_user'

function readStoredUser() {
  try {
    const rawUser = localStorage.getItem(USER_STORAGE_KEY)
    return rawUser ? JSON.parse(rawUser) : null
  } catch {
    localStorage.removeItem(USER_STORAGE_KEY)
    return null
  }
}

const user = ref(readStoredUser())
const token = ref(localStorage.getItem(TOKEN_STORAGE_KEY))
let hydrationPromise = null

if (token.value) {
  setAuthToken(token.value)
}

const isLoggedIn = computed(() => !!token.value)
const isAdmin = computed(() => user.value?.role === 'admin')

function persistUser(newUser) {
  if (newUser) {
    localStorage.setItem(USER_STORAGE_KEY, JSON.stringify(newUser))
  } else {
    localStorage.removeItem(USER_STORAGE_KEY)
  }
}

function setAuth(newToken, newUser) {
  token.value = newToken
  user.value = newUser
  localStorage.setItem(TOKEN_STORAGE_KEY, newToken)
  persistUser(newUser)
  setAuthToken(newToken)
}

function clearAuth() {
  token.value = null
  user.value = null
  localStorage.removeItem(TOKEN_STORAGE_KEY)
  localStorage.removeItem(USER_STORAGE_KEY)
  setAuthToken(null)
}

function setUser(newUser) {
  user.value = newUser
  persistUser(newUser)
}

async function hydrateAuthUser(force = false) {
  if (!token.value) {
    return null
  }

  if (user.value && !force) {
    return user.value
  }

  if (!hydrationPromise) {
    hydrationPromise = getProfile()
      .then((profile) => {
        setUser(profile)
        return profile
      })
      .catch((error) => {
        clearAuth()
        throw error
      })
      .finally(() => {
        hydrationPromise = null
      })
  }

  return hydrationPromise
}

export function useAuth() {
  return { user, token, isLoggedIn, isAdmin, setAuth, clearAuth, setUser, hydrateAuthUser }
}
