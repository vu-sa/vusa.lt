import { mount } from '@vue/test-utils';
import { usePage } from '@inertiajs/vue3';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import { createMockPage } from '@/tests/helpers/createMockPage';
import LearningPathView from '../LearningPathView.vue';
import { path, step } from './fixtures';
import { commonStubs } from '@/tests/stubs';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));
beforeEach(() => vi.mocked(usePage).mockReturnValue(createMockPage()));

describe('LearningPathView', () => {
  it('shows every stage that has steps, with its rows and no disclosures', () => {
    const wrapper = mount(LearningPathView, { props: { path: path() }, global: { stubs: commonStubs } });
    expect(wrapper.findAll('[data-stage]')).toHaveLength(3);
    expect(wrapper.get('[data-slot="current-stage"]').text()).toBe('learning.path.now');
    expect(wrapper.get('[data-slot="current-stage"]').attributes('aria-current')).toBe('step');
    expect(wrapper.findAll('details')).toHaveLength(0);
    expect(wrapper.findAll('[data-step]')).toHaveLength(3);
    expect(wrapper.text()).toContain('Pagrindas');
    expect(wrapper.text()).toContain('1/1');
    wrapper.unmount();
  });

  it('keeps meeting preparation a plain list without progress controls', () => {
    const wrapper = mount(LearningPathView, { props: { path: path() }, global: { stubs: commonStubs } });
    const preparation = wrapper.get('[data-slot="meeting-prep"]');
    expect(preparation.text()).toContain('SPK');
    expect(preparation.text()).toContain('Read the agenda');
    expect(preparation.findAll('button, input')).toHaveLength(0);
    wrapper.unmount();
  });

  it('shows an empty state when there are no steps', () => {
    const data = path();
    data.stages.forEach(stage => { stage.steps = []; });
    const wrapper = mount(LearningPathView, { props: { path: data }, global: { stubs: commonStubs } });
    expect(wrapper.get('[data-slot="empty-state"]').text()).toContain('learning.path.empty_title');
    wrapper.unmount();
  });
});

it('places documents before the timeline and the page slot before meeting prep', () => {
  const data = path();
  data.meetingPrep[0]!.steps.push(step({ id: 'prep-two', title: 'Second preparation' }));
  const wrapper = mount(LearningPathView, {
    props: { path: data }, slots: { 'before-meeting-prep': '<div data-slot="tiles">Tiles</div>' }, global: { stubs: commonStubs },
  });
  const blocks = wrapper.findAll('[data-slot="documents-entry"], [data-stage], [data-slot="tiles"], [data-slot="meeting-prep"]');
  expect(blocks[0]!.attributes('data-slot')).toBe('documents-entry');
  expect(blocks[1]!.attributes('data-stage')).toBe('before_start');
  expect(blocks.at(-2)!.attributes('data-slot')).toBe('tiles');
  expect(blocks.at(-1)!.attributes('data-slot')).toBe('meeting-prep');
  const prep = wrapper.get('[data-slot="meeting-prep"]');
  expect(prep.findAll('ol > li > span').map(number => number.text())).toEqual(['1.', '2.']);
  wrapper.unmount();
});

it('groups task rows by audience in first-seen order', () => {
  const data = path();
  data.stages[0]!.steps = [step({ id: 'b-1', audienceLabel: 'B' }), step({ id: 'a', audienceLabel: 'A' }), step({ id: 'b-2', audienceLabel: 'B' })];
  const wrapper = mount(LearningPathView, { props: { path: data }, global: { stubs: commonStubs } });
  const stage = wrapper.get('[data-stage="before_start"]');
  expect(stage.findAll('h3').map(heading => heading.text())).toEqual(['B 0/2', 'A 0/1']);
  expect(stage.findAll('[data-step]').map(row => row.attributes('data-step'))).toEqual(['b-1', 'b-2', 'a']);
  wrapper.unmount();
});
