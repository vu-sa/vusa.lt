import { router, usePage } from '@inertiajs/vue3';
import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import ShowKnowledgeBase from '@/Pages/Admin/Knowledge/ShowKnowledgeBase.vue';
import ShowKnowledgeForYou from '@/Pages/Admin/Knowledge/ShowKnowledgeForYou.vue';
import { KnowledgeItemKind, KnowledgeSectionKind, LearningImportance } from '@/Types/enums';
import type { SelfLearnedItemData, WikiIndexEntry } from '@/Types/learning';
import { createMockPage } from '@/tests/helpers/createMockPage';
import { commonStubs } from '@/tests/stubs';

import { knowledge } from '../../Learning/__tests__/fixtures';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));
beforeEach(() => {
  vi.clearAllMocks();
  vi.mocked(usePage).mockReturnValue({ ...createMockPage(), url: '/mano/knowledge' });
});

const entry = (id: string, title: string, overrides: Partial<WikiIndexEntry> = {}): WikiIndexEntry => ({
  id, kind: KnowledgeItemKind.Term, title, abbreviation: '', summary: '', summaryHtml: '', url: `/mano/knowledge/items/${id}`, thumbnailUrl: null, sectionId: null, countsInProgress: true, updatedAt: null, ...overrides,
});
// The server sends topics in their order, then collections.
const sections = [
  { id: 'first', kind: KnowledgeSectionKind.Topic, title: 'Pirmas', description: 'Mokymų medžiaga', date: null, order: 1 },
  { id: 'second', kind: KnowledgeSectionKind.Topic, title: 'Antras', description: '', date: null, order: 2 },
  { id: 'empty', kind: KnowledgeSectionKind.Topic, title: 'Tuščias', description: '', date: null, order: 3 },
];
const index = [
  entry('akreditacija', 'Akreditacija', { sectionId: 'first', summary: 'Leidimas vykdyti studijas.', updatedAt: '2026-11-01T00:00:00Z' }),
  entry('skvc', 'Studijų kokybės vertinimo centras', { abbreviation: 'SKVC', sectionId: 'first', updatedAt: '2026-11-10T00:00:00Z' }),
  entry('senatas', 'Senatas', { sectionId: 'second' }),
  entry('zodis', 'Žodynas'),
];
const items = () => [
  knowledge({ id: 'done', state: 'done', importance: LearningImportance.Essential, section: { id: 'second', title: 'Antras' } }),
  knowledge({ id: 'recommended', section: { id: 'first', title: 'Pirmas' } }),
  knowledge({ id: 'unread', state: 'updated', importance: LearningImportance.Essential }),
  knowledge({ id: 'optional', importance: LearningImportance.Optional }),
  knowledge({ id: 'skipped', state: 'skipped', importance: LearningImportance.Essential }),
];
function render(knowledgeItems = items(), extra: { selfLearned?: SelfLearnedItemData[]; doneIds?: string[] } = {}) {
  const selfLearned = extra.selfLearned ?? [];
  return mount(ShowKnowledgeBase, {
    props: {
      knowledge: { sections: sections.slice(0, 2), items: knowledgeItems, selfLearned, stats: { pathDone: 0, pathTotal: knowledgeItems.length, selfLearned: selfLearned.length } },
      index, sections, doneIds: extra.doneIds,
    },
    global: { stubs: commonStubs },
  });
}

function renderMine(knowledgeItems = items(), selfLearned: SelfLearnedItemData[] = []) {
  return mount(ShowKnowledgeForYou, {
    props: { knowledge: { sections: sections.slice(0, 2), items: knowledgeItems, selfLearned, stats: { pathDone: 0, pathTotal: knowledgeItems.length, selfLearned: selfLearned.length } }, assignedProgress: knowledgeItems.length
      ? [
          { importance: LearningImportance.Essential, done: 2, total: 3 },
          { importance: LearningImportance.Recommended, done: 0, total: 1 },
          { importance: LearningImportance.Optional, done: 0, total: 1 },
        ]
      : [] },
    global: { stubs: commonStubs },
  });
}

