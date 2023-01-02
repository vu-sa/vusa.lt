<template>
  <span>
    <strong>{{ data.objectName }}</strong> paliko komentarą.
  </span>
</template>

<script setup lang="ts">
defineProps<{
  data: Record<string, any>;
}>();
</script>
