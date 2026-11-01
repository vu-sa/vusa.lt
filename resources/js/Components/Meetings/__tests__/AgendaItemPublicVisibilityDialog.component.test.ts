import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

import AgendaItemPublicVisibilityDialog from '../AgendaItemPublicVisibilityDialog.vue';

import { commonStubs } from '@/tests/stubs';

const render = (mode: string, isPrivate: boolean) => mount(AgendaItemPublicVisibilityDialog, {
  props: { open: true, mode, isPrivate } as never,
  global: { stubs: { ...commonStubs, DialogDescription: { template: '<p data-testid="intro"><slot /></p>' } } },
});

describe('AgendaItemPublicVisibilityDialog', () => {
  it('lists the public row of an ordinary item', () => {
    const text = render('public_page', false).get('[data-slot="visibility-shown"]').text();

    expect(text).toContain('meetings.visibility.item.shown.votes');
    expect(text).not.toContain('meetings.visibility.item.shown.position');
  });

  it('leaves a private item only its place and public title, whatever the meeting', () => {
    const wrapper = render('public_page', true);

    expect(wrapper.get('[data-slot="visibility-shown"]').findAll('li')).toHaveLength(2);
    expect(wrapper.get('[data-slot="visibility-never"]').text()).toContain('meetings.visibility.item.never.outcome');
  });

  it('says an unpublished meeting shows nothing yet', () => {
    const wrapper = render('internal', false);

    expect(wrapper.get('[data-testid="intro"]').text()).toContain('meetings.visibility.item.intro.unpublished');
    expect(wrapper.get('[data-slot="visibility-shown"]').text()).toContain('meetings.visibility.item.would_show_heading');
  });
});
