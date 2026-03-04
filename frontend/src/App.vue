<script setup>
import { RouterView } from 'vue-router'
import Header from '@/components/organisms/Header.vue'
</script>

<template>
  <Header />
  <main class="pt-14">
    <RouterView />
  </main>
</template>
