import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import { ref } from 'vue';

import InstitutionPreview from '../InstitutionPreview.vue';

import { commonStubs } from '@/tests/stubs';

const related = Array.from({ length: 12 }, (_, i) => ({ id: `i${i}`, name: `Taryba ${i}`, kind_label: 'Pavaldi' }));

vi.mock('@/Composables/usePreviewApi', () => ({
  usePreviewApi: () => ({
    data: ref({ types: [{ id: 't1', title: 'Taryba' }], last_meetings: [], representatives: [], related_institutions: related }),
    isLoading: ref(false),
  }),
}));

describe('InstitutionPreview', () => {
  it('lists only the first relations and leads to the record for the rest', () => {
    const wrapper = mount(InstitutionPreview, {
      props: { institution: { id: 'inst', name_lt: 'VU SA MIF', type_titles: ['Senas tipas'] } },
      global: { stubs: commonStubs },
    });

    expect(wrapper.findAll('[data-slot="preview-chip-list"] > li:not([data-slot]) a')).toHaveLength(6);
    expect(wrapper.find('[data-slot="preview-chip-more"]').text()).toBe('+6');
    expect(wrapper.text()).toContain('Taryba');
    expect(wrapper.text()).not.toContain('Senas tipas');
  });
});
