<script setup lang="ts">
import { useData } from 'vitepress'
import DefaultTheme from 'vitepress/theme'
import { ref, onMounted } from 'vue'
import TestEvidence from './TestEvidence.vue'
import DocReview from './DocReview.vue'
import DocReviewDate from './DocReviewDate.vue'

const { Layout } = DefaultTheme
const { lang } = useData()

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
    <template #nav-bar-title-before>
      <div class="vusa-logo-mark" aria-hidden="true"></div>
    </template>
    
    <template #doc-before><DocReview /></template>

    <template #doc-footer-before>
      <TestEvidence />
      <DocReviewDate />
    </template>

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
.vusa-logo-mark {
  width: 24px;
  height: 24px;
  margin-right: 8px;
  background: linear-gradient(135deg, var(--vusa-red) 0%, var(--vusa-red) 55%, var(--vusa-yellow) 55%);
  flex-shrink: 0;
}

</style>
