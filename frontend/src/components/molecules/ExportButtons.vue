<script setup>
import AppButton from '@/components/atoms/AppButton.vue'

defineProps({
  exporting: { type: Boolean, default: false },
})

defineEmits(['export'])
</script>

<template>
  <div class="flex flex-col sm:flex-row justify-end gap-3 sm:gap-4">
    <AppButton variant="secondary" :disabled="exporting" @click="$emit('export', 'json')">
      Download JSON
    </AppButton>
    <AppButton variant="secondary" :disabled="exporting" @click="$emit('export', 'csv')">
      Download CSV
    </AppButton>
  </div>
</template>
