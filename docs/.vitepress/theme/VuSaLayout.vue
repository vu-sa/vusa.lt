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

const { Layout } = DefaultTheme
const { frontmatter, lang } = useData()

const lastUpdated = ref<string | null>(null)
const latestVersion = ref<string | null>(null)

onMounted(async () => {
  try {
    const res = await fetch('/docs/changelog-meta.json')
    if (res.ok) {
      const meta = await res.json()
      lastUpdated.value = meta.lastUpdated
      latestVersion.value = meta.latestVersion
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

    <!-- Last update banner on home page -->
    <template #home-features-after>
      <div v-if="latestVersion" class="vusa-home-section">
        <div class="last-update-banner">
          <span class="update-icon">🔄</span>
          <span v-if="lang === 'lt'">
            Versija <strong>{{ latestVersion }}</strong> · {{ lastUpdated }} —
            <a href="/docs/changelog/">Peržiūrėti visus atnaujinimus →</a>
          </span>
          <span v-else>
            Version <strong>{{ latestVersion }}</strong> · {{ lastUpdated }} —
            <a href="/docs/en/changelog/">View all updates →</a>
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
  background: linear-gradient(135deg, oklch(0.5 0.12 25) 0%, oklch(0.75 0.12 65) 100%);
  border-radius: 4px;
  flex-shrink: 0;
}

/* Aside enhancement */
.aside-last-updated {
  padding: 8px 12px;
  margin-bottom: 16px;
  background: var(--vp-c-bg-soft);
  border-radius: 6px;
  font-size: 12px;
  color: var(--vp-c-text-2);
  border-left: 3px solid oklch(0.5 0.12 25);
}
</style>
