<template>
  <!-- Server-side rendered HTML from PHP TipTap editor -->
  <div v-if="element.html" v-html="element.html"></div>
  <div v-else class="text-zinc-400 italic text-sm">
    {{ $t('Turinio nepavyko atvaizduoti') }}
  </div>
</template>

<script setup lang="ts">
defineProps<{
  element: models.ContentPart;
}>();
</script>

