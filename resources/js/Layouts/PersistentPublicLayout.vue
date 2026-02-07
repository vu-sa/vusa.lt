<template>
  <!-- Simplified layout without Naive UI wrapper for better performance -->
  <PublicLayout>
    <slot />
  </PublicLayout>
</template>

<script setup lang="tsx">
import PublicLayout from "@/Components/Public/Layouts/PublicLayout.vue";
</script>
