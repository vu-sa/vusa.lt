import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';

import DuplicateDutyWarning from '@/Components/AdminForms/DuplicateDutyWarning.vue';
import type { DutyMatch, DutySimilarityMatches } from '@/Components/AdminForms/DuplicateDutyWarning.vue';
import { commonStubs } from '@/tests/stubs';

const makeMatch = (overrides: Partial<DutyMatch> = {}): DutyMatch => ({
  id: 'duty-1',
  name: 'Komunikacijos koordinatorius',
  reason: 'same_institution_exact',
  institution_name: 'VU SA MIF',
  tenant_shortname: 'VU SA MIF',
  current_holder_names: ['Jonas Jonaitis'],
  places_to_occupy: 1,
  can_manage: false,
  ...overrides,
});

const EMPTY: DutySimilarityMatches = { same_institution: [], other_institution: [], other_institution_count: 0 };

const mountWarning = (matches: DutySimilarityMatches, currentDutyId: string | null = null) =>
  mount(DuplicateDutyWarning, {
    props: { matches, currentDutyId },
    global: { stubs: { ...commonStubs } },
  });

describe('DuplicateDutyWarning.vue', () => {
  it('renders nothing when there are no matches at all', () => {
    const wrapper = mountWarning(EMPTY);

    expect(wrapper.text()).toBe('');
  });

  it('shows the name and current holder of a same-institution match', () => {
    const wrapper = mountWarning({ ...EMPTY, same_institution: [makeMatch()] });

    expect(wrapper.text()).toContain('Komunikacijos koordinatorius');
    expect(wrapper.text()).toContain('Jonas Jonaitis');
  });

  it('labels a vacant duty rather than leaving the holder slot blank', () => {
    const wrapper = mountWarning({ ...EMPTY, same_institution: [makeMatch({ current_holder_names: [] })] });

    expect(wrapper.text()).toContain('forms.duty_duplicate.no_holder');
  });

  it('flags an exact name match distinctly from a gendered variant', () => {
    const exact = mountWarning({ ...EMPTY, same_institution: [makeMatch({ reason: 'same_institution_exact' })] });
    expect(exact.text()).toContain('forms.duty_duplicate.reason_exact');

    const variant = mountWarning({ ...EMPTY, same_institution: [makeMatch({ reason: 'same_institution_variant' })] });
    expect(variant.text()).not.toContain('forms.duty_duplicate.reason_exact');
  });

  it('explains that gendered variants are automatic only when one is present', () => {
    const withVariant = mountWarning({ ...EMPTY, same_institution: [makeMatch({ reason: 'same_institution_variant' })] });
    expect(withVariant.text()).toContain('forms.duty_duplicate.variant_hint');

    const exactOnly = mountWarning({ ...EMPTY, same_institution: [makeMatch({ reason: 'same_institution_exact' })] });
    expect(exactOnly.text()).not.toContain('forms.duty_duplicate.variant_hint');
  });

  it('offers an Open link only when the admin can manage the match', () => {
    const unmanageable = mountWarning({ ...EMPTY, same_institution: [makeMatch({ can_manage: false })] });
    expect(unmanageable.find('a').exists()).toBe(false);
    expect(unmanageable.text()).toContain('forms.duty_duplicate.contact_admins');

    const manageable = mountWarning({ ...EMPTY, same_institution: [makeMatch({ can_manage: true })] });
    expect(manageable.find('a').exists()).toBe(true);
  });

  it('offers a merge shortcut only when editing an existing duty against a manageable variant', () => {
    const variant = makeMatch({ reason: 'same_institution_variant', can_manage: true });

    // Creating (no currentDutyId yet) — nothing to merge.
    const onCreate = mountWarning({ ...EMPTY, same_institution: [variant] }, null);
    expect(onCreate.text()).not.toContain('forms.duty_duplicate.merge_instead');

    // Editing an existing duty against a manageable variant — offer the shortcut.
    const onEdit = mountWarning({ ...EMPTY, same_institution: [variant] }, 'duty-self');
    expect(onEdit.text()).toContain('forms.duty_duplicate.merge_instead');

    // An exact match isn't a "variant to merge", it's just a duplicate to go look at.
    const exactMatch = makeMatch({ reason: 'same_institution_exact', can_manage: true });
    const exactOnEdit = mountWarning({ ...EMPTY, same_institution: [exactMatch] }, 'duty-self');
    expect(exactOnEdit.text()).not.toContain('forms.duty_duplicate.merge_instead');
  });

  it('does not render the other-institution note while it is intentionally disabled', () => {
    const wrapper = mountWarning({
      ...EMPTY,
      other_institution: [makeMatch({ id: 'duty-2', reason: 'other_institution' })],
      other_institution_count: 57,
    });

    expect(wrapper.text()).not.toContain('forms.duty_duplicate.other_institutions');
    // The informational note must not borrow the amber same-institution styling.
    expect(wrapper.find('.border-amber-300').exists()).toBe(false);
  });

  it('renders same-institution tier without the disabled other-institution note', () => {
    const wrapper = mountWarning({
      same_institution: [makeMatch()],
      other_institution: [makeMatch({ id: 'duty-2' })],
      other_institution_count: 3,
    });

    expect(wrapper.find('.border-amber-300').exists()).toBe(true);
    expect(wrapper.text()).not.toContain('forms.duty_duplicate.other_institutions');
  });
});
