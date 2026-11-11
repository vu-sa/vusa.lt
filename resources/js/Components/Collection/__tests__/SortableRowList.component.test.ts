import { mount } from '@vue/test-utils';
import { expect, it, vi } from 'vitest';
import SortableRowList from '../SortableRowList.vue';

const { option } = vi.hoisted(() => ({ option: vi.fn() }));
vi.mock('@/Composables/useMotionAwareSortable', () => ({ useMotionAwareSortable: () => ({ option }) }));

it('moves rows with arrows and prevents moves while disabled', async () => {
  const wrapper = mount(SortableRowList, { props: { items: ['a', 'b'] } });
  const buttons = wrapper.findAll('button');
  expect(buttons[1]!.attributes('disabled')).toBeDefined();
  await buttons[2]!.trigger('click');
  expect(wrapper.emitted('update:items')).toEqual([[['b', 'a']]]);
  await wrapper.setProps({ disabled: true });
  expect(option).toHaveBeenCalledWith('disabled', true);
  await buttons[2]!.trigger('click');
  expect(wrapper.emitted('update:items')).toHaveLength(1);
  wrapper.unmount();
});
