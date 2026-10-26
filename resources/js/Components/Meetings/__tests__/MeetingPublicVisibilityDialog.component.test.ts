import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';

import MeetingPublicVisibilityDialog from '../MeetingPublicVisibilityDialog.vue';
import type { MeetingPublicVisibility } from '../meetingPublicVisibility';

const passthrough = { template: '<div><slot /></div>' };

function mountDialog(mode: MeetingPublicVisibility['mode']) {
  return mount(MeetingPublicVisibilityDialog, {
    props: { open: true, mode },
    global: {
      stubs: { Dialog: passthrough, DialogContent: passthrough, DialogHeader: passthrough, DialogTitle: passthrough, DialogDescription: passthrough },
    },
  });
}

describe('MeetingPublicVisibilityDialog', () => {
  it('lists what a public meeting shows and that its files never are', () => {
    const wrapper = mountDialog('public_page');

    expect(wrapper.find('[data-slot="visibility-shown"]').text()).toContain('meetings.visibility.shown.documents');
    expect(wrapper.find('[data-slot="visibility-never"]').text()).toContain('meetings.visibility.never.files');
    expect(wrapper.text()).toContain('meetings.visibility.public_intro');
  });

  it('shows nothing as published for an internal meeting', () => {
    const wrapper = mountDialog('internal');

    expect(wrapper.find('[data-slot="visibility-shown"]').exists()).toBe(false);
    expect(wrapper.find('[data-slot="visibility-never"]').exists()).toBe(true);
    expect(wrapper.text()).toContain('meetings.visibility.internal_intro');
  });

  it('lists only the agenda and documents for a published calendar event', () => {
    const shown = mountDialog('calendar_event').find('[data-slot="visibility-shown"]').text();

    expect(shown).toContain('meetings.visibility.shown.agenda');
    expect(shown).toContain('meetings.visibility.shown.documents');
    expect(shown).not.toContain('meetings.visibility.shown.representatives');
  });
});
