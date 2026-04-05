import axios from 'axios'

export const apiBaseUrl = import.meta.env.VITE_API_BASE_URL

const api = axios.create({
    baseURL: apiBaseUrl
})

let authToken = null
let unauthorizedHandler = null

export function setAuthToken(token) {
    authToken = token
}

export function getAuthToken() {
    return authToken
}

export function setUnauthorizedHandler(handler) {
    unauthorizedHandler = handler
}

api.interceptors.request.use((config) => {
    if (authToken) {
        config.headers.Authorization = `Bearer ${authToken}`
    }
    return config
})

api.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response?.status === 401 && unauthorizedHandler) {
            unauthorizedHandler()
        }
        return Promise.reject(error)
    }
)

export default api
