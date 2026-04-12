<script setup>
// Template: AuthTemplate centers authentication pages inside a simple branded card layout.
import ShieldMark from '@/components/atoms/ShieldMark.vue'
</script>

<template>
  <main class="min-h-screen bg-page flex items-center justify-center px-4 md:px-6 lg:px-8">
    <div class="w-full max-w-md bg-surface border border-line rounded-xl p-6">
      <div class="flex items-center justify-center gap-3 mb-6">
        <ShieldMark size="md" />
        <h1 class="text-2xl font-bold text-heading text-center">Visual Shield</h1>
      </div>
      <slot />
    </div>
  </main>
</template>
