<script setup>
// Molecule: SiteFooter renders the global site footer with a Privacy Policy link.
import { RouterLink } from 'vue-router'
</script>

<template>
  <footer class="bg-surface border-t border-line px-4 md:px-6 lg:px-8 py-4 text-center text-xs text-muted">
    <p>
      Visual Shield does not provide medical advice. The analysis is automated and based on
      technical thresholds for accessibility awareness only.
      &mdash;
      <RouterLink
        :to="{ name: 'privacy' }"
        class="text-link hover:text-link-hover underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary rounded"
      >Privacy Policy</RouterLink>
    </p>
  </footer>
</template>
