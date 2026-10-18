import { afterEach, describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { defineComponent, nextTick, shallowRef } from 'vue';

import StructureGraph from '../StructureGraph.vue';

import { useAccessibilityPreferences } from '@/Composables/useAccessibilityPreferences';

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
