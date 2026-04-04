<script setup>
// Atom: AppSelect is the reusable labeled select control for choosing one value from a list.
defineProps({
  label: { type: String, default: '' },
  options: { type: Array, required: true },
})

const model = defineModel()
</script>

<template>
  <div>
    <label v-if="label" class="block text-sm text-body mb-1">{{ label }}</label>
    <select
      v-model="model"
      class="w-full bg-surface-alt border border-line-strong rounded-lg px-4 py-2 text-heading focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent"
    >
      <option v-for="opt in options" :key="opt.value" :value="opt.value">
        {{ opt.label }}
      </option>
    </select>
  </div>
</template>
