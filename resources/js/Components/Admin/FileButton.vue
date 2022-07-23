<template>
  <button
    class="relative flex h-40 items-center justify-center rounded-xl border-2 p-2 duration-200 hover:bg-stone-100"
  >
    <button class="flex flex-col items-center">
      <slot />
    </button>
  </button>
</template>

<script setup lang="ts"></script>
