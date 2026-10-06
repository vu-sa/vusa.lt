import { defineComponent, markRaw } from 'vue';
import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

import EmptyState from '../EmptyState.vue';

import TurtleMascot from '@/Components/Empty/TurtleMascot.vue';

describe('EmptyState (Patterns)', () => {
  const defaultProps = {
    title: 'Nėra duomenų',
    description: 'Čia bus rodomi tavo duomenys.',
  };

  it('falls back to TurtleMascot in empty mode when no icon is provided', () => {
    const wrapper = mount(EmptyState, {
      props: defaultProps,
    });

    expect(wrapper.findComponent(TurtleMascot).exists()).toBe(true);
    expect(wrapper.find('[data-slot="empty-state"]').exists()).toBe(true);
  });

  it('renders a custom icon in a square hairline chip rather than rounded-full', () => {
    const TestIcon = markRaw(defineComponent({
      template: '<svg data-testid="test-icon" />',
    }));

    const wrapper = mount(EmptyState, {
      props: {
        ...defaultProps,
        icon: TestIcon,
      },
    });

    expect(wrapper.find('[data-testid="test-icon"]').exists()).toBe(true);
    expect(wrapper.findComponent(TurtleMascot).exists()).toBe(false);
    // Asserts square shape without rounded-full literal
    expect(wrapper.find('.rounded-full').exists()).toBe(false);
  });

  it('renders a supplied #icon slot inside a square hairline chip', () => {
    const wrapper = mount(EmptyState, {
      props: defaultProps,
      slots: {
        icon: '<span data-testid="slot-icon">Icon</span>',
      },
    });

    expect(wrapper.find('[data-testid="slot-icon"]').exists()).toBe(true);
    expect(wrapper.find('.rounded-full').exists()).toBe(false);
  });

  it('renders primary action and emits action event on click', async () => {
    const wrapper = mount(EmptyState, {
      props: {
        ...defaultProps,
        actionLabel: 'Sukurti naują',
      },
    });

    const button = wrapper.find('button');
    expect(button.exists()).toBe(true);
    expect(button.text()).toContain('Sukurti naują');

    await button.trigger('click');
    expect(wrapper.emitted('action')).toHaveLength(1);
  });

  it('renders documentation link when docsHref is provided', () => {
    const wrapper = mount(EmptyState, {
      props: {
        ...defaultProps,
        docsHref: 'https://vusa.lt/docs',
        docsLabel: 'Skaityti gidą',
      },
    });

    const link = wrapper.find('a[href="https://vusa.lt/docs"]');
    expect(link.exists()).toBe(true);
    expect(link.text()).toContain('Skaityti gidą');
  });

  it('renders no-results mode with search icon and clear button that emits clear', async () => {
    const wrapper = mount(EmptyState, {
      props: {
        mode: 'no-results',
        title: 'Nieko nerasta',
        clearLabel: 'Išvalyti filtrus',
      },
    });

    expect(wrapper.findComponent(TurtleMascot).exists()).toBe(false);
    expect(wrapper.text()).toContain('Nieko nerasta');

    const clearButton = wrapper.find('button');
    expect(clearButton.exists()).toBe(true);
    expect(clearButton.text()).toContain('Išvalyti filtrus');

    await clearButton.trigger('click');
    expect(wrapper.emitted('clear')).toHaveLength(1);
  });

  it('supports custom slots for action, docs, and arbitrary default content', () => {
    const wrapper = mount(EmptyState, {
      props: defaultProps,
      slots: {
        action: '<button data-testid="custom-action">Pasirinktinis</button>',
        docs: '<a data-testid="custom-docs" href="#">Dokumentacija</a>',
        default: '<div data-testid="custom-content">Papildomas tekstas</div>',
      },
    });

    expect(wrapper.find('[data-testid="custom-action"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="custom-docs"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="custom-content"]').exists()).toBe(true);
  });
});
