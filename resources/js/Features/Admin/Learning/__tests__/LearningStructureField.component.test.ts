import { describe, expect, it, vi } from 'vitest';
import { flushPromises, mount } from '@vue/test-utils';

import LearningStructureField from '../LearningStructureField.vue';

const mountField = (graph: string | null, node: string | null) => mount(LearningStructureField, {
  props: {
    locale: 'lt' as const,
    graph,
    node,
    'onUpdate:graph': (value: string | null) => wrapper.setProps({ graph: value }),
    'onUpdate:node': (value: string | null) => wrapper.setProps({ node: value }),
  },
  global: { stubs: { FlowGraph: { props: ['graphKey', 'highlight'], template: '<div data-testid="preview" :data-graph="graphKey" :data-highlight="highlight" />' } } },
});
let wrapper: ReturnType<typeof mountField>;

describe('LearningStructureField', () => {
  it('offers the chosen graph\'s nodes and previews the highlighted one', async () => {
    wrapper = mountField('vusa-structure', 'node-taryba');

    // The graph's nodes load as their own chunk.
    await vi.waitFor(() => expect(wrapper.get('select[data-testid="structure-node"]').findAll('option').map(option => option.attributes('value'))).toContain('node-taryba'));
    expect(wrapper.get('[data-testid="preview"]').attributes()).toMatchObject({ 'data-graph': 'vusa-structure', 'data-highlight': 'node-taryba' });
  });

  it('clears the node when another graph is chosen', async () => {
    wrapper = mountField('vusa-structure', 'node-taryba');
    await flushPromises();

    await wrapper.get('select[data-testid="structure-graph"]').setValue('vu');
    await flushPromises();

    expect(wrapper.emitted('update:node')?.at(-1)).toEqual([null]);
  });

  it('has no node picker or preview without a graph', () => {
    wrapper = mountField(null, null);

    expect(wrapper.find('[data-testid="structure-node"]').exists()).toBe(false);
    expect(wrapper.find('[data-testid="preview"]').exists()).toBe(false);
  });
});
