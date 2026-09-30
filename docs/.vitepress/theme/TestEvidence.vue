<template>
  <details v-if="tests.length > 0" class="test-evidence">
    <summary>Testų nuorodos ({{ tests.length }})</summary>
    <p>Šie testai tikrina atskiras elgsenos dalis. Jie nepakeičia turinio peržiūros.</p>
    <template v-for="group in groups" :key="group.label">
      <p v-if="group.tests.length > 0" class="test-evidence__group">{{ group.label }}</p>
      <ul v-if="group.tests.length > 0">
        <li v-for="test in group.tests" :key="test">
          <a :href="`${repository}/blob/main/${test}`" target="_blank" rel="noopener">{{ test }}</a>
        </li>
      </ul>
    </template>
  </details>
</template>

<script setup lang="ts">
import { useData } from 'vitepress'
import { computed } from 'vue'

const repository = 'https://github.com/vu-sa/vusa.lt'

const { frontmatter } = useData()

const tests = computed<string[]>(() => {
  const value = frontmatter.value.tests

  return Array.isArray(value) ? value.filter((item): item is string => typeof item === 'string') : []
})

const isBrowserTest = (test: string) => test.startsWith('tests/Browser/')

const groups = computed(() => [
  { label: 'Serveris – taisyklės ir teisės', tests: tests.value.filter(test => test.startsWith('tests/') && !isBrowserTest(test)) },
  { label: 'Sąsaja – ką rodo ir leidžia ekranas', tests: tests.value.filter(test => test.startsWith('resources/js/')) },
  { label: 'Naršyklė – kaip veikia tikrame ekrane', tests: tests.value.filter(isBrowserTest) },
])

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

.test-evidence summary {
  margin: 0 0 4px;
  font-weight: 600;
  cursor: pointer;
  min-height: 44px;
  align-content: center;
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
  overflow-wrap: anywhere;
  color: var(--vusa-brand);
  font-family: var(--vp-font-family-mono);
  font-size: 12px;
}
</style>
