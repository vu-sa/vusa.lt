import { mount } from '@vue/test-utils';
import { expect, it, vi } from 'vitest';

import { KnowledgeItemKind } from '@/Types/enums';
import type { WikiMaterial } from '@/Types/learning';

import WikiMaterialList from '../WikiMaterialList.vue';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

const material = (id: string, overrides: Partial<WikiMaterial> = {}): WikiMaterial => ({
  id, kind: KnowledgeItemKind.Material, title: `${id} nuostatai`, abbreviation: '', summary: '', summaryHtml: '',
  url: `/mano/knowledge/items/${id}`, thumbnailUrl: null, tenant: id, mine: false, ...overrides,
});

it('shows material as one sideways row of thumbnails, with a placeholder where there is no preview', () => {
  const wrapper = mount(WikiMaterialList, {
    props: { materials: [material('MIF', { mine: true, thumbnailUrl: '/thumb/mif' }), material('FF')], doneIds: new Set(['FF']) },
  });
  expect(wrapper.get('[data-slot="wiki-materials"]').classes()).toContain('overflow-x-auto');
  const tiles = wrapper.findAll('li');
  expect(tiles.map(tile => tile.attributes('data-mine'))).toEqual(['true', undefined]);
  expect(tiles[0]!.get('img').attributes('src')).toBe('/thumb/mif');
  expect(tiles[1]!.find('img').exists()).toBe(false);
  expect(tiles[1]!.find('[aria-label="learning.wiki.learned"]').exists()).toBe(true);
});

it('fades the edge that still has tiles beyond it', async () => {
  const wrapper = mount(WikiMaterialList, { props: { materials: [material('MIF'), material('FF'), material('IF')] } });
  const strip = wrapper.get<HTMLElement>('[data-slot="wiki-materials"]');
  expect(strip.attributes('data-more-right')).toBeUndefined();
  Object.defineProperties(strip.element, { scrollWidth: { value: 600, configurable: true }, clientWidth: { value: 300, configurable: true } });

  await strip.trigger('scroll');
  expect(strip.attributes('data-more-right')).toBe('true');
  expect(strip.attributes('data-more-left')).toBeUndefined();
  expect(strip.element.style.maskImage).toContain('transparent 100%');

  strip.element.scrollLeft = 300;
  await strip.trigger('scroll');
  expect(strip.attributes('data-more-left')).toBe('true');
  expect(strip.attributes('data-more-right')).toBeUndefined();
});
