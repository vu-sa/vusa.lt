import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

import CalendarRelatedMeetingPanel from '../CalendarRelatedMeetingPanel.vue';

const meeting = {
  id: 'meeting-123',
  title: 'Valdybos posėdis',
  institution_name: 'VU SA Valdyba',
  start_time: '2026-05-01 10:00:00',
  trashed: false,
  agenda_items_count: 3,
};

const mountPanel = (props: Record<string, unknown> = {}) => mount(CalendarRelatedMeetingPanel, {
  props: { meeting, isDraft: false, ...props },
  global: { stubs: { FormPanel: { template: '<div><slot /></div>' } } },
});

describe('CalendarRelatedMeetingPanel', () => {
  it('links the announced meeting and counts its agenda', () => {
    const wrapper = mountPanel();
    expect(wrapper.get('a').text()).toContain('VU SA Valdyba');
    expect(wrapper.text()).toContain('3 darbotvarkės punktai');
    expect(wrapper.text()).toContain('meetings.announce.form_alert_published');
  });

  it('says what a draft announcement means and flags a trashed meeting', () => {
    const wrapper = mountPanel({ isDraft: true, meeting: { ...meeting, trashed: true } });
    expect(wrapper.text()).toContain('meetings.announce.form_alert_draft');
    expect(wrapper.text()).toContain('meetings.announce.form_alert_trashed');
  });
});
