import { ref } from 'vue'
import api from '@/utils/api.js'

const config = ref(null)
const loaded = ref(false)

async function loadConfig() {
  if (loaded.value) return
  try {
    const { data } = await api.get('/config')
    config.value = data.data
    loaded.value = true
  } catch (e) {
    console.error('Failed to load config', e)
  }
}

export function useConfig() {
  return { config, loaded, loadConfig }
}
