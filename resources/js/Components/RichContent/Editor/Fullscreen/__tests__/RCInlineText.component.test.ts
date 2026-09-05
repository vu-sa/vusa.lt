import { describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';

import RCInlineText from '../RCInlineText.vue';

describe('RCInlineText', () => {
  it('is zero-cost when not editable: a plain element, no contenteditable, no listeners', () => {
    const wrapper = mount(RCInlineText, { props: { modelValue: 'Hello', as: 'h2' } });
    expect(wrapper.element.tagName).toBe('H2');
    expect(wrapper.text()).toBe('Hello');
    expect(wrapper.attributes('contenteditable')).toBeUndefined();
    expect(wrapper.attributes('data-rc-interactive')).toBeUndefined();
  });

  it('renders as contenteditable="plaintext-only" with data-rc-interactive when editable', () => {
    const wrapper = mount(RCInlineText, { props: { modelValue: 'Hello', editable: true } });
    expect(wrapper.attributes('contenteditable')).toBe('plaintext-only');
    expect(wrapper.attributes('data-rc-interactive')).toBeDefined();
  });

  it('renders the tag passed via `as`', () => {
    const wrapper = mount(RCInlineText, { props: { modelValue: 'Hi', as: 'h1', editable: true } });
    expect(wrapper.element.tagName).toBe('H1');
  });

  it('debounces write-back on input and flushes immediately on blur', async () => {
    vi.useFakeTimers();
    const wrapper = mount(RCInlineText, { props: { modelValue: 'Old', editable: true } });

    wrapper.element.textContent = 'New';
    await wrapper.trigger('input');
    expect(wrapper.emitted('update:modelValue')).toBeUndefined();

    vi.advanceTimersByTime(150);
    expect(wrapper.emitted('update:modelValue')?.at(-1)).toEqual(['New']);

    wrapper.element.textContent = 'Newer';
    await wrapper.trigger('input');
    await wrapper.trigger('blur');
    // blur flushes synchronously — no need to advance timers for this one.
    expect(wrapper.emitted('update:modelValue')?.at(-1)).toEqual(['Newer']);

    vi.useRealTimers();
  });

  it('does not overwrite the DOM text while focused — a mid-focus reset would jump the caret to position 0', async () => {
    const wrapper = mount(RCInlineText, { props: { modelValue: 'Old', editable: true } });

    await wrapper.trigger('focus');
    await wrapper.setProps({ modelValue: 'Externally changed' });
    expect(wrapper.element.textContent).toBe('Old');

    await wrapper.trigger('blur');
    await wrapper.setProps({ modelValue: 'Applied after blur' });
    expect(wrapper.element.textContent).toBe('Applied after blur');
  });

  it('emits focus/blur so the caller can claim/release this field', async () => {
    const wrapper = mount(RCInlineText, { props: { modelValue: 'Hi', editable: true } });
    await wrapper.trigger('focus');
    expect(wrapper.emitted('focus')).toHaveLength(1);
    await wrapper.trigger('blur');
    expect(wrapper.emitted('blur')).toHaveLength(1);
  });

  it('repopulates the DOM text on a second entry into edit mode (view → edit → view → edit)', async () => {
    const wrapper = mount(RCInlineText, { props: { modelValue: 'Sükelt tekstas', editable: true } });
    expect(wrapper.element.textContent).toBe('Sükelt tekstas');

    // Toggle to view (a static, non-editable span) and back to edit — `v-if`/`v-else`
    // swaps in a brand-new contenteditable DOM node the second time, not the original one.
    await wrapper.setProps({ editable: false });
    await wrapper.setProps({ editable: true });

    expect(wrapper.element.textContent).toBe('Sükelt tekstas');
  });

  it('commits focused text when preview mode unmounts the field before its debounce fires', async () => {
    vi.useFakeTimers();
    const wrapper = mount(RCInlineText, { props: { modelValue: 'Old', editable: true } });

    await wrapper.trigger('focus');
    wrapper.element.textContent = 'Kept before preview';
    await wrapper.trigger('input');
    wrapper.unmount();

    expect(wrapper.emitted('update:modelValue')?.at(-1)).toEqual(['Kept before preview']);
    vi.useRealTimers();
  });
});
