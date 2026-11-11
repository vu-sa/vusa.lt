import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';

import ShowGlossaryTerm from '@/Pages/Admin/Knowledge/ShowGlossaryTerm.vue';
import { LearningImportance } from '@/Types/enums';
import type { GlossaryTermPageData } from '@/Types/learning';
import { commonStubs } from '@/tests/stubs';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

const item = (id: string, inPath: boolean, importance: LearningImportance | null = null): GlossaryTermPageData['items'][number] => ({
  id, title: `Item ${id}`, url: `/mano/knowledge/items/${id}`, inPath, importance, state: 'pending',
  section: { id: 'intro', title: 'Įvadiniai mokymai' }, source: null,
});

const term = (items: GlossaryTermPageData['items']): GlossaryTermPageData => ({
  id: 'term-1', term: 'Kadencija', abbreviation: '', definition: 'Laikotarpis, kuriam esi išrinktas.',
  translations: { term: { lt: 'Kadencija', en: 'Term' }, definition: { lt: 'Laikotarpis', en: 'Period' } },
  items,
});

const render = (props: { term: GlossaryTermPageData; canUpdate?: boolean; canDelete?: boolean }) => mount(ShowGlossaryTerm, {
  props,
  global: { stubs: { ...commonStubs, GlossaryTermSheet: true, ConfirmDialog: true } },
});

describe('ShowGlossaryTerm', () => {
  it('lists items from the viewer\'s path first, in the order given, and the rest apart', () => {
    const wrapper = render({ term: term([item('a', true, LearningImportance.Essential), item('b', true, LearningImportance.Optional), item('c', false)]) });

    expect(wrapper.get('[data-slot="term-items"]').findAll('[data-item]').map(row => row.attributes('data-item'))).toEqual(['a', 'b']);
    expect(wrapper.get('[data-slot="term-elsewhere"]').text()).toContain('Item c');
    expect(wrapper.get('[data-slot="term-elsewhere"]').text()).toContain('learning.glossary.elsewhere');
    wrapper.unmount();
  });

  it('titles the only list as the explanation when none of the items is in the path', () => {
    const wrapper = render({ term: term([item('c', false)]) });

    expect(wrapper.find('[data-slot="term-items"]').exists()).toBe(false);
    expect(wrapper.get('[data-slot="term-elsewhere"] h2').text()).toBe('learning.glossary.explained_in');
    wrapper.unmount();
  });

  it('offers editing only to term managers', () => {
    expect(render({ term: term([]) }).findComponent({ name: 'GlossaryTermSheet' }).exists()).toBe(false);
    expect(render({ term: term([]), canUpdate: true }).findComponent({ name: 'GlossaryTermSheet' }).exists()).toBe(true);
  });
});
