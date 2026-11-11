import { mount } from '@vue/test-utils';
import { expect, it, vi } from 'vitest';

import WikiLinkCard from '../WikiLinkCard.vue';
import { KnowledgeItemKind } from '@/Types/enums';
import type { WikiPageSummary } from '@/Types/learning';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

const page = (overrides: Partial<WikiPageSummary> = {}): WikiPageSummary => ({
  id: 'kvorumas', kind: KnowledgeItemKind.Term, title: 'Kvorumas', abbreviation: '',
  summary: 'Kvorumas – mažiausias narių skaičius.', summaryHtml: '<strong>Kvorumas</strong> – mažiausias narių skaičius.',
  url: '/mano/knowledge/items/kvorumas', ...overrides,
});

it('is one link to the article that leads with the bold summary instead of repeating the title', () => {
  const wrapper = mount(WikiLinkCard, { props: { page: page() } });
  const links = wrapper.findAll('a');
  expect(links).toHaveLength(1);
  expect(links[0]!.attributes('href')).toBe('/mano/knowledge/items/kvorumas');
  expect(wrapper.get('[data-slot="wiki-link-card-summary"] strong').text()).toBe('Kvorumas');
  expect(wrapper.text().match(/Kvorumas/g)).toHaveLength(1);
  expect(wrapper.text()).toContain('learning.kinds.term');
});

it('falls back to the title without a summary and shows an abbreviation the summary leaves out', () => {
  const wrapper = mount(WikiLinkCard, { props: { page: page({ summary: '', summaryHtml: '', abbreviation: 'KV' }) } });
  expect(wrapper.text()).toContain('Kvorumas');
  expect(wrapper.get('abbr').text()).toBe('KV');
});
