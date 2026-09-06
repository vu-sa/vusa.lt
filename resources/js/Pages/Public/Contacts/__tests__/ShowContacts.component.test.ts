import { mount } from '@vue/test-utils';
import { describe, expect, it, vi, beforeEach } from 'vitest';
import { ref, computed } from 'vue';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

const mockResults = ref<Array<Record<string, unknown>>>([]);
const mockSearch = vi.fn();
const mockInitializeSearchClient = vi.fn();

vi.mock('@/Composables/useInstitutionSearch', () => ({
  useInstitutionSearch: () => ({
    results: mockResults,
    isSearching: ref(false),
    isLoadingMore: ref(false),
    hasResults: computed(() => mockResults.value.length > 0),
    hasMoreResults: ref(false),
    totalHits: computed(() => mockResults.value.length),
    facets: ref([]),
    filters: ref({ query: '', tenants: [], types: [] }),
    search: mockSearch,
    initializeSearchClient: mockInitializeSearchClient,
    toggleTenant: vi.fn(),
    toggleType: vi.fn(),
    setFilter: vi.fn(),
    clearFilters: vi.fn(),
  }),
}));

import ShowContacts from '../ShowContacts.vue';

const stubs = {
  NewInstitutionCard: {
    props: ['institution', 'showMetadata'],
    template: '<div class="mock-institution-card" data-slot="institution-card">{{ institution.name }}</div>',
  },
  StudentRepInstitutionCard: {
    props: ['institution'],
    template: '<div class="mock-student-rep-card" data-slot="student-rep-institution-card">{{ institution.name }}</div>',
  },
  PageTitleBand: {
    props: ['title', 'eyebrow', 'lead'],
    template: '<div class="mock-title-band"><h1>{{ title }}</h1><p>{{ lead }}</p><slot name="breadcrumbs" /><slot name="actions" /></div>',
  },
  PublicBreadcrumbs: {
    template: '<nav class="mock-breadcrumbs" />',
  },
  PublicFilterPopover: {
    props: ['label'],
    template: '<div class="mock-filter-popover">{{ label }}</div>',
  },
  SmartLink: {
    props: ['href'],
    template: '<a :href="href"><slot /></a>',
  },
};

describe('ShowContacts', () => {
  beforeEach(() => {
    mockResults.value = [];
    mockSearch.mockClear();
  });

  it('renders standard NewInstitutionCard for normal institutions', () => {
    mockResults.value = [
      {
        id: 'inst-standard',
        name: 'VU SA Centrinis biuras',
        type_slugs: ['padaliniai'],
        is_student_representation: false,
      },
    ];

    const wrapper = mount(ShowContacts, {
      props: {
        studentRepTypeSlugs: ['studentu-atstovu-organas'],
      },
      global: { stubs },
    });

    expect(wrapper.find('[data-slot="institution-card"]').exists()).toBe(true);
    expect(wrapper.find('[data-slot="student-rep-institution-card"]').exists()).toBe(false);
    expect(wrapper.text()).toContain('VU SA Centrinis biuras');
  });

  it('renders StudentRepInstitutionCard when institution has is_student_representation flag', () => {
    mockResults.value = [
      {
        id: 'inst-rep-1',
        name: 'VU SA MIF Taryba',
        type_slugs: ['fakulteto-taryba'],
        is_student_representation: true,
      },
    ];

    const wrapper = mount(ShowContacts, {
      props: {
        studentRepTypeSlugs: ['studentu-atstovu-organas'],
      },
      global: { stubs },
    });

    expect(wrapper.find('[data-slot="student-rep-institution-card"]').exists()).toBe(true);
    expect(wrapper.find('[data-slot="institution-card"]').exists()).toBe(false);
    expect(wrapper.text()).toContain('VU SA MIF Taryba');
  });

  it('renders StudentRepInstitutionCard when institution type slug matches studentRepTypeSlugs', () => {
    mockResults.value = [
      {
        id: 'inst-rep-2',
        name: 'Studijų programos komitetas',
        type_slugs: ['spk-organas'],
        is_student_representation: false,
      },
    ];

    const wrapper = mount(ShowContacts, {
      props: {
        studentRepTypeSlugs: ['studentu-atstovu-organas', 'spk-organas'],
      },
      global: { stubs },
    });

    expect(wrapper.find('[data-slot="student-rep-institution-card"]').exists()).toBe(true);
    expect(wrapper.find('[data-slot="institution-card"]').exists()).toBe(false);
    expect(wrapper.text()).toContain('Studijų programos komitetas');
  });
});
