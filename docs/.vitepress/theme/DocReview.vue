<script setup lang="ts">
import { useData } from 'vitepress'
import { computed } from 'vue'

const { frontmatter } = useData()
const labels: Record<string, string> = { draft: 'Rašoma', partial: 'Dalinis', reviewed: 'Peržiūrėta' }
const status = computed(() => labels[frontmatter.value.doc_status])
const reviewed = computed(() => {
  const value = frontmatter.value.last_reviewed
  return value instanceof Date ? value.toISOString().slice(0, 10) : value ? String(value).slice(0, 10) : null
})
</script>

<template>
  <div v-if="status" class="doc-review" aria-label="Puslapio parengtis">
    <span>{{ status }}</span>
    <span v-if="reviewed">Turinys peržiūrėtas <time :datetime="reviewed">{{ reviewed }}</time></span>
    <span v-else>Turinio peržiūra dar nepažymėta</span>
  </div>
</template>

<style scoped>
.doc-review {
  display: flex;
  flex-wrap: wrap;
  gap: 8px 16px;
  margin-bottom: 20px;
  padding-bottom: 12px;
  border-bottom: 1px solid var(--vp-c-divider);
  color: var(--vp-c-text-2);
  font-size: 13px;
}
.doc-review > span:first-child { font-weight: 600; color: var(--vp-c-text-1); }
</style>
