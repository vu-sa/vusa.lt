import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

import MeetingCollectionRow from '../MeetingCollectionRow.vue';

import type { MeetingSearchResult } from '@/Shared/Search/types';

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
  it('links the whole title block to the record and marks it as the row\'s main link', () => {
    const wrapper = mountRow({ meeting: meeting() });
    const main = wrapper.find('a[data-collection-open]');

    expect(main.exists()).toBe(true);
    expect(main.attributes('href')).toContain('meetings.show');
    expect(main.text()).toContain('Senato posėdis');
    expect(main.text()).toContain('VU SA Senato atstovai');
  });

  it('shows the date plate in Vilnius time', () => {
    const wrapper = mountRow({ meeting: meeting() });

    expect(wrapper.find('time').text()).toContain('18');
    expect(wrapper.find('time').attributes('datetime')).toBe('2026-09-18T07:00:00.000Z');
  });

  it('keeps a complete meeting quiet while offering its record link', () => {
    const wrapper = mountRow({ meeting: meeting({ completion_status: 'complete' }) });

    expect(wrapper.find('[data-slot="status-badge"]').exists()).toBe(false);
    expect(wrapper.findAll('a').some(link => link.text() === 'Atidaryti')).toBe(true);
  });

  it.each([
    ['incomplete', 'attention', 'Neužpildyta'],
    ['no_items', 'attention', 'Nėra darbotvarkės'],
  ])('marks a %s meeting once, with its role and word, and points at the record to fill it in', (status, role, label) => {
    const wrapper = mountRow({ meeting: meeting({ completion_status: status }) });

    const badge = wrapper.find('[data-slot="status-badge"]');
    expect(badge.attributes('data-status-role')).toBe(role);
    expect(badge.text()).toBe(label);
    expect(wrapper.findAll('[data-slot="status-badge"]')).toHaveLength(1);

    const action = wrapper.findAll('a').find(link => link.text() === 'Atidaryti');
    expect(action?.attributes('href')).toContain('meetings.show');
    // The action is not the row's main link, so the preview view cannot swallow it.
    expect(action?.attributes('data-collection-open')).toBeUndefined();
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
});
