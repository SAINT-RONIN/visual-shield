<template>
  <div class="fixed bottom-4 right-4 z-50 flex flex-col gap-2 w-80">
    <TransitionGroup
      enter-active-class="transition duration-300 ease-out"
      enter-from-class="translate-x-full opacity-0"
      enter-to-class="translate-x-0 opacity-100"
      leave-active-class="transition duration-200 ease-in"
      leave-from-class="translate-x-0 opacity-100"
      leave-to-class="translate-x-full opacity-0"
    >
      <Toast
        v-for="toast in toasts"
        :key="toast.id"
        :id="toast.id"
        :message="toast.message"
        :type="toast.type"
        @dismiss="removeToast"
      />
    </TransitionGroup>
  </div>
</template>

<script setup>
import { useToast } from '@/composables/useToast.js'
import Toast from '@/components/atoms/Toast.vue'

const { toasts, removeToast } = useToast()
</script>
