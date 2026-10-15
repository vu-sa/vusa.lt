import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import { defineComponent, h } from 'vue';

import CollectionStatusMenu from '../CollectionStatusMenu.vue';

import { contentStatuses } from '@/Constants/statuses';
import { commonStubs } from '@/tests/stubs';

const passthrough = defineComponent({ setup: (_, { slots }) => () => h('div', slots.default?.()) });
const RadioGroup = defineComponent({
  name: 'DropdownMenuRadioGroup',
  props: { modelValue: { type: String, default: '' } },
  emits: ['update:modelValue'],
  setup: (_, { slots }) => () => h('div', slots.default?.()),
});

const options = [
  { value: 'published', status: contentStatuses.published },
  { value: 'draft', status: contentStatuses.draft },
];

function mountMenu(props: Record<string, unknown> = {}) {
  return mount(CollectionStatusMenu, {
    props: { status: contentStatuses.published, modelValue: 'published', options, ...props },
    global: {
      stubs: {
        ...commonStubs,
        DropdownMenu: passthrough,
        DropdownMenuTrigger: passthrough,
        DropdownMenuContent: passthrough,
        DropdownMenuRadioItem: passthrough,
        DropdownMenuRadioGroup: RadioGroup,
      },
    },
  });
}

describe('CollectionStatusMenu', () => {
  it('is a plain badge when the viewer may not change the status', () => {
    const wrapper = mountMenu();

    expect(wrapper.find('button').exists()).toBe(false);
    expect(wrapper.text()).toContain('Paskelbta');
  });

  it('offers every option and emits a different choice only', async () => {
    const wrapper = mountMenu({ editable: true });

    expect(wrapper.find('[data-slot="collection-status-menu"]').attributes('aria-label')).toContain('Paskelbta');
    expect(wrapper.text()).toContain('Juodraštis');

    const group = wrapper.findComponent(RadioGroup);
    group.vm.$emit('update:modelValue', 'published');
    group.vm.$emit('update:modelValue', 'draft');

    expect(wrapper.emitted('update:modelValue')).toEqual([['draft']]);
  });
});
