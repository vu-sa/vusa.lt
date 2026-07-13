import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import { defineComponent } from 'vue';

import ReservationResourceStateTag from '../ReservationResourceStateTag.vue';
import ReservationStateSummary from '../ReservationStateSummary.vue';

import type { ReservationStateCount } from '@/Utils/ReservationStatus';

// HoverCard positions with popper, which jsdom cannot do; render the slots inline instead so the
// breakdown is assertable.
const hoverCardStubs = {
  HoverCard: defineComponent({ name: 'HoverCardStub', template: '<div><slot /></div>' }),
  HoverCardTrigger: defineComponent({ name: 'HoverCardTriggerStub', template: '<div><slot /></div>' }),
  HoverCardContent: defineComponent({
    name: 'HoverCardContentStub',
    template: '<div class="hover-card-content"><slot /></div>',
  }),
};

const mountSummary = (states: ReservationStateCount[], unresolved = false) =>
  mount(ReservationStateSummary, {
    props: { states, unresolved },
    global: { stubs: hoverCardStubs },
  });

describe('ReservationStateSummary', () => {
  it('renders a dash when the scope leaves no items', () => {
    const wrapper = mountSummary([]);

    expect(wrapper.text()).toContain('—');
    expect(wrapper.findComponent(ReservationResourceStateTag).exists()).toBe(false);
  });

  it('shows the plain item tag when every item is in the same state', () => {
    const wrapper = mountSummary([{ state: 'lent', count: 3 }], true);

    const tag = wrapper.findComponent(ReservationResourceStateTag);

    expect(tag.exists()).toBe(true);
    expect(tag.props('state')).toBe('lent');
    expect(tag.props('unresolved')).toBe(true);
  });

  it('marks a reservation whose items are in different states as mixed', () => {
    const wrapper = mountSummary([
      { state: 'created', count: 2 },
      { state: 'lent', count: 1 },
    ]);

    expect(wrapper.findComponent(ReservationResourceStateTag).exists()).toBe(false);
    expect(wrapper.text()).toContain('reservations.status_mixed');
    // One dot per state on the badge itself, so the mix is readable without hovering.
    expect(wrapper.findAll('span.-space-x-1 > span')).toHaveLength(2);
  });

  it('breaks the mix down per state, with how many items are in each', () => {
    const wrapper = mountSummary([
      { state: 'created', count: 2 },
      { state: 'lent', count: 1 },
    ]);

    const breakdown = wrapper.find('.hover-card-content');

    expect(breakdown.text()).toContain('State.status.created');
    expect(breakdown.text()).toContain('State.status.lent');
    expect(breakdown.findAll('li').map(item => item.text())).toEqual([
      'State.status.created2',
      'State.status.lent1',
    ]);
  });
});
