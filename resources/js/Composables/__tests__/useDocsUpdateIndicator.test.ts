import { describe, test, expect, vi, beforeEach, afterEach } from 'vitest';
import { defineComponent } from 'vue';
import { flushPromises, mount } from '@vue/test-utils';
import { usePage } from '@inertiajs/vue3';

import { createMockPage } from '@/tests/helpers/createMockPage';
import { useDocsUpdateIndicator } from '@/Composables/useDocsUpdateIndicator';

const mockFetch = vi.fn();
vi.stubGlobal('fetch', mockFetch);

async function mountIndicator(locale: string) {
  vi.mocked(usePage).mockReturnValue(createMockPage({ app: { locale } }));

  let indicator!: ReturnType<typeof useDocsUpdateIndicator>;
  mount(defineComponent({
    setup() {
      indicator = useDocsUpdateIndicator();
      return () => null;
    },
  }));
  await flushPromises();

  return indicator;
}

describe('useDocsUpdateIndicator', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    localStorage.clear();
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  test('links to the per-major changelog named by the build meta', async () => {
    mockFetch.mockResolvedValue({
      ok: true,
      json: async () => ({ latestVersion: 'v3.1', lastUpdated: '2027-01-02', latestChangelog: 'v3' }),
    });

    const { changelogHref, latestVersion } = await mountIndicator('en');

    expect(changelogHref.value).toBe('/docs/en/changelog/v3');
    expect(latestVersion.value).toBe('v3.1');
  });

  test('falls back to the v2 changelog when the meta is unavailable', async () => {
    mockFetch.mockResolvedValue({ ok: false });

    const { changelogHref } = await mountIndicator('lt');

    expect(changelogHref.value).toBe('/docs/changelog/v2');
  });
});
