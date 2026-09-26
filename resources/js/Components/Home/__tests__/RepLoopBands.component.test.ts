import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import AccessChangeBand from '../AccessChangeBand.vue';
import CoordinatorCard from '../CoordinatorCard.vue';
import type { HomeAccessChange } from '../types';

import UserAvatar from '@/Components/Avatars/UserAvatar.vue';
import { useApiMutation } from '@/Composables/useApi';
import { globalProgress } from '@/Composables/useTutorialProgress';

vi.mock('@/Composables/useApi', () => ({
  useApiMutation: vi.fn(() => ({ execute: vi.fn().mockResolvedValue(undefined) })),
}));

const change = (overrides: Partial<HomeAccessChange> = {}): HomeAccessChange => ({
  kind: 'started',
  dutyName: 'Studentų atstovas',
  institutionName: 'VU MIF',
  date: '2026-09-20',
  effectiveOn: '2026-09-20',
  isExOfficio: false,
  ...overrides,
});

beforeEach(() => {
  vi.clearAllMocks();
  globalProgress.value = {};
});

describe('AccessChangeBand', () => {
  it('says what changed when it was one duty', () => {
    const wrapper = mount(AccessChangeBand, { props: { changes: [change()] } });

    expect(wrapper.get('[data-testid="access-change-text"]').text()).toBe('access.band.started');
  });

  it('says the ending in its own words', () => {
    const wrapper = mount(AccessChangeBand, { props: { changes: [change({ kind: 'ended' })] } });

    expect(wrapper.get('[data-testid="access-change-text"]').text()).toBe('access.band.ended');
  });

  it('collapses several changes into one line rather than a list', () => {
    const wrapper = mount(AccessChangeBand, { props: { changes: [change(), change({ dutyName: 'Kita' })] } });

    expect(wrapper.get('[data-testid="access-change-text"]').text()).toBe('access.band.many');
  });

  it('renders nothing when nothing changed', () => {
    expect(mount(AccessChangeBand, { props: { changes: [] } }).find('[data-slot="access-change-band"]').exists()).toBe(false);
  });

  it('leads to the dated history on Mano rolės', () => {
    const wrapper = mount(AccessChangeBand, { props: { changes: [change()] } });

    expect(wrapper.get('a').attributes('href')).toBe('/mocked-route/profile.roles#history');
  });

  it('is dismissed per change, so a later change brings it back', async () => {
    const wrapper = mount(AccessChangeBand, { props: { changes: [change()] } });

    await wrapper.get('[data-testid="access-change-dismiss"]').trigger('click');

    expect(wrapper.find('[data-slot="access-change-band"]').exists()).toBe(false);
    expect(useApiMutation).toHaveBeenCalledWith(
      expect.stringContaining('tutorials.complete'),
      'POST',
      { tour_id: 'spotlight-access-change-band-2026-09-20' },
      expect.anything(),
    );

    const sameChange = mount(AccessChangeBand, { props: { changes: [change()] } });
    const laterChange = mount(AccessChangeBand, { props: { changes: [change({ effectiveOn: '2026-09-27' })] } });

    expect(sameChange.find('[data-slot="access-change-band"]').exists()).toBe(false);
    expect(laterChange.find('[data-slot="access-change-band"]').exists()).toBe(true);
  });
});

describe('CoordinatorCard compact', () => {
  const coordinator = { name: 'Jonas Jonaitis', email: 'jonas@vusa.lt', profile_photo_path: null, duty: 'Koordinatorius' };

  it('still names the person and offers to write to them', () => {
    const wrapper = mount(CoordinatorCard, { props: { coordinators: [coordinator], compact: true } });

    expect(wrapper.text()).toContain('Jonas Jonaitis');
    expect(wrapper.find('a[href="mailto:jonas@vusa.lt"]').exists()).toBe(true);
  });

  it('uses a smaller avatar in compact mode while keeping the home heading', () => {
    const compact = mount(CoordinatorCard, { props: { coordinators: [coordinator], compact: true } });
    const full = mount(CoordinatorCard, { props: { coordinators: [coordinator] } });

    expect(compact.get('h2').classes()).toContain('text-sm');
    expect(full.get('h2').classes()).toContain('text-sm');
    expect(compact.findComponent(UserAvatar).props('size')).toBe(32);
    expect(full.findComponent(UserAvatar).props('size')).toBe(40);
  });
});
