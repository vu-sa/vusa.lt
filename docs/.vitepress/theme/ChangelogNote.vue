<template>
  <aside class="changelog-note">
    <p class="changelog-note__label">
      <span class="changelog-note__version">Atnaujinta {{ date }}</span>
    </p>
    <p class="changelog-note__title">{{ title }}</p>
    <div class="changelog-note__body">
      <slot />
    </div>
    <a v-if="major" class="changelog-note__link" :href="withBase(`/changelog/${major}`)">Visi {{ major }} pakeitimai →</a>
  </aside>
</template>

<script setup lang="ts">
import { useData, withBase } from 'vitepress'
import { computed } from 'vue'

import { majorForDate, type ChangelogMajor } from '../changelogEntries.ts'

// A dated pointer from a guide section to the change that shaped it; the PDF build renders the same note.
const props = defineProps<{
  date: string
  title: string
}>()

const { theme } = useData<{ changelogMajors: ChangelogMajor[] }>()
const major = computed(() => majorForDate(theme.value.changelogMajors, props.date))
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
