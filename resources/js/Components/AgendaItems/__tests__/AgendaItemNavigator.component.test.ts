import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

import AgendaItemNavigator from '@/Components/AgendaItems/AgendaItemNavigator.vue';
import { commonStubs } from '@/tests/stubs';

const siblings = [
  { id: 'a', title: 'First', type: 'voting', order: 1, brought_by_students: false },
  { id: 'b', title: 'Second', type: 'informational', order: 2, brought_by_students: true },
  { id: 'c', title: 'Third', type: 'deferred', order: 3, brought_by_students: false },
];

function factory(props: Record<string, unknown> = {}) {
  return mount(AgendaItemNavigator, {
    props: {
      meetingId: 'm1',
      meetingTitle: 'VU Senatas',
      currentId: 'b',
      siblingAgendaItems: siblings,
      ...props,
    },
    global: {
      stubs: {
        ...commonStubs,
        Link: { template: '<a><slot /></a>' },
        Popover: { template: '<div><slot /></div>' },
        PopoverTrigger: { template: '<div><slot /></div>' },
        PopoverContent: { template: '<div><slot /></div>' },
      },
    },
  });
}

describe('AgendaItemNavigator', () => {
  it('shows the current position out of the total', () => {
    const wrapper = factory();
    // current is index 1 → "2"
    expect(wrapper.text()).toContain('2');
    expect(wrapper.text()).toContain('3');
  });

  it('emits navigate with the previous item id', async () => {
    const wrapper = factory();
    const prevButton = wrapper.find('[title="Ankstesnis punktas"]');
    await prevButton.trigger('click');
    expect(wrapper.emitted('navigate')?.[0]).toEqual(['a']);
  });

  it('emits navigate with the next item id', async () => {
    const wrapper = factory();
    const nextButton = wrapper.find('[title="Kitas punktas"]');
    await nextButton.trigger('click');
    expect(wrapper.emitted('navigate')?.[0]).toEqual(['c']);
  });

  it('disables previous navigation on the first item', () => {
    const wrapper = factory({ currentId: 'a' });
    const prevButton = wrapper.find('[title="Ankstesnis punktas"]');
    expect(prevButton.attributes('disabled')).toBeDefined();
  });

  it('disables next navigation on the last item', () => {
    const wrapper = factory({ currentId: 'c' });
    const nextButton = wrapper.find('[title="Kitas punktas"]');
    expect(nextButton.attributes('disabled')).toBeDefined();
  });
});
