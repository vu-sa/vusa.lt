import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import { computed, ref } from 'vue';

import InstitutionMeetingsCollection from '../InstitutionMeetingsCollection.vue';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

const sourceOptions = vi.hoisted(() => ({ value: undefined as Record<string, unknown> | undefined }));

vi.mock('@/Composables/useCollectionSource', () => ({
  useTypesenseCollectionSource: (options: Record<string, unknown>) => {
    sourceOptions.value = options;
    return {
      items: computed(() => []),
      filters: ref({}),
      query: ref(''),
      facets: computed(() => []),
      chips: computed(() => []),
      activeFilterCount: computed(() => 0),
      toggleFilter: vi.fn(),
      refresh: vi.fn(),
    };
  },
}));

vi.mock('@/Composables/useRowAbilities', () => ({
  useRowAbilities: () => ({ can: () => false, invalidate: vi.fn() }),
}));

describe('InstitutionMeetingsCollection', () => {
  /** The scoped key decides what the reader may see; the institution filter may only narrow it. */
  it('searches the meetings index narrowed to this institution, without touching the page URL', () => {
    mount(InstitutionMeetingsCollection, {
      props: { institutionId: '01abc' },
      global: { stubs: { EmbeddedCollection: true, CollectionConfirmAction: true } },
    });

    const options = sourceOptions.value!;
    expect(options.collection).toBe('meetings');
    expect(options.syncToUrl).toBe(false);
    expect((options.baseFilterBy as () => string)()).toBe('institution_ids:=[`01abc`]');
    expect(options.facetFields).not.toContain('tenant_shortnames');
    expect(options.facetFields).not.toContain('institution_type_title');
  });
});
