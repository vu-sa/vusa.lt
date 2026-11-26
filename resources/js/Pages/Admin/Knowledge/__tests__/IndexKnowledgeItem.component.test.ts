import { usePage } from '@inertiajs/vue3';
import { flushPromises, mount } from '@vue/test-utils';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { ArrowUpRight } from '@lucide/vue';

import { scopedGlobals } from '@/tests/helpers/scopedGlobals';
import RecordActionMenu from '@/Components/Patterns/RecordActionMenu.vue';
import IndexKnowledgeItem from '@/Pages/Admin/Knowledge/IndexKnowledgeItem.vue';
import { KnowledgeItemKind } from '@/Types/enums';
import type { KnowledgeLibraryItem } from '@/Types/learning';
import { createMockPage } from '@/tests/helpers/createMockPage';
import { commonStubs } from '@/tests/stubs';

const { stubGlobal, restoreGlobals } = scopedGlobals();
afterEach(restoreGlobals);

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

const originalUrl = window.location.href;
beforeEach(() => {
  stubGlobal('route', (name: string) => `/mocked/${name}`);
  localStorage.clear();
  window.history.replaceState({}, '', '/mano/learning/library?view=rows');
  vi.mocked(usePage).mockReturnValue({ ...createMockPage(), url: '/mano/learning/library' });
  stubGlobal('matchMedia', (query: string) => ({
    matches: query.includes('min-width'), media: query, onchange: null,
    addListener: vi.fn(), removeListener: vi.fn(),
    addEventListener: vi.fn(), removeEventListener: vi.fn(), dispatchEvent: vi.fn(),
  }));
});

afterEach(() => {
  localStorage.clear();
  window.history.replaceState({}, '', originalUrl);
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
