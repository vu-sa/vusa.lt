import { usePage } from '@inertiajs/vue3';
import { flushPromises, mount } from '@vue/test-utils';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { ArrowUpRight } from '@lucide/vue';

import RecordActionMenu from '@/Components/Patterns/RecordActionMenu.vue';
import IndexKnowledgeItem from '@/Pages/Admin/Knowledge/IndexKnowledgeItem.vue';
import IndexLearningStep from '@/Pages/Admin/Learning/IndexLearningStep.vue';
import { KnowledgeItemKind, LearningAudienceType, LearningImportance, LearningStage, LearningStepKind } from '@/Types/enums';
import type { KnowledgeLibraryItem, LearningLibraryStep } from '@/Types/learning';
import { createMockPage } from '@/tests/helpers/createMockPage';
import { commonStubs } from '@/tests/stubs';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

const originalUrl = window.location.href;
const steps: LearningLibraryStep[] = [
  {
    id: 'guide', title: 'Read the guide', kind: LearningStepKind.MeetingPrep,
    audiences: [{ id: 'a1', type: LearningAudienceType.Everyone, label: 'Visi', stage: LearningStage.BeforeStart, importance: LearningImportance.Essential }],
    audienceTypes: [LearningAudienceType.Everyone], order: 0, isActive: true, canUpdate: false,
  },
  {
    id: 'action', title: 'Meet your team', kind: LearningStepKind.Action,
    audiences: [{ id: 'a2', type: LearningAudienceType.InstitutionType, label: 'Taryba', stage: LearningStage.BeforeStart, importance: LearningImportance.Recommended }],
    audienceTypes: [LearningAudienceType.InstitutionType], order: 1, isActive: true, canUpdate: true,
  },
];

beforeEach(() => {
  vi.stubGlobal('route', (name: string) => `/mocked/${name}`);
  localStorage.clear();
  window.history.replaceState({}, '', '/mano/learning/library?view=rows');
  vi.mocked(usePage).mockReturnValue({ ...createMockPage(), url: '/mano/learning/library' });
  vi.stubGlobal('matchMedia', (query: string) => ({
    matches: query.includes('min-width'), media: query, onchange: null,
    addListener: vi.fn(), removeListener: vi.fn(),
    addEventListener: vi.fn(), removeEventListener: vi.fn(), dispatchEvent: vi.fn(),
  }));
});

afterEach(() => {
  vi.unstubAllGlobals();
  localStorage.clear();
  window.history.replaceState({}, '', originalUrl);
});

function render(canCreate = false) {
  return mount(IndexLearningStep, {
    props: { steps, canCreate },
    global: { stubs: {
      ...commonStubs,
      Sheet: commonStubs.Dialog, SheetContent: commonStubs.DialogContent,
      SheetHeader: commonStubs.DialogHeader, SheetTitle: commonStubs.DialogTitle, SheetDescription: commonStubs.DialogDescription,
    } },
  });
}

const rowTitles = (wrapper: ReturnType<typeof render>) =>
  wrapper.findAll('[data-slot="collection-row"]').map(row => row.get('a').text());

describe('learning library', () => {
  it('offers only the main splits as quick filters', async () => {
    const wrapper = render();
    await flushPromises();
    expect(wrapper.findAll('[data-filter-id]').map(chip => chip.attributes('data-filter-id'))).toEqual(['kind:action', 'kind:meeting_prep']);
    wrapper.unmount();
  });

  it.each([
    ['kind', 'action', 'Meet your team'],
    ['kind', 'meeting_prep', 'Read the guide'],
  ])('narrows rows with the %s quick filter', async (field, value, title) => {
    const filter = `${field}:${value}`;
    const wrapper = render();
    await flushPromises();
    expect(wrapper.find('[data-slot=learning-tabs]').exists()).toBe(false);
    expect(rowTitles(wrapper)).toEqual(['Read the guide', 'Meet your team']);

    await wrapper.get(`[data-filter-id="${filter}"]`).trigger('click');
    expect(rowTitles(wrapper)).toEqual([title]);
    expect(wrapper.get(`[data-filter-id="${filter}"]`).attributes('aria-pressed')).toBe('true');
    expect(new URLSearchParams(window.location.search).get(field)).toBe(value);

    await wrapper.get(`[data-filter-id="${filter}"]`).trigger('click');
    expect(rowTitles(wrapper)).toEqual(['Read the guide', 'Meet your team']);
    wrapper.unmount();
  });

  it('lets the active chips clear filters', async () => {
    const wrapper = render();
    await flushPromises();
    await wrapper.get('[data-filter-id="kind:action"]').trigger('click');
    expect(rowTitles(wrapper)).toEqual(['Meet your team']);
    expect(new URLSearchParams(window.location.search).get('kind')).toBe('action');
    const clear = wrapper.get('[data-slot="collection-active-chips"]').findAll('button').find(button => button.text() === 'Išvalyti visus');
    expect(clear).toBeDefined();
    await clear!.trigger('click');
    expect(rowTitles(wrapper)).toEqual(['Read the guide', 'Meet your team']);
    expect(window.location.search).not.toContain('repeat=');
    expect(window.location.search).not.toContain('kind=');
    wrapper.unmount();
  });

  it('uses the controller permission prop for the creation action', async () => {
    const wrapper = render();
    expect(wrapper.find('[data-testid="create-learning-step"]').exists()).toBe(false);
    await wrapper.setProps({ canCreate: true });
    expect(wrapper.get('[data-testid="create-learning-step"]').attributes('href')).toContain('learningSteps.create');
    wrapper.unmount();
  });
});

