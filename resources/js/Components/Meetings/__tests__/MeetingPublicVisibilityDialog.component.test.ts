import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';

import MeetingPublicVisibilityDialog from '../MeetingPublicVisibilityDialog.vue';

const passthrough = { template: '<div><slot /></div>' };

function mountDialog(isPublic: boolean) {
  return mount(MeetingPublicVisibilityDialog, {
    props: { open: true, isPublic },
    global: {
      stubs: { Dialog: passthrough, DialogContent: passthrough, DialogHeader: passthrough, DialogTitle: passthrough, DialogDescription: passthrough },
    },
  });
}

describe('MeetingPublicVisibilityDialog', () => {
  it('lists what a public meeting shows and that its files never are', () => {
    const wrapper = mountDialog(true);

    expect(wrapper.find('[data-slot="visibility-shown"]').text()).toContain('meetings.visibility.shown.documents');
    expect(wrapper.find('[data-slot="visibility-never"]').text()).toContain('meetings.visibility.never.files');
    expect(wrapper.text()).toContain('meetings.visibility.public_intro');
  });

  it('shows nothing as published for an internal meeting', () => {
    const wrapper = mountDialog(false);

    expect(wrapper.find('[data-slot="visibility-shown"]').exists()).toBe(false);
    expect(wrapper.find('[data-slot="visibility-never"]').exists()).toBe(true);
    expect(wrapper.text()).toContain('meetings.visibility.internal_intro');
  });
});
