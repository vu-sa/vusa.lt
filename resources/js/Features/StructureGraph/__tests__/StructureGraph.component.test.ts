import { afterEach, describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { defineComponent, nextTick, shallowRef } from 'vue';

import { useAccessibilityPreferences } from '@/Composables/useAccessibilityPreferences';

import StructureGraph from '../StructureGraph.vue';

afterEach(() => useAccessibilityPreferences().reset());

describe('StructureGraph motion', () => {
  it('stops edge and double-click zoom animation on a live preference change', async () => {
    const preferences = useAccessibilityPreferences();
    preferences.reset();
    const duration = vi.fn(() => 250);
    const interrupt = vi.fn();
    const instance = { d3Zoom: shallowRef({ duration }), d3Selection: shallowRef({ interrupt }) };
    const FlowStub = defineComponent({
      props: ['edges'],
      emits: ['init'],
      mounted() { this.$emit('init', instance); },
      template: '<div />',
    });
    const wrapper = mount(StructureGraph, {
      props: { nodes: [], edges: [{ id: 'a-b', source: 'a', target: 'b' }], animated: true },
      global: { stubs: { VueFlow: FlowStub } },
    });
    try {
      expect(wrapper.getComponent(FlowStub).props('edges')[0].animated).toBe(true);
      preferences.reduceMotion.value = true;
      await nextTick();
      expect(wrapper.getComponent(FlowStub).props('edges')[0].animated).toBe(false);
      expect(duration).toHaveBeenLastCalledWith(0);
      expect(interrupt).toHaveBeenCalledOnce();
      preferences.reduceMotion.value = false;
      await nextTick();
      expect(duration).toHaveBeenLastCalledWith(250);
    }
    finally {
      wrapper.unmount();
    }
  });
});

describe('StructureGraph focus', () => {
  it('fades what a clicked node is not tied to, and restores it on an empty click', async () => {
    const FlowStub = defineComponent({
      props: ['nodes', 'edges'],
      emits: ['init', 'nodeClick', 'paneClick'],
      template: '<div />',
    });
    const wrapper = mount(StructureGraph, {
      props: {
        nodes: [{ id: 'a', position: { x: 0, y: 0 } }, { id: 'b', position: { x: 0, y: 0 } }, { id: 'c', position: { x: 0, y: 0 } }],
        edges: [{ id: 'a-b', source: 'a', target: 'b' }, { id: 'b-c', source: 'b', target: 'c' }],
      },
      global: { stubs: { VueFlow: FlowStub } },
    });
    const flow = () => wrapper.getComponent(FlowStub);
    const faded = (items: { id: string; class?: string }[]) => items.filter(item => item.class?.includes('is-faded')).map(item => item.id);

    flow().vm.$emit('nodeClick', { node: { id: 'a' } });
    await nextTick();
    expect(faded(flow().props('nodes'))).toEqual(['c']);
    expect(faded(flow().props('edges'))).toEqual(['b-c']);

    flow().vm.$emit('paneClick');
    await nextTick();
    expect(faded(flow().props('nodes'))).toEqual([]);
  });
});
