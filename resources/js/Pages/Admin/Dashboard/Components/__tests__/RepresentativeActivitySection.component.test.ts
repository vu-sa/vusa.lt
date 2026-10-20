import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';

import RepresentativeActivitySection from '../RepresentativeActivitySection.vue';
import type { RepresentativeUser } from '../../types';

const user = (id: string, category: RepresentativeUser['category'], last_action: string | null): RepresentativeUser => ({
  id, name: id, email: `${id}@example.test`, category, last_action, profile_photo_path: null, duties: [],
});

const render = () => mount(RepresentativeActivitySection, {
  props: {
    tenantIds: ['tenant'],
    stats: { total: 288, activeToday: 1, activeLast7Days: 55, activeLast30Days: 112, neverLoggedIn: 65 },
    users: [
      user('Never active', 'never', null),
      user('Older activity', 'month', '2026-10-01'),
      user('Recent activity', 'week', '2026-10-18'),
      user('Most recent', 'today', '2026-10-20'),
      user('Third recent', 'week', '2026-10-17'),
      user('Inactive', 'stale', '2026-08-01'),
    ],
  },
  global: { stubs: { RepresentativeDataTable: true, UserAvatar: true } },
});

describe('RepresentativeActivitySection', () => {
  it('shows four counters and the three most recently active representatives', () => {
    const wrapper = render();
    expect(wrapper.findAll('[data-number]')).toHaveLength(4);
    expect(wrapper.get('[data-number=month] dd').text()).toBe('112 / 288');
    expect(wrapper.get('[data-number=today] dd').text()).toBe('1');
    expect(wrapper.get('[data-number=week] dd').text()).toBe('55');
    expect(wrapper.get('[data-number=never] dd').text()).toBe('65');
    expect(wrapper.findAll('li').map(row => row.text())).toEqual([
      expect.stringContaining('Most recent'), expect.stringContaining('Recent activity'), expect.stringContaining('Third recent'),
    ]);
  });

  it('opens the full list on active representatives', async () => {
    const wrapper = render();
    await wrapper.get('button').trigger('click');
    const table = wrapper.getComponent({ name: 'RepresentativeDataTable' });
    expect(table.props('isOpen')).toBe(true);
    expect(table.props('initialTab')).toBe('active');
  });
});
