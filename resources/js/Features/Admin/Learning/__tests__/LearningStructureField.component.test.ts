import { describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';

import LearningStructureField, { type LearningStructureDraft } from '../LearningStructureField.vue';

const mountField = (structure: LearningStructureDraft[]) => {
  const wrapper = mount(LearningStructureField, {
    props: {
      'locale': 'lt' as const,
      'errors': {},
      'modelValue': structure,
      'onUpdate:modelValue': (value: LearningStructureDraft[]) => wrapper.setProps({ modelValue: value }),
    },
    global: { stubs: { FlowGraph: { props: ['graphKey', 'highlight'], template: '<div data-testid="preview" :data-graph="graphKey" :data-highlight="highlight" />' } } },
  });
  return wrapper;
};

describe('LearningStructureField', () => {
  it('lists each graph with its nodes and previews the highlighted one', async () => {
    const wrapper = mountField([{ graph: 'vusa-structure', node: 'node-taryba' }, { graph: 'vu', node: null }]);

    // A graph's nodes load as their own chunk.
    await vi.waitFor(() => expect(wrapper.get('select[data-testid="structure-node-0"]').findAll('option').map(option => option.attributes('value'))).toContain('node-taryba'));
    expect(wrapper.findAll('[data-testid="preview"]').map(preview => [preview.attributes('data-graph'), preview.attributes('data-highlight')]))
      .toEqual([['vusa-structure', 'node-taryba'], ['vu', undefined]]);
  });

  it('offers each graph once and adds the next free one', async () => {
    const structure: LearningStructureDraft[] = [{ graph: 'vusa-structure', node: null }];
    const wrapper = mountField(structure);

    expect(wrapper.get('select[data-testid="structure-graph-0"]').findAll('option').map(option => option.attributes('value'))).toContain('vusa-structure');
    await wrapper.get('[data-testid="structure-add"]').trigger('click');

    expect(structure.map(place => place.graph)).toEqual(['vusa-structure', 'vu']);
    expect(wrapper.get('select[data-testid="structure-graph-1"]').findAll('option').map(option => option.attributes('value'))).not.toContain('vusa-structure');
  });

  it('removes a graph without touching the others', async () => {
    const structure: LearningStructureDraft[] = [{ graph: 'vu', node: 'node-senatas' }, { graph: 'am', node: null }];
    const wrapper = mountField(structure);

    await wrapper.get('[data-testid="structure-remove-0"]').trigger('click');

    expect(structure).toEqual([{ graph: 'am', node: null }]);
  });
});
