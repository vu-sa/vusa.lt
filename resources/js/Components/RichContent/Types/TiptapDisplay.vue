<template>
  <!-- Single root: with two sibling v-if/v-else-if roots, Vue can't auto-inherit the
       width/spacing class RichContentParser binds via :class (fallthrough attrs have
       no target on a multi-root/fragment component), so it would silently never apply. -->
  <div>
    <!-- Server-side rendered HTML from PHP TipTap editor -->
    <div v-if="element.html" class="rc-prose" v-html="element.html" />
    <div v-else-if="element.html === null" class="text-zinc-400 italic text-sm">
      {{ $t('Turinio nepavyko atvaizduoti') }}
    </div>
  </div>
</template>

<script setup lang="ts">
defineProps<{
  element: models.ContentPart;
}>();
</script>
