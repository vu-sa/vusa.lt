import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

import PublicAgendaList from '@/Components/Public/PublicAgendaList.vue';
import { commonStubs } from '@/tests/stubs';

function makeItem(overrides: Record<string, unknown> = {}) {
  return {
    id: 'a1',
    order: 1,
    title: 'Dėl studijų programos atnaujinimo',
    type: 'voting',
    description: null,
    start_time: null,
    end_time: null,
    brought_by_students: false,
    main_vote: null,
    votes: [],
    ...overrides,
  };
}

function decided(overrides: Record<string, unknown> = {}) {
  return makeItem({
    main_vote: { id: 'v1', is_main: true, decision: 'positive', student_vote: 'positive', student_benefit: 'positive' },
    ...overrides,
  });
}

function factory(props: Record<string, unknown> = {}) {
  return mount(PublicAgendaList, {
    props: { items: [makeItem()], ...props },
    global: { stubs: commonStubs },
  });
}

describe('PublicAgendaList', () => {
  it('renders one row per agenda item with its number and title', () => {
    const wrapper = factory({ items: [makeItem(), makeItem({ id: 'a2', order: 2, title: 'Kitas klausimas' })] });

    expect(wrapper.text()).toContain('Dėl studijų programos atnaujinimo');
    expect(wrapper.text()).toContain('Kitas klausimas');
    expect(wrapper.findAll('[data-slot], details, div.grid').length).toBeGreaterThan(0);
  });

  it('renders the timetable slot trimmed to HH:MM', () => {
    const wrapper = factory({ items: [makeItem({ start_time: '18:30:00' })] });

    expect(wrapper.text()).toContain('18:30');
    expect(wrapper.text()).not.toContain('18:30:00');
  });

  it('renders a start–end range when the item carries both', () => {
    const wrapper = factory({ items: [makeItem({ start_time: '18:30:00', end_time: '19:15:00' })] });

    expect(wrapper.text()).toContain('18:30–19:15');
  });

  it('never shows the unclassified status publicly', () => {
    // `type: null` is a prompt to the editor, not information for a reader.
    const wrapper = factory({ items: [makeItem({ type: null })] });

    expect(wrapper.text()).toContain('Dėl studijų programos atnaujinimo');
    expect(wrapper.text()).not.toContain('Nepažymėtas');
  });

  it('hides "not discussed" on a meeting that has not happened yet', () => {
    const items = [makeItem({ type: 'voting', main_vote: null })];

    expect(factory({ items }).text()).toContain('Neaptartas');
    expect(factory({ items, isUpcoming: true }).text()).not.toContain('Neaptartas');
  });

  it('collapses detail behind a disclosure only when there is something to show', () => {
    // A bare informational item has neither description nor outcome — nothing to expand.
    const plain = factory({ items: [makeItem({ type: 'informational' })] });
    expect(plain.find('details').exists()).toBe(false);

    const withDescription = factory({ items: [makeItem({ type: 'informational', description: 'Ilgesnis aprašymas' })] });
    expect(withDescription.find('details').exists()).toBe(true);
  });

  it('shows the decision as a chip and the student comparison for external bodies', () => {
    const wrapper = factory({ items: [decided()], requiresStudentPerspective: true });

    expect(wrapper.text()).toContain('Priimtas');
    expect(wrapper.text()).toContain('Studentai');
  });

  it('omits the student perspective entirely for a VU SA body', () => {
    const wrapper = factory({ items: [decided()], requiresStudentPerspective: false });

    expect(wrapper.text()).toContain('Priimtas');
    expect(wrapper.text()).not.toContain('Studentų balsas');
    expect(wrapper.text()).not.toContain('Nauda studentams');
  });

  it('shows a decision-only vote as decided for a VU SA body, not "Neaptartas"', () => {
    const wrapper = factory({
      items: [makeItem({
        main_vote: { id: 'v1', is_main: true, decision: 'positive', student_vote: null, student_benefit: null },
      })],
      requiresStudentPerspective: false,
    });

    expect(wrapper.text()).toContain('Priimtas');
    expect(wrapper.text()).not.toContain('Neaptartas');
  });

  it('renders an empty state that reads differently before the meeting happens', () => {
    expect(factory({ items: [] }).text()).toContain('Darbotvarkė dar neįvesta');
    expect(factory({ items: [], isUpcoming: true }).text()).toContain('Darbotvarkė dar nepaskelbta');
  });

  it('groups the disclosures so opening one row closes the others', () => {
    const wrapper = factory({ items: [decided(), decided({ id: 'a2', order: 2 })] });
    const names = wrapper.findAll('details').map(d => d.attributes('name'));

    expect(names).toHaveLength(2);
    expect(new Set(names).size).toBe(1);
    expect(names[0]).toBeTruthy();
  });
});
