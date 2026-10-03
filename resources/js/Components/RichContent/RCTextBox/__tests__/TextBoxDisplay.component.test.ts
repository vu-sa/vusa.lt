import { mount } from '@vue/test-utils';
import { nextTick } from 'vue';
import { describe, expect, it, vi } from 'vitest';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

import TextBoxDisplay from '../../Types/TextBoxDisplay.vue';

import { ssrRoundTrip } from '@/tests/helpers/ssrRoundTrip';
import type { TextBox } from '@/Types/contentParts';

function makeElement(options: TextBox['options'] = null): TextBox & { id: number } {
  return {
    id: 1,
    json_content: {},
    options: {
      title: 'Atsiliepimai',
      placeholder: 'Tavo tekstas...',
      isClosed: false,
      closedMessage: 'Forma uždaryta',
      ...options,
    },
  };
}

describe('TextBoxDisplay', () => {
  it('hydrates the server-rendered form before showing an earlier submission', async () => {
    const { container, hydrationWarnings, unmount } = await ssrRoundTrip(TextBoxDisplay, { element: makeElement() }, {
      beforeHydrate: () => localStorage.setItem('text_box_submitted_1', '1'),
    });

    expect(hydrationWarnings).toEqual([]);
    await nextTick();
    expect(container.querySelector('form')).toBeNull();
    expect(container.textContent).toContain('rich-content.text_box_success');
    unmount();
    localStorage.removeItem('text_box_submitted_1');
  });

  it('renders title and textarea in view mode', () => {
    const wrapper = mount(TextBoxDisplay, {
      props: {
        element: makeElement(),
      },
    });

    expect(wrapper.text()).toContain('Atsiliepimai');
    expect(wrapper.find('textarea').attributes('placeholder')).toBe('Tavo tekstas...');
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
