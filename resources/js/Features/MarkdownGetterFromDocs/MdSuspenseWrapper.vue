<template>
  <Suspense>
    <MdGetter v-bind="$attrs" @content-loaded="$emit('content-loaded', $event)" />
  </Suspense>
</template>

<script setup lang="ts">
import MdGetter from "./MdGetter.vue";

defineEmits<{
  'content-loaded': [boolean]; 
}>();
</script>
