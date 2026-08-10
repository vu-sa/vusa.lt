import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';

import DuplicateUserWarning from '@/Components/AdminForms/DuplicateUserWarning.vue';
import type { DuplicateUserMatch } from '@/Components/AdminForms/DuplicateUserWarning.vue';
import { commonStubs } from '@/tests/stubs';

const makeMatch = (overrides: Partial<DuplicateUserMatch> = {}): DuplicateUserMatch => ({
  id: 'user-1',
  name: 'Jonas Jonaitis',
  reason: 'name',
  tenants: ['VU SA MIF'],
  duties_count: 2,
  email_masked: 'j***@stud.vu.lt',
  can_manage: false,
  ...overrides,
});

const mountWarning = (props: Record<string, unknown>) =>
  mount(DuplicateUserWarning, {
    props: { matches: [], ...props },
    global: { stubs: { ...commonStubs } },
  });

describe('DuplicateUserWarning.vue', () => {
  it('renders nothing when there are no matches', () => {
    const wrapper = mountWarning({ matches: [] });

    expect(wrapper.text()).toBe('');
  });

  it('shows the name, unit and masked email of a match', () => {
    const wrapper = mountWarning({ matches: [makeMatch()] });

    expect(wrapper.text()).toContain('Jonas Jonaitis');
    expect(wrapper.text()).toContain('VU SA MIF');
    expect(wrapper.text()).toContain('j***@stud.vu.lt');
  });

  it('labels a match with no units rather than leaving the slot blank', () => {
    // tests/setup.ts stubs laravel-vue-i18n to echo the key back, so translated
    // strings are asserted by key here rather than by their Lithuanian text.
    const wrapper = mountWarning({ matches: [makeMatch({ tenants: [] })] });

    expect(wrapper.text()).toContain('users.no_tenant');
  });

  it('offers an Open link only when the admin can manage the match', () => {
    const unmanageable = mountWarning({ matches: [makeMatch({ can_manage: false })] });
    expect(unmanageable.find('a').exists()).toBe(false);

    const manageable = mountWarning({ matches: [makeMatch({ can_manage: true })] });
    expect(manageable.find('a').exists()).toBe(true);
  });

  it('emits use with the match when the wizard offers that action', async () => {
    // Only the wizard can switch to an existing person; the create form links out
    // instead, so the action is opt-in via showUseAction.
    const match = makeMatch();
    const wrapper = mountWarning({ matches: [match], showUseAction: true });

    await wrapper.find('button').trigger('click');

    expect(wrapper.emitted('use')?.[0]).toEqual([match]);
  });

  it('flags an exact email collision distinctly from a name similarity', () => {
    const withCollision = mountWarning({ matches: [makeMatch({ reason: 'email' })] });
    expect(withCollision.text()).toContain('users.duplicate_reason_email');

    const nameOnly = mountWarning({ matches: [makeMatch({ reason: 'name' })] });
    expect(nameOnly.text()).not.toContain('users.duplicate_reason_email');
  });
});
