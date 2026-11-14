import { describe, expect, it, vi } from 'vitest';
import { defineComponent, nextTick } from 'vue';
import { mount } from '@vue/test-utils';

import MultipleHandleNode from '../MultipleHandleNode.vue';

const ToolbarStub = defineComponent({
  name: 'NodeToolbar',
  props: ['isVisible'],
  template: '<div data-testid="toolbar" :data-visible="String(isVisible)"><slot /></div>',
});

const mountNode = (props: Record<string, unknown>) => mount(MultipleHandleNode, {
  props: { id: 'n', data: { label: 'Senatas', handles: [] }, ...props },
  global: { stubs: { Handle: true, NodeToolbar: ToolbarStub, Link: { props: ['href'], template: '<a :href="href"><slot /></a>' } } },
});

describe('MultipleHandleNode', () => {
  it('shows its details while hovered, with a moment to reach the popover, and never pins them on click', async () => {
    vi.useFakeTimers();
    try {
      const wrapper = mountNode({ data: { label: 'Senatas', handles: [], description: 'Akademinės savivaldos organas.' } });
      const visible = () => wrapper.get('[data-testid="toolbar"]').attributes('data-visible');
      expect(visible()).toBe('false');

      await wrapper.get('button').trigger('mouseenter');
      expect(visible()).toBe('true');

      await wrapper.get('button').trigger('mouseleave');
      await wrapper.get('[data-testid="toolbar"]').trigger('mouseenter');
      vi.advanceTimersByTime(300);
      await nextTick();
      expect(visible()).toBe('true');

      await wrapper.get('[data-testid="toolbar"]').trigger('mouseleave');
      await wrapper.get('button').trigger('click');
      await wrapper.get('button').trigger('focus');
      vi.advanceTimersByTime(300);
      await nextTick();
      expect(visible()).toBe('false');
    }
    finally {
      vi.useRealTimers();
    }
  });

  it('marks a node that leads to a wiki article and links to it', () => {
    const wrapper = mountNode({ data: { label: 'Senatas', handles: [], article: { title: 'Senatas', url: '/mano/wiki/senatas' } } });

    expect(wrapper.find('[data-testid="structure-node-linked"]').exists()).toBe(true);
    expect(wrapper.get('[data-testid="structure-node-article"]').attributes('href')).toBe('/mano/wiki/senatas');
  });

  it('has no details for a bare node', () => {
    expect(mountNode({}).find('[data-testid="toolbar"]').exists()).toBe(false);
  });
});
