import { ref } from 'vue'

const toasts = ref([])
let nextId = 0
const timeoutMap = new Map()

export function useToast() {
  function showToast(message, type = 'info') {
    const id = nextId++
    toasts.value.push({ id, message, type })
    const timeoutId = setTimeout(() => {
      toasts.value = toasts.value.filter(toast => toast.id !== id)
      timeoutMap.delete(id)
    }, 4000)
    timeoutMap.set(id, timeoutId)
  }

  function removeToast(id) {
    const timeoutId = timeoutMap.get(id)
    if (timeoutId !== undefined) {
      clearTimeout(timeoutId)
      timeoutMap.delete(id)
    }
    toasts.value = toasts.value.filter(toast => toast.id !== id)
  }

  return { toasts, showToast, removeToast }
}
