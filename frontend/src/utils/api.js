import axios from 'axios'

export const apiBaseUrl = import.meta.env.VITE_API_BASE_URL

const api = axios.create({
    baseURL: apiBaseUrl
})

let authToken = null

export function setAuthToken(token) {
    authToken = token
}

export function getAuthToken() {
    return authToken
}

api.interceptors.request.use((config) => {
    if (authToken) {
        config.headers.Authorization = `Bearer ${authToken}`
    }
    return config
})

export default api
