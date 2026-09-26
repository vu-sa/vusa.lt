import { computed, ref, onMounted } from 'vue';

import { useDocsHref } from '@/Composables/useDocsHref';

const STORAGE_KEY = 'docs-changelog-last-seen';

/** Fallback until `changelog-meta.json` names the newest per-major changelog page. */
const DEFAULT_CHANGELOG = 'v2';

/**
 * Tracks whether there are unseen documentation/platform updates.
 *
 * Fetches `/docs/changelog-meta.json` (generated at docs build time)
 * and compares `lastUpdated` with a localStorage timestamp to show
 * an indicator badge next to the documentation link in the admin sidebar.
 */
export function useDocsUpdateIndicator() {
  const hasNewUpdates = ref(false);
  const lastUpdateDate = ref<string | null>(null);
  const latestVersion = ref<string | null>(null);
  const latestChangelog = ref(DEFAULT_CHANGELOG);

  const docsBase = useDocsHref();
  const changelogHref = computed(() => `${docsBase.value}/changelog/${latestChangelog.value}`);

  onMounted(async () => {
    try {
      const response = await fetch('/docs/changelog-meta.json');

      if (!response.ok) return;

      const meta = await response.json();
      lastUpdateDate.value = meta.lastUpdated;
      latestVersion.value = meta.latestVersion;
      latestChangelog.value = meta.latestChangelog ?? DEFAULT_CHANGELOG;

      const lastSeen = localStorage.getItem(STORAGE_KEY);
      if (!lastSeen || lastSeen < meta.lastUpdated) {
        hasNewUpdates.value = true;
      }
    }
    catch {
      // Silently fail — indicator just won't show
    }
  });

  function markAsSeen() {
    if (lastUpdateDate.value) {
      localStorage.setItem(STORAGE_KEY, lastUpdateDate.value);
      hasNewUpdates.value = false;
    }
  }

  return {
    hasNewUpdates,
    lastUpdateDate,
    latestVersion,
    docsBase,
    changelogHref,
    markAsSeen,
  };
}
