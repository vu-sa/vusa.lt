import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import { ref } from 'vue';

import MeetingPreview from '../MeetingPreview.vue';

import { commonStubs } from '@/tests/stubs';

vi.mock('@/Composables/usePreviewApi', () => ({
  usePreviewApi: () => ({
    data: ref({
      representatives: [],
      agenda_items: [{ id: 'a1', title: 'Biudžetas', decision: 'positive', student_benefit: null }],
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
  it('names a meeting\'s completion the way the collection row does (U10)', () => {
    const badge = mountPreview().get('[data-slot="status-badge"]');

    expect(badge.text()).toBe('Neužpildyta');
    expect(badge.attributes('data-status-role')).toBe('attention');
  });

  it('draws no completion badge for a state it has no name for', () => {
    const badges = mountPreview({ completion_status: 'partial' }).findAll('[data-slot="status-badge"]');

    expect(badges.map(badge => badge.text())).not.toContain('partial');
  });

  it('opens the meeting\'s record page', () => {
    expect(mountPreview().find('h2 a').attributes('href')).toMatch(/^\/mocked-route\/meetings\.show/);
  });

  it('shows an agenda item\'s decision as a vote status, with its word', () => {
    const wrapper = mountPreview();

    const decision = wrapper.findAll('[data-slot="status-badge"]').find(badge => badge.attributes('data-status-role') === 'success');
    expect(decision?.text()).toBe('Už');
  });
});