describe('Wiki home', () => {
  it('shows only whole-Wiki progress at home and assigned priority progress on for-you', () => {
    const wrapper = render(items(), { doneIds: ['skvc'] });
    expect(wrapper.find('[data-slot="wiki-progress-assigned"]').exists()).toBe(false);
    expect(wrapper.get('[data-slot="wiki-progress-all"] dt').classes()).toContain('sr-only');
    expect(wrapper.get('[data-slot="wiki-progress-all"] [role="progressbar"]').attributes('aria-valuenow')).toBe('25');
    expect(wrapper.text()).not.toContain('learning.documents.lead');
    wrapper.unmount();

    const mine = renderMine();
    const assigned = mine.get('[data-slot="wiki-progress-assigned"]');
    expect(assigned.get('dt').classes()).toContain('sr-only');
    expect(assigned.text()).toContain('2/5');
    expect(assigned.get('[data-priority="essential"]').attributes('aria-valuenow')).toBe('2');
    expect(assigned.get('[data-priority="essential"]').attributes('aria-valuemax')).toBe('3');
    expect(assigned.get('[data-priority="recommended"]').attributes('aria-valuenow')).toBe('0');
    expect(assigned.get('[data-priority="optional"]').classes()).toContain('border-dashed');
    expect(mine.find('[data-slot="wiki-progress-all"]').exists()).toBe(false);
    expect(mine.find('[data-slot="wiki-progress-legend"]').exists()).toBe(true);
    mine.unmount();
  });

  it('leads with the unread essentials for you, then sections as portals, recent changes and letters', () => {
    const wrapper = render();
    expect(wrapper.get('[data-slot="wiki-for-you"]').findAll('[data-step]').map(row => row.attributes('data-step'))).toEqual(['unread', 'recommended']);
    const portals = wrapper.get('[data-slot="wiki-sections"]').findAll('h3').map(heading => heading.text());
    expect(portals).toEqual(['Pirmas', 'Antras', 'learning.wiki.unsorted']);
    expect(wrapper.text()).not.toContain('Tuščias');
    expect(wrapper.get('[data-slot="wiki-aside"]').findAll('[data-slot="wiki-page-list"] li')[0]!.text()).toContain('SKVC');
    expect(wrapper.get('[data-slot="wiki-aside"]').findAll('nav li').map(letter => letter.text())).toEqual(['A', 'S', 'Ž']);
    wrapper.unmount();
  });

  it('leaves exempt articles out of the whole-Wiki count unless they are on your path', () => {
    const wrapper = mount(ShowKnowledgeBase, {
      props: {
        knowledge: { sections: [], items: [knowledge({ id: 'mine-unit' })], selfLearned: [], stats: { pathDone: 0, pathTotal: 1, selfLearned: 0 } },
        index: [
          entry('kvorumas', 'Kvorumas'),
          entry('mine-unit', 'MIF nuostatai', { kind: KnowledgeItemKind.Material, countsInProgress: false }),
          entry('other-unit', 'FF nuostatai', { kind: KnowledgeItemKind.Material, countsInProgress: false }),
        ],
        sections: [], doneIds: ['kvorumas', 'other-unit'],
      },
      global: { stubs: commonStubs },
    });
    expect(wrapper.get('[data-slot="wiki-progress"] [role="progressbar"]').attributes('aria-valuetext')).toBe('1/2');
    wrapper.unmount();
  });

  it('searches titles, abbreviations and summaries without diacritics and replaces the home blocks', async () => {
    const wrapper = render();
    await wrapper.get('[data-slot="wiki-search"] input').setValue('skvc');
    expect(wrapper.find('[data-slot="wiki-sections"]').exists()).toBe(false);
    expect(wrapper.get('[data-slot="wiki-results"]').text()).toContain('Studijų kokybės vertinimo centras');
    await wrapper.get('[data-slot="wiki-search"] input').setValue('zodynas');
    expect(wrapper.get('[data-slot="wiki-results"]').findAll('li')).toHaveLength(1);
    await wrapper.get('[data-slot="wiki-search"] input').setValue('vykdyti');
    expect(wrapper.get('[data-slot="wiki-results"]').text()).toContain('Akreditacija');
    wrapper.unmount();
  });

  it('opens the full list for you by importance or section and keeps the view in the URL', async () => {
    const wrapper = render();
    expect(wrapper.get('[data-testid="wiki-for-you-all"]').attributes('href')).toBe(route('knowledgeBase.mine'));
    wrapper.unmount();

    vi.mocked(usePage).mockReturnValue({ ...createMockPage(), url: '/mano/knowledge/for-you?view=sections' });
    const mine = renderMine();
    expect(mine.findAll('[data-slot="document-section"]').map(group => group.get('h2').text())).toEqual(['Pirmas 0/1', 'Antras 1/1', 'learning.documents.other 1/3']);
    await mine.get('[data-testid="documents-view-importance"]').trigger('click');
    expect(router.replace).toHaveBeenLastCalledWith(expect.objectContaining({ url: route('knowledgeBase.mine', { view: 'importance' }) }));
    mine.unmount();
  });

  it('says so when everything essential is read, and teaches the empty list for you', () => {
    const wrapper = render([knowledge({ id: 'done', state: 'done', importance: LearningImportance.Essential })]);
    expect(wrapper.get('[data-slot="wiki-for-you"]').text()).toContain('learning.wiki.for_you_done');
    wrapper.unmount();

    vi.mocked(usePage).mockReturnValue({ ...createMockPage(), url: '/mano/knowledge/for-you' });
    const empty = renderMine([]);
    expect(empty.get('[data-slot="empty-state"]').text()).toContain('learning.documents.empty_title');
    expect(empty.get('[data-slot="wiki-progress-assigned"]').text()).toContain('0/0');
    expect(empty.findAll('[data-priority]')).toHaveLength(0);
    empty.unmount();
  });

  it('lists articles you learned on your own after your path, and ticks learned articles in lists', () => {
    vi.mocked(usePage).mockReturnValue({ ...createMockPage(), url: '/mano/knowledge/for-you' });
    const self: SelfLearnedItemData = { ...knowledge({ id: 'kvorumas', title: 'Kvorumas', state: 'done' }), importance: null };
    const mine = renderMine(items(), [self]);
    const groups = mine.findAll('[data-slot="document-section"]');
    expect(groups.at(-1)!.get('h2').text()).toBe('learning.wiki.self_learned 1/1');
    expect(groups.at(-1)!.text()).toContain('Kvorumas');
    mine.unmount();

    vi.mocked(usePage).mockReturnValue({ ...createMockPage(), url: '/mano/knowledge' });
    const home = render(items(), { doneIds: ['skvc'] });
    const learned = home.get('[data-slot="wiki-aside"]').findAll('[data-slot="wiki-page-learned"]');
    expect(learned).toHaveLength(1);
    home.unmount();
  });
});
