<script setup lang="ts">
/**
 * VU SA Custom VitePress Layout
 * 
 * Extends the default VitePress layout with:
 * - Custom navigation logo mark
 * - Enhanced styling
 * - Home page last-updated section
 */
import { useData } from 'vitepress'
import DefaultTheme from 'vitepress/theme'
import { ref, onMounted } from 'vue'
import TestEvidence from './TestEvidence.vue'

const { Layout } = DefaultTheme
const { frontmatter, lang } = useData()

const lastUpdated = ref<string | null>(null)
const latestVersion = ref<string | null>(null)
const latestChangelog = ref('v2')

onMounted(async () => {
  try {
    const res = await fetch('/docs/changelog-meta.json')
    if (res.ok) {
      const meta = await res.json()
      lastUpdated.value = meta.lastUpdated
      latestVersion.value = meta.latestVersion
      latestChangelog.value = meta.latestChangelog ?? latestChangelog.value
    }
  } catch {
    // silently ignore
  }
})
</script>

<template>
  <Layout>
    <!-- Custom nav bar title slot for branding -->
    <template #nav-bar-title-before>
      <div class="vusa-logo-mark" aria-hidden="true"></div>
    </template>
    
    <!-- Custom aside top for additional context -->
    <template #aside-top>
      <div v-if="frontmatter.lastUpdated" class="aside-last-updated">
        Dokumentacija reguliariai atnaujinama
      </div>
    </template>

    <template #doc-footer-before>
      <TestEvidence />
    </template>

    <!-- Last update banner on home page -->
    <template #home-features-after>
      <div v-if="latestVersion" class="vusa-home-section">
        <div class="last-update-banner">
          <span class="update-icon">🔄</span>
          <span v-if="lang === 'lt'">
            Versija <strong>{{ latestVersion }}</strong> · {{ lastUpdated }} —
            <a :href="`/docs/changelog/${latestChangelog}`">Peržiūrėti visus atnaujinimus →</a>
          </span>
          <span v-else>
            Version <strong>{{ latestVersion }}</strong> · {{ lastUpdated }} —
            <a :href="`/docs/en/changelog/${latestChangelog}`">View all updates →</a>
          </span>
        </div>
      </div>
    </template>
  </Layout>
</template>

<style scoped>
/* VU SA Logo mark for navigation */
.vusa-logo-mark {
  width: 24px;
  height: 24px;
  margin-right: 8px;
  background: linear-gradient(135deg, var(--vusa-red) 0%, var(--vusa-red) 55%, var(--vusa-yellow) 55%);
  flex-shrink: 0;
}

/* Aside enhancement */
.aside-last-updated {
  padding: 8px 12px;
  margin-bottom: 16px;
  background: var(--vusa-callout-bg);
  font-size: 12px;
  color: var(--vp-c-text-2);
  border-left: 3px solid var(--vusa-yellow);
}
</style>
