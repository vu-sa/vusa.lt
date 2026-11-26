import { mount } from '@vue/test-utils';
import { nextTick } from 'vue';
import { afterEach, describe, expect, it, vi } from 'vitest';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

import { ssrRoundTrip } from '@/tests/helpers/ssrRoundTrip';
import type { TextBox } from '@/Types/contentParts';

import TextBoxDisplay from '../TextBoxDisplay.vue';

function makeElement(options: TextBox['options'] = {}): TextBox & { id: number } {
  return {
    id: 1,
    json_content: {},
    options: {
      title: { lt: 'Atsiliepimai', en: 'Feedback' },
      placeholder: { lt: 'Tavo tekstas...', en: 'Your text...' },
      isClosed: false,
      closedMessage: { lt: 'Forma uždaryta', en: 'Form closed' },
      ...options,
    },
  };
}

describe('TextBoxDisplay', () => {
  let unmountHydrated: (() => void) | undefined;

  afterEach(() => {
    unmountHydrated?.();
    unmountHydrated = undefined;
    localStorage.removeItem('text_box_submitted_1');
  });

  it('hydrates the server-rendered form before showing an earlier submission', async () => {
    const { container, hydrationWarnings, unmount } = await ssrRoundTrip(TextBoxDisplay, { element: makeElement() }, {
      beforeHydrate: () => localStorage.setItem('text_box_submitted_1', '1'),
    });

    unmountHydrated = unmount;
    expect(hydrationWarnings).toEqual([]);
    await nextTick();
    expect(container.querySelector('form')).toBeNull();
    expect(container.textContent).toContain('rich-content.text_box_success');
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
