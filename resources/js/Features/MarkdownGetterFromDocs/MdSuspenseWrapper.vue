<template>
  <Suspense>
    <MdGetter v-bind="$attrs" />
  </Suspense>
</template>

<script setup lang="ts">
import MdGetter from "./MdGetter.vue";
</script>
