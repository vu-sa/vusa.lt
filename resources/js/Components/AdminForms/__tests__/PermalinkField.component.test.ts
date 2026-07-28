import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { mount } from '@vue/test-utils';

import PermalinkField from '@/Components/AdminForms/PermalinkField.vue';
import { commonStubs } from '@/tests/stubs';

describe('PermalinkField.vue', () => {
  let wrapper: ReturnType<typeof mount>;

  const createWrapper = (props = {}) => {
    return mount(PermalinkField, {
      props: {
        permalink: 'test-permalink',
        baseUrl: 'www.vusa.test',
        ...props,
      },
      global: {
        stubs: {
          ...commonStubs,
          IFluentLink24Regular: { template: '<span class="icon-link" />' },
          IFluentCopy24Regular: { template: '<span class="icon-copy" />' },
          IFluentCheckmark24Regular: { template: '<span class="icon-check" />' },
          IFluentOpen24Regular: { template: '<span class="icon-open" />' },
          IFluentInfo16Regular: { template: '<span class="icon-info" />' },
          IFluentWarning24Regular: { template: '<span class="icon-warning" />' },
        },
      },
    });
  };

  beforeEach(() => {
    vi.clearAllMocks();
  });

  afterEach(() => {
    wrapper?.unmount();
  });

  it('renders the permalink in the input', () => {
    wrapper = createWrapper();

    expect(wrapper.find('input').element.value).toBe('test-permalink');
  });

  it('disables the input when disabled prop is true', () => {
    wrapper = createWrapper({ disabled: true });

    expect(wrapper.find('input').attributes('disabled')).toBeDefined();
  });

  it('enables the input by default', () => {
    wrapper = createWrapper();

    expect(wrapper.find('input').attributes('disabled')).toBeUndefined();
  });

  it('shows the explanation only when disabled', () => {
    wrapper = createWrapper({ disabled: true, explanation: 'Cannot change' });

    expect(wrapper.text()).toContain('Cannot change');

    wrapper.unmount();
    wrapper = createWrapper({ explanation: 'Cannot change' });

    expect(wrapper.text()).not.toContain('Cannot change');
  });

  it('renders a warning alert when the warning prop is provided', () => {
    wrapper = createWrapper({ warning: 'Old link will break!' });

    expect(wrapper.text()).toContain('Dėmesio');
    expect(wrapper.text()).toContain('Old link will break!');
    expect(wrapper.find('.icon-warning').exists()).toBe(true);
  });

  it('does not render a warning alert when no warning prop is provided', () => {
    wrapper = createWrapper();

    expect(wrapper.text()).not.toContain('Dėmesio');
  });

  it('emits update:permalink when the input value changes', async () => {
    wrapper = createWrapper();

    await wrapper.find('input').setValue('new-permalink');

    expect(wrapper.emitted('update:permalink')).toHaveLength(1);
    expect(wrapper.emitted('update:permalink')![0]).toEqual(['new-permalink']);
  });
});
