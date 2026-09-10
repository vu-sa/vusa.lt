import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

import TextBoxDisplay from '../../Types/TextBoxDisplay.vue';

import type { TextBox } from '@/Types/contentParts';

function makeElement(options: TextBox['options'] = null): TextBox & { id: number } {
  return {
    id: 1,
    json_content: {},
    options: {
      title: 'Atsiliepimai',
      placeholder: 'Jūsų tekstas...',
      isClosed: false,
      closedMessage: 'Forma uždaryta',
      ...options,
    },
  };
}

describe('TextBoxDisplay', () => {
  it('renders title and textarea in view mode', () => {
    const wrapper = mount(TextBoxDisplay, {
      props: {
        element: makeElement(),
      },
    });

    expect(wrapper.text()).toContain('Atsiliepimai');
    expect(wrapper.find('textarea').attributes('placeholder')).toBe('Jūsų tekstas...');
  });

  it('renders closed message when isClosed is true', () => {
    const wrapper = mount(TextBoxDisplay, {
      props: {
        element: makeElement({ isClosed: true }),
      },
    });

    expect(wrapper.text()).toContain('Forma uždaryta');
    expect(wrapper.find('textarea').exists()).toBe(false);
  });

  it('renders inline editable text when editable is true', () => {
    const wrapper = mount(TextBoxDisplay, {
      props: {
        element: makeElement(),
        editable: true,
        blockKey: 'tb-1',
      },
      global: {
        stubs: {
          RCInlineText: { props: ['modelValue'], template: '<span class="inline-text-mock">{{ modelValue }}</span>' },
        },
      },
    });

    expect(wrapper.find('.inline-text-mock').text()).toBe('Atsiliepimai');
  });
});
