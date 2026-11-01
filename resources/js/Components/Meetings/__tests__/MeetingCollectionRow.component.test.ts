import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';

import MeetingCollectionRow from '../MeetingCollectionRow.vue';

import CollectionRowActions from '@/Components/Collection/CollectionRowActions.vue';
import { commonStubs } from '@/tests/stubs';
import type { MeetingSearchResult } from '@/Shared/Search/types';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

const meeting = (overrides: Partial<MeetingSearchResult> = {}): MeetingSearchResult => ({
  id: '01j0000000000000000000000a',
  title: 'Senato posėdis',
  // 2026-09-18 10:00 in Europe/Vilnius
  start_time: Date.parse('2026-09-18T07:00:00Z') / 1000,
  institution_name_lt: 'VU SA Senato atstovai',
  tenant_shortname: 'VU SA',
  agenda_items_count: 4,
  type: 'in-person',
  completion_status: 'complete',
  ...overrides,
});

const mountRow = (props: { meeting: MeetingSearchResult; pinned?: boolean }) => mount(MeetingCollectionRow, { props });

describe('MeetingCollectionRow', () => {
  it('renders the supplied actions through the shared row component and forwards commands', () => {
    const actions = [{ key: 'open', role: 'open' as const, label: 'Atidaryti', href: '/mano/meetings/1' }, { key: 'delete', label: 'Ištrinti', destructive: true }];
    const wrapper = mount(MeetingCollectionRow, { props: { meeting: meeting(), actions }, global: { stubs: commonStubs } });
    expect(wrapper.findComponent(CollectionRowActions).props('actions')).toEqual(actions);
    wrapper.findComponent(CollectionRowActions).vm.$emit('select', 'delete');
    expect(wrapper.emitted('select')).toEqual([['delete']]);
  });

  it('links the whole title block to the record and marks it as the row\'s main link', () => {
    const wrapper = mountRow({ meeting: meeting() });
    const main = wrapper.find('a[data-collection-open]');

    expect(main.exists()).toBe(true);
    expect(main.attributes('href')).toContain('meetings.show');
    expect(main.text()).toContain('Senato posėdis');
    expect(main.text()).toContain('VU SA Senato atstovai');
  });

  it('leads with the institution and leaves the date to the title', () => {
    const wrapper = mountRow({ meeting: meeting() });

    expect(wrapper.find('a[data-collection-open] .font-bold').text()).toBe('VU SA Senato atstovai');
    expect(wrapper.find('time').exists()).toBe(false);
  });

  it('keeps a complete meeting quiet', () => {
    const wrapper = mountRow({ meeting: meeting({ completion_status: 'complete' }) });

    expect(wrapper.find('[data-slot="meeting-completion"]').exists()).toBe(false);
  });

  it.each([
    ['incomplete', 'attention', 'Neužpildyta'],
    ['no_items', 'attention', 'Nėra darbotvarkės'],
  ])('marks a %s meeting once, with its role and word', (status, role, label) => {
    const wrapper = mountRow({ meeting: meeting({ completion_status: status }) });

    const badge = wrapper.find('[data-slot="meeting-completion"]');
    expect(badge.attributes('data-status-role')).toBe(role);
    expect(badge.text()).toBe(label);
    expect(wrapper.findAll('[data-slot="meeting-completion"]')).toHaveLength(1);
  });

  it('is not every meeting a posėdis: an e-mail one is a decision and shows no time', () => {
    const wrapper = mountRow({ meeting: meeting({ type: 'email', start_time: Date.parse('2026-09-18T20:59:00Z') / 1000 }) });

    expect(wrapper.text()).toContain('Sprendimas el. paštu');
    // 23:59 is a deadline marker for an e-mail meeting, not a start time.
    expect(wrapper.text()).not.toContain('23:59');
  });

  it('names a remote meeting, and leaves an in-person one unlabelled', () => {
    expect(mountRow({ meeting: meeting({ type: 'remote' }) }).text()).toContain('Nuotolinis posėdis');
    expect(mountRow({ meeting: meeting({ type: 'in-person' }) }).text()).not.toContain('Nuotolinis posėdis');
  });

  it('says so when the row is a pinned change the index has not caught up with', () => {
    expect(mountRow({ meeting: meeting(), pinned: true }).text()).toContain('Ką tik atnaujinta');
    expect(mountRow({ meeting: meeting() }).text()).not.toContain('Ką tik atnaujinta');
  });

  it('falls back to the English institution name and a placeholder title', () => {
    const wrapper = mountRow({ meeting: meeting({ title: '', institution_name_lt: undefined, institution_name_en: 'Senate representatives' }) });

    expect(wrapper.text()).toContain('Be pavadinimo');
    expect(wrapper.text()).toContain('Senate representatives');
  });

  it('stays minimal beside the detail card', () => {
    const wrapper = mount(MeetingCollectionRow, { props: { meeting: meeting({ outcome_aligned: 3, vote_alignment_status: 'all_match', completion_status: 'incomplete' }), compact: true }, global: { stubs: commonStubs } });

    expect(wrapper.find('[data-slot="meeting-outcome"]').exists()).toBe(false);
  });

  it('folds the vote split into one tag and adds only what the tag cannot say', () => {
    const wrapper = mountRow({ meeting: meeting({ outcome_aligned: 3, outcome_misaligned: 1, outcome_discussed: 2, vote_alignment_status: 'mixed', agenda_items_count: 6 }) });

    const tag = wrapper.get('[data-slot="status-badge"]');
    expect(tag.attributes('data-status-role')).toBe('attention');
    expect(tag.text()).toBe('meetings.outcome.matched_of');

    const counts = wrapper.get('[data-slot="meeting-outcome-counts"]').text();
    expect(counts).toContain('meetings.outcome.discussed');
    expect(counts).not.toContain('meetings.outcome.aligned');
  });
});
