import { mount } from '@vue/test-utils';
import { describe, it, expect } from 'vitest';

import MeetingAgendaList from '../MeetingAgendaList.vue';

import { commonStubs } from '@/tests/stubs';

const baseStubs = {
  ...commonStubs,
  // Visual children we don't need to exercise here
  SpotlightPopover: { template: '<div><slot /></div>' },
  AdminVotingHelpButton: { template: '<div />' },
  VoteStatusIndicator: { template: '<span class="vote-indicator" />' },
  Switch: {
    props: ['modelValue'],
    emits: ['update:modelValue'],
    template: '<button class="switch" @click="$emit(\'update:modelValue\', !modelValue)" />',
  },
};

const makeItem = (overrides: Record<string, any> = {}) => ({
  id: 'item-1',
  meeting_id: 'm1',
  title: 'Test agenda item',
  order: 1,
  brought_by_students: false,
  type: 'voting',
  votes: [],
  ...overrides,
});

describe('MeetingAgendaList', () => {
  it('renders read-only rows linking to the per-item edit page', () => {
    const wrapper = mount(MeetingAgendaList, {
      props: { agendaItems: [makeItem()] as any, meetingId: 'm1' },
      global: { stubs: baseStubs },
    });

    const link = wrapper.find('a');
    expect(link.exists()).toBe(true);
    expect(link.attributes('href')).toContain('agendaItems.edit');
    expect(wrapper.text()).toContain('Test agenda item');
  });

  it('does not show drag handles or remove buttons in read-only mode', () => {
    const wrapper = mount(MeetingAgendaList, {
      props: { agendaItems: [makeItem()] as any, meetingId: 'm1', editing: false },
      global: { stubs: baseStubs },
    });

    expect(wrapper.find('.drag-handle').exists()).toBe(false);
    expect(wrapper.find('[aria-label="Šalinti"]').exists()).toBe(false);
  });

  it('reveals drag handles and a remove button in edit mode', () => {
    const wrapper = mount(MeetingAgendaList, {
      props: { agendaItems: [makeItem()] as any, meetingId: 'm1', editing: true },
      global: { stubs: baseStubs },
    });

    expect(wrapper.find('.drag-handle').exists()).toBe(true);
    expect(wrapper.find('[aria-label="Šalinti"]').exists()).toBe(true);
  });

  it('emits delete with the item when the remove button is clicked', async () => {
    const item = makeItem();
    const wrapper = mount(MeetingAgendaList, {
      props: { agendaItems: [item] as any, meetingId: 'm1', editing: true },
      global: { stubs: baseStubs },
    });

    await wrapper.find('[aria-label="Šalinti"]').trigger('click');

    expect(wrapper.emitted('delete')?.[0]?.[0]).toMatchObject({ id: 'item-1' });
  });

  it('shows the vote count label only when there is more than one vote', () => {
    const single = mount(MeetingAgendaList, {
      props: {
        agendaItems: [makeItem({ votes: [{ id: 'v1', is_main: true, decision: 'positive' }] })] as any,
        meetingId: 'm1',
      },
      global: { stubs: baseStubs },
    });
    expect(single.text()).not.toContain('balsavimai');

    const multiple = mount(MeetingAgendaList, {
      props: {
        agendaItems: [makeItem({
          votes: [
            { id: 'v1', is_main: true, decision: 'positive' },
            { id: 'v2', is_main: false, decision: 'negative' },
          ],
        })] as any,
        meetingId: 'm1',
      },
      global: { stubs: baseStubs },
    });
    expect(multiple.text()).toContain('balsavimai');
  });
});
