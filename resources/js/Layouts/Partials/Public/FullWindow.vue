<template>
  <div class="min-h-screen antialiased bg-neutral-50 pb-8">
    <!-- style="
      background-image: url('/images/elements/Background.svg');
      background-size: cover;
    " -->
    <slot></slot>
  </div>
  <Footer />
</template>

<script setup>
import Footer from "@/Layouts/Partials/Public/Footer.vue";
</script>
