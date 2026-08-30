import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';

import InstitutionMeetingsPreview from '../InstitutionMeetingsPreview.vue';

type MeetingsProp = InstanceType<typeof InstitutionMeetingsPreview>['$props']['meetings'];

const makeMeetings = (overrides: Record<string, unknown> = {}): MeetingsProp => ([{
  id: 'm1',
  title: 'Rugsėjo 08 d. posėdis',
  start_time: '2099-09-08T10:00:00.000Z',
  agenda_item_titles: ['Dėl studijų kokybės', 'Dėl stipendijų', 'Dėl rinkimų'],
  agenda_items_count: 5,
  ...overrides,
}] as unknown as MeetingsProp);

const mountPreview = (meetings: MeetingsProp) => mount(InstitutionMeetingsPreview, {
  props: {
    meetings,
    institution: { id: '1', name: 'VU Senatas' },
    totalCount: 28,
  },
});

describe('InstitutionMeetingsPreview', () => {
  it('previews the first agenda items and how many are left', () => {
    const text = mountPreview(makeMeetings()).text();

    expect(text).toContain('Dėl studijų kokybės');
    expect(text).toContain('Dėl rinkimų');
    // The i18n mock echoes keys rather than interpolating, so assert the line, not the number.
    expect(text).toContain('ir dar :count');
  });

  it('says so when a meeting has no agenda', () => {
    const text = mountPreview(makeMeetings({ agenda_item_titles: [], agenda_items_count: 0 })).text();

    expect(text).toContain('Nėra darbotvarkės');
    expect(text).not.toContain('ir dar :count');
  });

  it('marks an upcoming meeting apart from a past one', () => {
    expect(mountPreview(makeMeetings()).text()).toContain('Būsimas');
    expect(mountPreview(makeMeetings({ start_time: '2020-01-08T10:00:00.000Z' })).text())
      .toContain('Įvykęs');
  });
});
