import { customRef, ref, watch, type Ref } from 'vue';
import { usePage } from '@inertiajs/vue3';

import { apiRequest } from '@/Composables/apiRequest';
import type { AdminSearchConfig } from '@/Search/core/types';

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

  async function request(refresh: boolean): Promise<void> {
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
        const result = refresh
          ? await apiRequest<{ success: boolean; config?: AdminSearchConfig }>(route('api.v1.admin.search.refreshKey'), { method: 'POST', raw: true })
          : await apiRequest<AdminSearchConfig>(route('api.v1.admin.search.config'));
        assertCurrent(requestGeneration);
        if (result.status === 0) throw new TypeError(result.message ?? 'Network error');
        if (result.status < 200 || result.status >= 300) {
          if (result.status === 429) {
            const retryAfter = parseInt(result.headers?.get('retry-after') || '5', 10);
            rateLimitedUntil.value = Date.now() + retryAfter * 1000;
            throw new Error('Too many requests. Please wait a moment.');
          }
          if (result.status === 401) throw new Error('Unauthorized - please log in');
          if (result.status === 503) throw new Error('Typesense search is not configured');
          throw new Error(`Failed to ${refresh ? 'refresh search key' : 'load search configuration'} (HTTP ${result.status})`);
        }
        if (refresh) {
          const data = result.data as { success: boolean; config?: AdminSearchConfig } | null;
          if (!data?.success || !data.config) throw new Error('Failed to refresh search key');
          config.value = data.config;
        }
        else {
          if (result.data === null) throw new SyntaxError('Unexpected end of JSON input');
          config.value = result.data as AdminSearchConfig;
        }
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
    refreshConfig: () => session.request(true),
  };
}
