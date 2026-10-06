import { beforeEach, describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { router } from '@inertiajs/vue3';

import SecretariesSection from '../SecretariesSection.vue';
import type { SecretaryRoster, SecretaryUser } from '../secretaryTypes';

import { commonStubs } from '@/tests/stubs';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

function makeUser(overrides: Partial<SecretaryUser> = {}): SecretaryUser {
  return {
    id: 'user-1',
    name: 'Jonas Jonaitis',
    email: 'jonas@vusa.lt',
    profile_photo_path: null,
    ...overrides,
  };
}

function makeRoster(overrides: Partial<SecretaryRoster> = {}): SecretaryRoster {
  return {
    cadence_id: 'cadence-1',
    label: '2025–2026',
    start_date: '2025-07-01',
    end_date: '2026-06-30',
    is_global: false,
    is_current: true,
    is_past: false,
    secretaries: [],
    ...overrides,
  };
}

function mountSection(rosters: SecretaryRoster[]) {
  return mount(SecretariesSection, {
    props: { institutionId: 'inst-1', rosters },
    global: { stubs: commonStubs },
  });
}

const editableIds = (wrapper: ReturnType<typeof mount>) =>
  wrapper.findAll('[data-slot="secretary-roster"]').map(row => row.attributes('data-cadence-id'));

describe('SecretariesSection', () => {
  beforeEach(() => {
    vi.mocked(router.put).mockClear();
  });

  it('offers only the current term and the next one for staffing', () => {
    // Newest first, as the server sends them.
    const wrapper = mountSection([
      makeRoster({ cadence_id: 'later', label: '2027–2028', is_current: false }),
      makeRoster({ cadence_id: 'next', label: '2026–2027', is_current: false }),
      makeRoster({ cadence_id: 'current' }),
      makeRoster({ cadence_id: 'past', label: '2024–2025', is_current: false, is_past: true }),
    ]);

    expect(editableIds(wrapper)).toEqual(['current', 'next']);
  });

  it('lists past terms only as read-only history, and only when someone held them', () => {
    const wrapper = mountSection([
      makeRoster(),
      makeRoster({ cadence_id: 'staffed', label: '2024–2025', is_current: false, is_past: true, secretaries: [makeUser({ id: 'user-9', name: 'Ona Onaitė' })] }),
      makeRoster({ cadence_id: 'empty', label: '2023–2024', is_current: false, is_past: true }),
    ]);

    const history = wrapper.get('[data-slot="previous-secretaries"]');
    expect(history.text()).toContain('Ona Onaitė');
    expect(history.text()).not.toContain('2023–2024');
    expect(history.find('[data-slot="remove-secretary"]').exists()).toBe(false);
  });

  it('removes a nominee without touching the rest of the roster', async () => {
    const wrapper = mountSection([makeRoster({
      secretaries: [makeUser(), makeUser({ id: 'user-2', name: 'Rūta Petraitė' })],
    })]);

    await wrapper.get('[data-slot="remove-secretary"][data-user-id="user-1"]').trigger('click');

    expect(router.put).toHaveBeenCalledWith(
      expect.anything(),
      { cadence_id: 'cadence-1', user_ids: ['user-2'] },
      expect.anything(),
    );
  });

  it('explains that a term with secretaries stops assigning to the membership', () => {
    expect(mountSection([makeRoster()]).text()).toContain('secretaries.institution.effect_warning');
  });

  it('says so when there is no current or upcoming term to staff', () => {
    const wrapper = mountSection([makeRoster({ is_current: false, is_past: true })]);

    expect(wrapper.text()).toContain('secretaries.institution.no_cadences_hint');
    expect(editableIds(wrapper)).toEqual([]);
  });
});
