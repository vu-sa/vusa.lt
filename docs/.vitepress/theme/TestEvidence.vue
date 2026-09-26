<template>
  <aside v-if="tests.length > 0" class="test-evidence">
    <p class="test-evidence__title">
      Įrodyta testais
      <span v-if="reviewed" class="test-evidence__reviewed">· peržiūrėta {{ reviewed }}</span>
    </p>
    <template v-for="group in groups" :key="group.label">
      <p v-if="group.tests.length > 0" class="test-evidence__group">{{ group.label }}</p>
      <ul v-if="group.tests.length > 0">
        <li v-for="test in group.tests" :key="test">
          <a :href="`${repository}/blob/main/${test}`" target="_blank" rel="noopener">{{ test }}</a>
        </li>
      </ul>
    </template>
  </aside>
</template>

<script setup lang="ts">
import { useData } from 'vitepress'
import { computed } from 'vue'

// Mirrors the `tests:` / `last_reviewed` frontmatter that `docs:coverage` reads; the PDF build renders the same block.
const repository = 'https://github.com/vu-sa/vusa.lt'

const { frontmatter } = useData()

const tests = computed<string[]>(() => {
  const value = frontmatter.value.tests

  return Array.isArray(value) ? value.filter((item): item is string => typeof item === 'string') : []
})

// Pest tests prove what the server enforces; Vitest specs what the screen shows and allows.
const groups = computed(() => [
  { label: 'Serveris – taisyklės ir teisės', tests: tests.value.filter(test => test.startsWith('tests/')) },
  { label: 'Sąsaja – ką rodo ir leidžia ekranas', tests: tests.value.filter(test => test.startsWith('resources/js/')) },
])

const reviewed = computed(() => {
  const value = frontmatter.value.last_reviewed

  return value ? String(value instanceof Date ? value.toISOString() : value).slice(0, 10) : null
})
</script>

<style scoped>
.test-evidence {
  margin-top: 48px;
  padding: 12px 16px;
  border-left: 3px solid var(--vusa-yellow);
  background: var(--vusa-callout-bg);
  font-size: 13px;
  line-height: 1.6;
}

.test-evidence__title {
  margin: 0 0 4px;
  font-weight: 600;
}

.test-evidence__reviewed {
  font-weight: 400;
  color: var(--vp-c-text-2);
}

.test-evidence__group {
  margin: 8px 0 2px;
  color: var(--vp-c-text-2);
}

.test-evidence ul {
  margin: 0;
  padding-left: 16px;
}

.test-evidence a {
  color: var(--vusa-brand);
  font-family: var(--vp-font-family-mono);
  font-size: 12px;
}
</style>
