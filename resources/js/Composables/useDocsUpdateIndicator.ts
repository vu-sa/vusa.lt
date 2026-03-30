import { ref, onMounted } from 'vue'

const STORAGE_KEY = 'docs-changelog-last-seen'

/**
 * Tracks whether there are unseen documentation/platform updates.
 * 
 * Fetches `/docs/changelog-meta.json` (generated at docs build time)
 * and compares `lastUpdated` with a localStorage timestamp to show
 * an indicator badge next to the documentation link in the admin sidebar.
 */
export function useDocsUpdateIndicator() {
  const hasNewUpdates = ref(false)
  const lastUpdateDate = ref<string | null>(null)
  const latestVersion = ref<string | null>(null)

  onMounted(async () => {
    try {
      const response = await fetch('/docs/changelog-meta.json')

      if (!response.ok) return

      const meta = await response.json()
      lastUpdateDate.value = meta.lastUpdated
      latestVersion.value = meta.latestVersion

      const lastSeen = localStorage.getItem(STORAGE_KEY)
      if (!lastSeen || lastSeen < (meta.latestVersion || meta.lastUpdated)) {
        hasNewUpdates.value = true
      }
    } catch {
      // Silently fail — indicator just won't show
    }
  })

  function markAsSeen() {
    const key = latestVersion.value || lastUpdateDate.value
    if (key) {
      localStorage.setItem(STORAGE_KEY, key)
      hasNewUpdates.value = false
    }
  }

  return {
    hasNewUpdates,
    lastUpdateDate,
    latestVersion,
    markAsSeen,
  }
}
