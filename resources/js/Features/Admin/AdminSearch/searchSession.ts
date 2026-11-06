import { customRef, ref, watch, type Ref } from 'vue';
import { usePage } from '@inertiajs/vue3';

import type { AdminSearchConfig } from '@/Shared/Search/types';

// Deadlines must be checked on access even when no reactive dependency changed.
const readAtAccess = (read: () => boolean): Readonly<Ref<boolean>> => customRef(track => ({
  get: () => {
    track();
    return read();
  },
  set: () => {},
}));

function createSearchSession() {
  const config = ref<AdminSearchConfig | null>(null);
  const isConfigLoading = ref(false);
  const configError = ref<string | null>(null);
  const rateLimitedUntil = ref(0);
  const generation = ref(0);
  let userId: string | number | null | undefined;
  let pending: Promise<void> | null = null;

  const hasExpired = readAtAccess(() => !!config.value?.expiresAt && Date.now() / 1000 > config.value.expiresAt - 60);
  const isRateLimited = readAtAccess(() => Date.now() < rateLimitedUntil.value);

  function reset() {
    config.value = null;
    configError.value = null;
    isConfigLoading.value = false;
    rateLimitedUntil.value = 0;
    pending = null;
    generation.value++;
  }

  function identify(id: typeof userId) {
    if (id === userId) return;
    userId = id;
    reset();
  }

  function assertCurrent(requestGeneration: number) {
    if (requestGeneration !== generation.value) throw new DOMException('Search session changed', 'AbortError');
  }

  async function request(refresh: boolean, csrfToken = ''): Promise<void> {
    if (pending) return pending;
    if (!refresh && config.value && !hasExpired.value) return;
    if (hasExpired.value) reset();
    if (isRateLimited.value) {
      configError.value = 'Too many requests. Please wait a moment.';
      return;
    }

    const requestGeneration = generation.value;
    config.value = null;
    isConfigLoading.value = true;
    configError.value = null;
    pending = (async () => {
      try {
        const response = await fetch(route(refresh ? 'api.v1.admin.search.refreshKey' : 'api.v1.admin.search.config'), {
          ...(refresh ? { method: 'POST' } : {}),
          credentials: 'same-origin',
          headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            ...(refresh ? { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken } : {}),
          },
        });
        assertCurrent(requestGeneration);
        if (!response.ok) {
          if (response.status === 429) {
            const retryAfter = parseInt(response.headers.get('retry-after') || '5', 10);
            rateLimitedUntil.value = Date.now() + retryAfter * 1000;
            throw new Error('Too many requests. Please wait a moment.');
          }
          if (response.status === 401) throw new Error('Unauthorized - please log in');
          if (response.status === 503) throw new Error('Typesense search is not configured');
          throw new Error(`Failed to ${refresh ? 'refresh search key' : 'load search configuration'} (HTTP ${response.status})`);
        }
        const data = await response.json();
        assertCurrent(requestGeneration);
        if (refresh && (!data.success || !data.config)) throw new Error('Failed to refresh search key');
        config.value = refresh ? data.config : data;
      }
      catch (error: unknown) {
        if (requestGeneration !== generation.value) throw error;
        configError.value = error instanceof Error ? error.message : 'Failed to load search configuration';
      }
      finally {
        if (requestGeneration === generation.value) {
          isConfigLoading.value = false;
          pending = null;
        }
      }
    })();
    return pending;
  }

  return { config, isConfigLoading, configError, rateLimitedUntil, generation, hasExpired, isRateLimited, identify, reset, assertCurrent, request };
}

const browserSession = createSearchSession();

export function clearAdminSearchSession() {
  browserSession.reset();
}

export function useAdminSearchSession() {
  const page = usePage();
  const session = typeof window === 'undefined' ? createSearchSession() : browserSession;
  watch(() => page.props.auth?.user?.id, id => session.identify(id), { immediate: true, flush: 'sync' });
  return {
    ...session,
    fetchConfig: () => session.request(false),
    refreshConfig: () => session.request(true, (page.props.csrf_token as string | undefined) ?? ''),
  };
}
