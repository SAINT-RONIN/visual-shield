<script setup>
// Atom: AppInput is the reusable labeled text input used by forms across the interface.
defineProps({
  label: { type: String, default: '' },
  type: { type: String, default: 'text' },
  placeholder: { type: String, default: '' },
  required: { type: Boolean, default: false },
})

const model = defineModel()
</script>

<template>
  <div>
    <label v-if="label" class="block text-sm text-body mb-1">{{ label }}</label>
    <input
      v-model="model"
      :type="type"
      :placeholder="placeholder"
      :required="required"
      class="w-full bg-surface-alt border border-line-strong rounded-lg px-4 py-2 text-heading placeholder-muted focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
    />
  </div>
</template>
