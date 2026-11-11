import { router, usePage } from '@inertiajs/vue3';
import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import ShowKnowledgeBase from '@/Pages/Admin/Knowledge/ShowKnowledgeBase.vue';
import { LearningImportance } from '@/Types/enums';
import type { WikiIndexEntry } from '@/Types/learning';
import { createMockPage } from '@/tests/helpers/createMockPage';
import { commonStubs } from '@/tests/stubs';
import { knowledge } from '../../Learning/__tests__/fixtures';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));
beforeEach(() => {
  vi.clearAllMocks();
  vi.mocked(usePage).mockReturnValue({ ...createMockPage(), url: '/mano/knowledge' });
});

const entry = (id: string, title: string, overrides: Partial<WikiIndexEntry> = {}): WikiIndexEntry => ({
  id, title, abbreviation: '', summary: '', url: `/mano/knowledge/items/${id}`, sectionId: null, updatedAt: null, ...overrides,
});
const sections = [
  { id: 'second', title: 'Antras', description: '', order: 2 },
  { id: 'first', title: 'Pirmas', description: 'Mokymų medžiaga', order: 1 },
  { id: 'empty', title: 'Tuščias', description: '', order: 3 },
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
function render(knowledgeItems = items()) {
  return mount(ShowKnowledgeBase, {
    props: { knowledge: { sections: sections.slice(0, 2), items: knowledgeItems }, index, sections },
    global: { stubs: commonStubs },
  });
}

describe('Wiki home', () => {
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
    await wrapper.get('[data-testid="wiki-for-you-all"]').trigger('click');
    expect(router.replace).toHaveBeenLastCalledWith(expect.objectContaining({ url: '/mano/knowledge?view=importance' }));
    wrapper.unmount();

    vi.mocked(usePage).mockReturnValue({ ...createMockPage(), url: '/mano/knowledge?view=sections' });
    const mine = render();
    expect(mine.findAll('[data-slot="document-section"]').map(group => group.get('h2').text())).toEqual(['Pirmas 0/1', 'Antras 1/1', 'learning.documents.other 1/3']);
    mine.unmount();
  });

  it('says so when everything essential is read, and teaches the empty list for you', () => {
    const wrapper = render([knowledge({ id: 'done', state: 'done', importance: LearningImportance.Essential })]);
    expect(wrapper.get('[data-slot="wiki-for-you"]').text()).toContain('learning.wiki.for_you_done');
    wrapper.unmount();

    vi.mocked(usePage).mockReturnValue({ ...createMockPage(), url: '/mano/knowledge?view=importance' });
    const empty = render([]);
    expect(empty.get('[data-slot="empty-state"]').text()).toContain('learning.documents.empty_title');
    empty.unmount();
  });
});
