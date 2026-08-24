import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';

import ImageAccessibilityDialog from '../ImageAccessibilityDialog.vue';
import { commonStubs } from '@/tests/stubs';

/**
 * Mounted closed, then opened — the dialog seeds its fields from a watcher on
 * `open`, which is how TiptapEditor.vue keeps it mounted and toggles it.
 */
async function mountDialog(imageData = { src: '/uploads/test.png', alt: 'Studentai', title: '' }) {
  const wrapper = mount(ImageAccessibilityDialog, {
    props: { open: false, imageData },
    global: { stubs: { ...commonStubs } },
  });
  await wrapper.setProps({ open: true });

  return wrapper;
}

describe('ImageAccessibilityDialog', () => {
  it('keeps the alt-text guidance collapsed until it is asked for', async () => {
    const wrapper = await mountDialog();

    expect(wrapper.text()).not.toContain('rich-content.example_photo');

    await wrapper.find('button').trigger('click');

    expect(wrapper.text()).toContain('rich-content.example_photo');
    expect(wrapper.text()).toContain('rich-content.image_alt_help');
  });

  it('shows how much of the 125-character alt budget is used', async () => {
    const wrapper = await mountDialog();

    expect(wrapper.text()).toContain('9/125');

    await wrapper.find('#alt-text').setValue('Studentai bibliotekoje');

    expect(wrapper.text()).toContain('22/125');
  });

  it('caps alt text at the length screen readers can reasonably announce', async () => {
    expect((await mountDialog()).find('#alt-text').attributes('maxlength')).toBe('125');
  });

  // The guidance toggle and the decorative checkbox sit in adjacent rows, so any
  // horizontal padding on the toggle visibly indents it past the checkbox. `p-0` alone
  // does not do it: the `sm` size variant's `has-[>svg]:px-2.5` survives tailwind-merge
  // because modifier-prefixed classes are a separate group.
  it('aligns the guidance toggle with the checkbox below it', async () => {
    const wrapper = await mountDialog();
    const toggle = wrapper.findAll('button').find(button => button.text().includes('rich-content.examples'))!;
    const classes = toggle.classes();

    expect(classes).toContain('has-[>svg]:p-0');
    expect(classes.some(name => /(^|:)px-\d/.test(name))).toBe(false);
  });

  it('submits the trimmed values and closes', async () => {
    const wrapper = await mountDialog();
    await wrapper.find('#alt-text').setValue('  Studentai  ');
    await wrapper.find('#title-text').setValue(' Papildoma ');

    const submit = wrapper.findAll('button').at(-1)!;
    await submit.trigger('click');

    expect(wrapper.emitted('submit')?.[0]).toEqual([{ alt: 'Studentai', title: 'Papildoma' }]);
    expect(wrapper.emitted('update:open')?.[0]).toEqual([false]);
  });
});
