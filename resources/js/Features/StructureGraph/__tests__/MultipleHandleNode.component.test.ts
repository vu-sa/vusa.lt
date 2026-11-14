import { describe, expect, it } from 'vitest';
import { defineComponent } from 'vue';
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
  it('opens its details when selected or focused, not before', async () => {
    const wrapper = mountNode({ data: { label: 'Senatas', handles: [], description: 'Akademinės savivaldos organas.' } });
    expect(wrapper.get('[data-testid="toolbar"]').attributes('data-visible')).toBe('false');

    await wrapper.get('button').trigger('focus');
    expect(wrapper.get('[data-testid="toolbar"]').attributes('data-visible')).toBe('true');

    await wrapper.get('button').trigger('blur');
    await wrapper.setProps({ selected: true });
    expect(wrapper.get('[data-testid="toolbar"]').attributes('data-visible')).toBe('true');
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
