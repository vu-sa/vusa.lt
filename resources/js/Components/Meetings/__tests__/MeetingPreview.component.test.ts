import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import { ref } from 'vue';

import MeetingPreview from '../MeetingPreview.vue';

import { commonStubs } from '@/tests/stubs';

vi.mock('@/Composables/usePreviewApi', () => ({
  usePreviewApi: () => ({
    data: ref({
      representatives: [],
      agenda_items: [
        { id: 'a1', title: 'Biudžetas', decision: 'positive', type: 'voting' },
        { id: 'a2', title: 'Ataskaita', decision: null, type: 'informational' },
      ],
    }),
    isLoading: ref(false),
  }),
}));

const mountPreview = (meeting: Record<string, unknown> = {}) =>
  mount(MeetingPreview, {
    props: { meeting: { id: 'm1', title: 'Senato posėdis', institution_name_lt: 'VU Senatas', completion_status: 'incomplete', ...meeting } as never },
    global: { stubs: { ...commonStubs } },
  });

describe('MeetingPreview', () => {
  it('carries a meeting\'s completion on its mark, named the way the collection row does (U10)', () => {
    const mark = mountPreview().get('[data-slot="status-mark"]');

    expect(mark.text()).toBe('Neužpildyta');
    expect(mark.attributes('data-status-role')).toBe('attention');
  });

  it('falls back to the plain mark for a state it has no name for', () => {
    expect(mountPreview({ completion_status: 'partial' }).find('[data-slot="status-mark"]').exists()).toBe(false);
  });

  it('opens the meeting\'s record page', () => {
    expect(mountPreview().find('h2 a').attributes('href')).toMatch(/^\/mocked-route\/meetings\.show/);
  });

  it('shows what became of each agenda item, by vote or by its type', () => {
    const wrapper = mountPreview();

    expect(wrapper.findAll('[data-slot="agenda-decision"]').map(item => item.text())).toEqual(['Už', 'Aptarimas']);
  });
});
