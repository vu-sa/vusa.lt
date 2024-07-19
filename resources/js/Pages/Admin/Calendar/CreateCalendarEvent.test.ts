import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';
import CreateCalendarEvent from './CreateCalendarEvent.vue';

describe('CreateCalendarEvent', () => {
  it('mounts', () => {
    const wrapper = mount(CreateCalendarEvent, { global: { stubs: { PageContent: true, UpsertModelLayout: true, CalendarForm: true } } });

    expect(wrapper.exists()).toBe(true);
  });
});