it('shows only two audience labels plus a remainder and uses the highest importance', async () => {
  const wrapper = render();
  const base = steps[1]!;
  await wrapper.setProps({ steps: [{ ...base, audiences: [
    { ...base.audiences[0]!, label: 'Viena', importance: LearningImportance.Optional },
    { ...base.audiences[0]!, id: 'a3', label: 'Antra', importance: LearningImportance.Essential },
    { ...base.audiences[0]!, id: 'a4', label: 'Trečia', importance: LearningImportance.Recommended },
  ] }] });
  await flushPromises();
  const row = wrapper.get('[data-slot="collection-row"]');
  expect(row.text()).toContain('Viena, Antra +1');
  expect(row.text()).not.toContain('Trečia');
  expect(row.text()).toContain('learning.importance.essential');
  wrapper.unmount();
});

it('opens sections from the library header', async () => {
  const wrapper = mount(IndexKnowledgeItem, { props: { items: [], canCreate: true }, global: { stubs: commonStubs } });
  await wrapper.get('[data-testid="manage-sections"]').trigger('click');
  expect(wrapper.findComponent({ name: 'LearningSectionsSheet' }).props('open')).toBe(true);
  wrapper.unmount();
});

it.each([true, false])('leads the Wiki table with open and keeps edit in the menu when editable is %s', async (canUpdate) => {
  window.history.replaceState({}, '', '/mano/knowledge/library?view=table');
  vi.mocked(usePage).mockReturnValue({ ...createMockPage(), url: '/mano/knowledge/library?view=table' });
  const item: KnowledgeLibraryItem = {
    id: 'article', kind: KnowledgeItemKind.Term, title: 'Wiki article', abbreviation: '', hasSource: false, linkBroken: false,
    audiences: [], audienceTypes: [], section: null, order: 0, isActive: true, canUpdate, flags: [],
  };
  const wrapper = mount(IndexKnowledgeItem, { props: { items: [item] }, global: { stubs: commonStubs } });
  await flushPromises();
  const actions = wrapper.get('[data-slot="collection-row-actions"]');
  const open = actions.get('[data-action="open"]');

  expect(open.attributes('data-collection-row-action')).toBe('primary');
  expect(open.text()).toBe('Atidaryti');
  expect(open.findComponent(ArrowUpRight).exists()).toBe(true);
  expect(actions.findComponent(RecordActionMenu).props('actions').map((action: { key: string }) => action.key)).toEqual(canUpdate ? ['edit'] : []);
  wrapper.unmount();
});

it('finds Wiki entries without a source through a quick filter instead of a row tag', async () => {
  window.history.replaceState({}, '', '/mano/knowledge/library?view=rows');
  vi.mocked(usePage).mockReturnValue({ ...createMockPage(), url: '/mano/knowledge/library?view=rows' });
  const entry = (id: string, hasSource: boolean): KnowledgeLibraryItem => ({
    id, kind: KnowledgeItemKind.Term, title: id, abbreviation: '', hasSource, linkBroken: false,
    audiences: [], audienceTypes: [], section: null, order: 0, isActive: true, canUpdate: true, flags: [],
  });
  const wrapper = mount(IndexKnowledgeItem, { props: { items: [entry('Sourced', true), entry('Unsourced', false)] }, global: { stubs: commonStubs } });
  await flushPromises();
  const titles = () => wrapper.findAll('[data-slot="collection-row"]').map(row => row.get('a').text());
  expect(titles()).toEqual(['Sourced', 'Unsourced']);

  await wrapper.get('[data-filter-id="source:without"]').trigger('click');

  expect(titles()).toEqual(['Unsourced']);
  expect(wrapper.find('[data-testid^="curator-flag-"]').exists()).toBe(false);
  wrapper.unmount();
});

it('finds broken links through the link quick filter', async () => {
  window.history.replaceState({}, '', '/mano/knowledge/library?view=rows');
  vi.mocked(usePage).mockReturnValue({ ...createMockPage(), url: '/mano/knowledge/library?view=rows' });
  const entry = (id: string, linkBroken: boolean): KnowledgeLibraryItem => ({
    id, kind: KnowledgeItemKind.Term, title: id, abbreviation: '', hasSource: true, linkBroken,
    audiences: [], audienceTypes: [], section: null, order: 0, isActive: true, canUpdate: true,
    flags: linkBroken ? ['broken_link'] : [],
  });
  const wrapper = mount(IndexKnowledgeItem, { props: { items: [entry('Broken', true), entry('Working', false)] }, global: { stubs: commonStubs } });
  await flushPromises();
  const titles = () => wrapper.findAll('[data-slot="collection-row"]').map(row => row.get('a').text());
  expect(titles()).toEqual(['Broken', 'Working']);

  await wrapper.get('[data-filter-id="link:broken"]').trigger('click');

  expect(titles()).toEqual(['Broken']);
  expect(new URLSearchParams(window.location.search).get('link')).toBe('broken');
  await wrapper.get('[data-filter-id="link:broken"]').trigger('click');
  expect(titles()).toEqual(['Broken', 'Working']);
  wrapper.unmount();
});
