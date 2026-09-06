import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';

import RCSection from '../RCSection.vue';

describe('RCSection — public (non-editable)', () => {
  it('renders the header when a title is set', () => {
    const wrapper = mount(RCSection, { props: { title: 'Nariai' } });
    expect(wrapper.text()).toContain('Nariai');
    expect(wrapper.find('h2').exists()).toBe(true);
  });

  it('renders no header at all when there is no title', () => {
    const wrapper = mount(RCSection, { props: {} });
    expect(wrapper.find('h2').exists()).toBe(false);
  });

  it('renders no contenteditable fields when editable is not set', () => {
    const wrapper = mount(RCSection, { props: { title: 'Nariai' } });
    expect(wrapper.find('[contenteditable]').exists()).toBe(false);
  });
});

describe('RCSection — editable (full-screen editor)', () => {
  it('renders nothing when every header field is empty — never invites adding a title to a block that never had one', () => {
    const wrapper = mount(RCSection, { props: { editable: true } });
    expect(wrapper.find('h2').exists()).toBe(false);
    expect(wrapper.find('[contenteditable]').exists()).toBe(false);
  });

  it('renders all three fields as click-to-edit once any one of them is set', () => {
    const wrapper = mount(RCSection, { props: { title: 'Nariai', editable: true } });
    expect(wrapper.findAll('[contenteditable]')).toHaveLength(3); // eyebrow, title, subtitle
  });

  it('renders the title at the configured heading level', () => {
    const wrapper = mount(RCSection, { props: { title: 'Nariai', headingLevel: 3, editable: true } });
    expect(wrapper.find('h3[contenteditable]').exists()).toBe(true);
    expect(wrapper.find('h2').exists()).toBe(false);
  });

  it('emits update:header with just the patched field', async () => {
    const wrapper = mount(RCSection, { props: { title: 'Old', subtitle: 'Sub', editable: true } });
    const title = wrapper.find('h2[contenteditable]');
    title.element.textContent = 'New title';
    await title.trigger('input');
    await new Promise(resolve => setTimeout(resolve, 200)); // RCInlineText's debounce

    const emitted = wrapper.emitted('update:header');
    expect(emitted).toBeTruthy();
    expect(emitted!.at(-1)![0]).toEqual({ title: 'New title' });
  });

  it('emits update:header for the eyebrow field independently', async () => {
    const wrapper = mount(RCSection, { props: { title: 'Nariai', editable: true } });
    const [eyebrow] = wrapper.findAll('[contenteditable]');
    eyebrow!.element.textContent = 'Naujiena';
    await eyebrow!.trigger('input');
    await new Promise(resolve => setTimeout(resolve, 200));

    const emitted = wrapper.emitted('update:header');
    expect(emitted).toBeTruthy();
    expect(emitted!.at(-1)![0]).toEqual({ eyebrow: 'Naujiena' });
  });

  it('still renders slotted content regardless of header visibility', () => {
    const wrapper = mount(RCSection, {
      props: { editable: true },
      slots: { default: '<p class="child">Child block</p>' },
    });
    expect(wrapper.find('.child').exists()).toBe(true);
  });
});
