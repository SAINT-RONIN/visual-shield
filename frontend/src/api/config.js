import api from '@/utils/api'

export async function fetchConfig() {
  const { data } = await api.get('/config')
  return data
}
