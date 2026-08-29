import { beforeEach, describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { router } from '@inertiajs/vue3';

import { commonStubs } from '@/tests/stubs';

import AdministratorsSection from '../AdministratorsSection.vue';
import type { AdministratorRoster, AdministratorUser } from '../administratorTypes';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

function makeUser(overrides: Partial<AdministratorUser> = {}): AdministratorUser {
  return {
    id: 'user-1',
    name: 'Jonas Jonaitis',
    email: 'jonas@vusa.lt',
    profile_photo_path: null,
    ...overrides,
  };
}

function makeRoster(overrides: Partial<AdministratorRoster> = {}): AdministratorRoster {
  return {
    cadence_id: 'cadence-1',
    label: '2025–2026',
    start_date: '2025-07-01',
    end_date: '2026-06-30',
    is_global: false,
    is_current: true,
    administrators: [],
    ...overrides,
  };
}

function mountSection(rosters: AdministratorRoster[], suggested: AdministratorUser[] = []) {
  return mount(AdministratorsSection, {
    props: { institutionId: 'inst-1', rosters, suggested },
    global: { stubs: commonStubs },
  });
}

describe('AdministratorsSection', () => {
  beforeEach(() => {
    vi.mocked(router.put).mockClear();
  });

  it('renders a row per term', () => {
    const wrapper = mountSection([
      makeRoster(),
      makeRoster({ cadence_id: 'cadence-2', label: '2024–2025', is_current: false }),
    ]);

    expect(wrapper.text()).toContain('2025–2026');
    expect(wrapper.text()).toContain('2024–2025');
  });

  it('lists the people already nominated for a term', () => {
    const wrapper = mountSection([makeRoster({ administrators: [makeUser()] })]);

    expect(wrapper.text()).toContain('Jonas Jonaitis');
  });

  it('offers current members as suggestions, minus the ones already nominated', () => {
    const wrapper = mountSection(
      [makeRoster({ administrators: [makeUser()] })],
      [makeUser(), makeUser({ id: 'user-2', name: 'Rūta Petraitė' })],
    );

    // Jonas is already on the roster, so only Rūta is left to suggest.
    const suggestions = wrapper.findAll('button').filter(button => button.text().includes('Rūta Petraitė'));
    expect(suggestions).toHaveLength(1);
    expect(wrapper.findAll('button').filter(b => b.text() === 'Jonas Jonaitis')).toHaveLength(0);
  });

  it('adds a suggested member to the term roster', async () => {
    const wrapper = mountSection(
      [makeRoster({ administrators: [makeUser()] })],
      [makeUser({ id: 'user-2', name: 'Rūta Petraitė' })],
    );

    const suggestion = wrapper.findAll('button').find(button => button.text().includes('Rūta Petraitė'));
    await suggestion!.trigger('click');

    expect(router.put).toHaveBeenCalledWith(
      expect.anything(),
      { cadence_id: 'cadence-1', user_ids: ['user-1', 'user-2'] },
      expect.objectContaining({ preserveScroll: true }),
    );
  });

  it('removes a nominee without touching the rest of the roster', async () => {
    const wrapper = mountSection([makeRoster({
      administrators: [makeUser(), makeUser({ id: 'user-2', name: 'Rūta Petraitė' })],
    })]);

    await wrapper.get('[data-slot="remove-administrator"][data-user-id="user-1"]').trigger('click');

    expect(router.put).toHaveBeenCalledWith(
      expect.anything(),
      { cadence_id: 'cadence-1', user_ids: ['user-2'] },
      expect.anything(),
    );
  });

  it('explains that a term with administrators stops assigning to the membership', () => {
    // The consequence is the whole point of the feature, so it must be on screen.
    expect(mountSection([makeRoster()]).text()).toContain('administrators.institution.effect_warning');
  });

  it('says so when the institution has no terms to nominate against', () => {
    expect(mountSection([]).text()).toContain('administrators.institution.no_cadences');
  });
});
