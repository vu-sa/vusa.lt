import { describe, test, expect, vi } from 'vitest';
import { usePage } from '@inertiajs/vue3';

import { createMockPage } from '@/tests/helpers/createMockPage';
import { useDocsHref } from '@/Composables/useDocsHref';

describe('useDocsHref', () => {
  test('sends English users to the Lithuanian guide page, which has no translation', () => {
    vi.mocked(usePage).mockReturnValue(createMockPage({ app: { locale: 'en' } }));

    expect(useDocsHref('/rezervacijos/rezervacijos#busenos').value).toBe('/docs/rezervacijos/rezervacijos#busenos');
  });

  test('keeps the bare base localized for the bilingual changelog', () => {
    vi.mocked(usePage).mockReturnValue(createMockPage({ app: { locale: 'en' } }));
    expect(useDocsHref().value).toBe('/docs/en');

    vi.mocked(usePage).mockReturnValue(createMockPage({ app: { locale: 'lt' } }));
    expect(useDocsHref().value).toBe('/docs');
  });
});
