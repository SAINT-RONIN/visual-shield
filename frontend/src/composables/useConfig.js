import { ref } from 'vue'

const config = ref(null)
const loaded = ref(false)

function setConfig(data) {
  config.value = data
  loaded.value = true
}

export function useConfig() {
  return { config, loaded, setConfig }
}
