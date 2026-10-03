<template>
  <aside class="changelog-note">
    <p class="changelog-note__label">
      <span class="changelog-note__version">Atnaujinta {{ version }}</span>
      <span class="changelog-note__date">{{ date }}</span>
    </p>
    <p class="changelog-note__title">{{ title }}</p>
    <div class="changelog-note__body">
      <slot />
    </div>
    <a class="changelog-note__link" :href="withBase(`/changelog/${major}#${anchor}`)">Visi {{ version }} pakeitimai →</a>
  </aside>
</template>

<script setup lang="ts">
import { withBase } from 'vitepress'
import { computed } from 'vue'

// A dated pointer from a guide section to the release that changed it; the PDF build renders the same note.
const props = defineProps<{
  /** e.g. `v2.21`, matching the changelog heading's `{#v2-21}` anchor. */
  version: string
  date: string
  title: string
}>()

const major = computed(() => props.version.split('.')[0])
const anchor = computed(() => props.version.replace('.', '-'))
</script>

<style scoped>
.changelog-note {
  margin: 20px 0;
  padding: 14px 18px;
  border: 1px dashed var(--vusa-yellow-dark);
  background: var(--vp-c-bg);
}

.changelog-note__label {
  display: flex;
  gap: 10px;
  align-items: baseline;
  margin: 0;
  font-size: 12px;
}

.changelog-note__version {
  padding: 1px 8px;
  background: var(--vusa-yellow);
  color: #1a1a1a;
  font-weight: 600;
  letter-spacing: 0.02em;
}

.changelog-note__date {
  color: var(--vp-c-text-2);
}

.changelog-note__title {
  margin: 8px 0 4px;
  font-weight: 600;
}

.changelog-note__body :deep(p) {
  margin: 4px 0;
  font-size: 14px;
}

.changelog-note__link {
  font-size: 13px;
  font-weight: 500;
}
</style>
