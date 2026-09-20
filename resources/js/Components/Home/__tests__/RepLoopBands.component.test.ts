import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import AccessChangeBand from '../AccessChangeBand.vue';
import CoordinatorCard from '../CoordinatorCard.vue';
import FirstLoginChecklist from '../FirstLoginChecklist.vue';
import type { HomeAccessChange, HomeChecklist } from '../types';

import { useApiMutation } from '@/Composables/useApi';
import { globalProgress } from '@/Composables/useTutorialProgress';

vi.mock('@/Composables/useApi', () => ({
  useApiMutation: vi.fn(() => ({ execute: vi.fn().mockResolvedValue(undefined) })),
}));

const checklist = (done: Record<string, boolean> = {}): HomeChecklist => {
  const items = (['photo', 'follow', 'notifications', 'meeting'] as const).map(key => ({
    key,
    done: done[key] ?? false,
    href: key === 'meeting' ? null : `/mocked-route/${key}`,
  }));

  return { items, doneCount: items.filter(item => item.done).length };
};

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

describe('FirstLoginChecklist', () => {
  it('lists the four steps with how far along the rep is', () => {
    const wrapper = mount(FirstLoginChecklist, { props: { checklist: checklist({ photo: true }) } });

    expect(wrapper.findAll('li')).toHaveLength(4);
    expect(wrapper.get('[data-testid="checklist-progress"]').text()).toBe('onboarding.progress');
    expect(wrapper.get('[data-item="photo"]').attributes('data-done')).toBe('true');
    expect(wrapper.get('[data-item="follow"]').attributes('data-done')).toBe('false');
  });

  it('offers an action only for what is still open, and says the rest is done', () => {
    const wrapper = mount(FirstLoginChecklist, { props: { checklist: checklist({ photo: true }) } });

    expect(wrapper.get('[data-item="photo"]').text()).toContain('onboarding.done');
    expect(wrapper.get('[data-item="photo"]').find('a, button').exists()).toBe(false);
    expect(wrapper.get('[data-item="follow"]').find('a').attributes('href')).toBe('/mocked-route/follow');
  });

  it('opens the ActionWindow for the first meeting instead of navigating', async () => {
    const wrapper = mount(FirstLoginChecklist, { props: { checklist: checklist() } });

    const action = wrapper.get('[data-item="meeting"]').find('button');
    expect(wrapper.get('[data-item="meeting"]').find('a').exists()).toBe(false);

    await action.trigger('click');

    expect(wrapper.emitted('record-meeting')).toHaveLength(1);
  });

  it('is remembered as dismissed on the server, so it does not come back', async () => {
    const wrapper = mount(FirstLoginChecklist, { props: { checklist: checklist() } });

    await wrapper.get('[data-testid="checklist-dismiss"]').trigger('click');

    expect(wrapper.find('[data-slot="first-login-checklist"]').exists()).toBe(false);
    expect(useApiMutation).toHaveBeenCalledWith(
      expect.stringContaining('tutorials.complete'),
      'POST',
      { tour_id: 'spotlight-checklist-first-login-v1' },
      expect.anything(),
    );
  });

  it('renders nothing for someone who dismissed it earlier', () => {
    globalProgress.value = { 'spotlight-checklist-first-login-v1': '2026-09-01T00:00:00Z' };

    expect(mount(FirstLoginChecklist, { props: { checklist: checklist() } }).find('[data-slot="first-login-checklist"]').exists()).toBe(false);
  });
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
    const wrapper = mount(CoordinatorCard, { props: { coordinator, compact: true } });

    expect(wrapper.text()).toContain('Jonas Jonaitis');
    expect(wrapper.find('a[href="mailto:jonas@vusa.lt"]').exists()).toBe(true);
  });

  it('uses a quieter heading than the full card', () => {
    const compact = mount(CoordinatorCard, { props: { coordinator, compact: true } });
    const full = mount(CoordinatorCard, { props: { coordinator } });

    expect(compact.get('h2').classes()).toContain('text-sm');
    expect(full.get('h2').classes()).toContain('text-base');
  });
});
