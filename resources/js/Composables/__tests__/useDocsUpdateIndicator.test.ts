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
      json: async () => ({ lastUpdated: '2027-01-02', latestChangelog: 'v3' }),
    });

    const { changelogHref, lastUpdateDate } = await mountIndicator('en');

    expect(changelogHref.value).toBe('/gidas/en/changelog/v3');
    expect(lastUpdateDate.value).toBe('2027-01-02');
  });

  test.each(['lt', 'en'])('falls back to the v3 changelog when the meta is unavailable (%s)', async (locale) => {
    mockFetch.mockResolvedValue({ ok: false });

    const { changelogHref } = await mountIndicator(locale);

    expect(changelogHref.value).toBe(`/gidas/${locale === 'en' ? 'en/' : ''}changelog/v3`);
  });

  test('uses the v3 fallback when older metadata omits the changelog page', async () => {
    mockFetch.mockResolvedValue({
      ok: true,
      json: async () => ({ lastUpdated: '2026-10-02' }),
    });

    const { changelogHref } = await mountIndicator('lt');

    expect(changelogHref.value).toBe('/gidas/changelog/v3');
  });
});
