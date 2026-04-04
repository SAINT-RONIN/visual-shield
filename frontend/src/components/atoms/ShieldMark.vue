<script setup>
// Atom: ShieldMark renders the reusable shield icon used for Visual Shield branding.
const props = defineProps({
  size: { type: String, default: 'md' },
})

const sizeClasses = {
  sm: 'w-6 h-6',
  md: 'w-7 h-7',
  lg: 'w-9 h-9',
}
</script>

<template>
  <svg
    :class="sizeClasses[props.size] || sizeClasses.md"
    viewBox="0 0 48 48"
    fill="none"
    aria-hidden="true"
  >
    <path
      d="M24 4 10.5 8.8v13.4c0 9.2 5.4 17.4 13.5 20.8 8.1-3.4 13.5-11.6 13.5-20.8V8.8L24 4Z"
      fill="#4338ca"
    />
    <path
      d="M24 8.2 14 11.8v10.1c0 6.9 3.8 13.2 10 16 6.2-2.8 10-9.1 10-16V11.8L24 8.2Z"
      fill="#6366f1"
    />
    <path
      d="M17.6 15.8h12.8"
      stroke="rgba(255,255,255,0.48)"
      stroke-width="2.2"
      stroke-linecap="round"
    />
    <path
      d="M18.2 24.6 22.1 28.5 29.8 19.8"
      stroke="#ffffff"
      stroke-width="3.4"
      stroke-linecap="round"
      stroke-linejoin="round"
    />
  </svg>
</template>
