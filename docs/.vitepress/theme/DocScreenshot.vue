<template>
  <figure v-if="!missing" :class="['doc-screenshot', { 'doc-screenshot--narrow': narrow }]">
    <img
      :src="withBase(`/screenshots/${locale}/${name}.png`)"
      :alt
      loading="lazy"
      @error="missing = true"
    >
    <figcaption v-if="caption || href">
      <span v-if="caption">{{ caption }}</span>
      <a v-if="href" :href>{{ locale === 'en' ? 'Open' : 'Atidaryti' }} {{ href }} →</a>
    </figcaption>
  </figure>
</template>

<script setup lang="ts">
import { withBase, useData } from 'vitepress'
import { computed, ref } from 'vue'

// Frames come from the browser tests (tests/Browser/README.md "Docs screenshots") and are never
// committed, so a missing one hides rather than showing a broken image.
defineProps<{
  name: string
  alt: string
  caption?: string
  /** App path the frame shows, e.g. `/mano` — served from the same host as the docs. */
  href?: string
  /** For element shots (dialogs, panels) that would otherwise stretch to the full column. */
  narrow?: boolean
}>()

const { localeIndex } = useData()
const locale = computed(() => (localeIndex.value === 'en' ? 'en' : 'lt'))
const missing = ref(false)
</script>

<style scoped>
.doc-screenshot {
  margin: 20px 0;
}

.doc-screenshot img {
  border: 1px solid var(--vp-c-divider);
}

.doc-screenshot--narrow {
  max-width: 420px;
}

.doc-screenshot figcaption {
  display: flex;
  flex-wrap: wrap;
  justify-content: space-between;
  gap: 4px 16px;
  margin-top: 8px;
  font-size: 13px;
  line-height: 1.5;
  color: var(--vp-c-text-2);
}

.doc-screenshot figcaption a {
  font-weight: 500;
  white-space: nowrap;
}
</style>
