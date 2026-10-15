<script setup lang="ts">
import { useData } from 'vitepress'
import { computed } from 'vue'

const { frontmatter } = useData()
const reviewed = computed(() => {
  const value = frontmatter.value.last_reviewed
  return typeof value === 'string' ? value.slice(0, 10) : null
})
</script>

<template>
  <p v-if="reviewed" class="doc-review-date">
    Turinys peržiūrėtas <time :datetime="reviewed">{{ reviewed }}</time>
  </p>
</template>

<style scoped>
.doc-review-date {
  margin-top: 24px;
  color: var(--vp-c-text-2);
  font-size: 13px;
}
</style>
