import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';

import { useAccessibilityPreferences } from '@/Composables/useAccessibilityPreferences';
import { KnowledgeItemKind } from '@/Types/enums';
import type { WikiGraphNode, WikiGraphPayload } from '@/Types/learning';

import WikiGraph from '../WikiGraph.vue';

const preferences = useAccessibilityPreferences();
let wrapper: ReturnType<typeof mount>;

const node = (id: string, title: string, degree: number, sectionId: string | null = 's1'): WikiGraphNode => ({
  id, title, degree, sectionId, kind: KnowledgeItemKind.Term, abbreviation: '', summary: `${title} summary`, summaryHtml: '', url: `/mano/knowledge/items/${id}`, thumbnailUrl: null, countsInProgress: true, updatedAt: null,
});

const graph: WikiGraphPayload = {
  nodes: [node('a', 'Senatas', 1), node('b', 'Taryba', 1), node('c', 'Kvorumas', 0, null)],
  edges: [{ source: 'a', target: 'b' }],
  sections: [{ id: 's1', title: 'Universitetas' }],
};

beforeEach(() => {
  vi.stubGlobal('ResizeObserver', class {
    observe = vi.fn();
    disconnect = vi.fn();
  });
  preferences.reduceMotion.value = true;
});

afterEach(() => {
  wrapper?.unmount();
  preferences.reset();
  vi.unstubAllGlobals();
});

describe('WikiGraph', () => {
  it('draws every term and marks learned ones', () => {
    wrapper = mount(WikiGraph, { props: { graph, doneIds: ['b'] } });

    expect(wrapper.findAll('[data-node-id]')).toHaveLength(3);
    expect(wrapper.findAll('line')).toHaveLength(1);
    expect(wrapper.find('[data-node-id="b"] [data-learned]').exists()).toBe(true);
    expect(wrapper.find('[data-node-id="a"] [data-learned]').exists()).toBe(false);
  });

  it('opens a panel with the term, its neighbours and a link when a term is tapped', async () => {
    wrapper = mount(WikiGraph, { props: { graph, 'onUpdate:selected': (id: string | null) => wrapper.setProps({ selected: id }) } });

    await wrapper.get('[data-node-id="a"]').trigger('click');

    const panel = wrapper.get('[data-slot="wiki-graph-selected"]');
    expect(panel.text()).toContain('Senatas');
    expect(panel.text()).toContain('Taryba');
    expect(panel.get('a').attributes('href')).toBe('/mano/knowledge/items/a');
  });
});
