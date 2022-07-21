<template>
  <Link
    class="border-2 h-40 rounded-xl flex items-center relative hover:bg-stone-100 duration-200 justify-center p-2"
  >
    <button class="flex flex-col items-center">
      <slot></slot>
    </button>
  </Link>
</template>

<script setup lang="ts">
import { Link } from "@inertiajs/inertia-vue3";
</script>
