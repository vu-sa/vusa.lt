import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';

import SpacerEditor from '../SpacerEditor.vue';
import { SPACER_SIZES, DEFAULT_SPACER_SIZE } from '../spacerSizes';

import type { Spacer } from '@/Types/contentParts';

/**
 * `$t` is globally mocked to return the key verbatim (see tests/setup.ts), so the
 * rendered labels show their i18n key — assertions here check the wiring (which key
 * is requested) rather than the translated string.
 *
 * reka-ui's RadioGroupItem renders a `<button role="radio" aria-checked="…">`, not a
 * native `<input type="radio">`, so assertions target that shape.
 */
describe('SpacerEditor', () => {
  it('renders one radio button per registered size, each inside a card with its label key', () => {
    const wrapper = mount(SpacerEditor, {
      props: { modelValue: {}, options: { size: 'md' } },
    });

    const radios = wrapper.findAll('button[role="radio"]');
    expect(radios).toHaveLength(SPACER_SIZES.length);

    // Each radio sits inside a <label> card whose text exposes the size's i18n key.
    SPACER_SIZES.forEach((option) => {
      const card = wrapper.findAll('label').find(l => l.text().includes(`rich-content.${option.labelKey}`));
      expect(card, `expected a card for size ${option.value}`).toBeTruthy();
    });
  });

  it('checks the radio matching options.size and no other', () => {
    const wrapper = mount(SpacerEditor, {
      props: { modelValue: {}, options: { size: 'lg' } },
    });

    const checked = wrapper.findAll('button[role="radio"][data-state="checked"]');
    expect(checked).toHaveLength(1);
    expect(checked[0]!.attributes('value')).toBe('lg');
  });

  it('falls back to the default size for the checked state when options.size is absent', () => {
    const wrapper = mount(SpacerEditor, {
      props: { modelValue: {}, options: {} },
    });

    const checked = wrapper.findAll('button[role="radio"][data-state="checked"]');
    expect(checked).toHaveLength(1);
    expect(checked[0]!.attributes('value')).toBe(DEFAULT_SPACER_SIZE);
  });

  it('emits update:options with the new size when a radio is clicked', async () => {
    const wrapper = mount(SpacerEditor, {
      props: { modelValue: {}, options: { size: 'sm' } },
    });

    const mdRadio = wrapper.findAll('button[role="radio"]').find(
      b => b.attributes('value') === 'md',
    );
    await mdRadio!.trigger('click');

    const emitted = wrapper.emitted('update:options');
    expect(emitted).toBeTruthy();
    const lastEmitted = emitted![emitted!.length - 1]![0];
    expect(lastEmitted).toMatchObject({ size: 'md' });
  });

  it('preserves any pre-existing option keys when writing the new size', async () => {
    // `options.width` could be set by RCWidthPicker; the editor must not clobber it.
    const wrapper = mount(SpacerEditor, {
      props: { modelValue: {}, options: { size: 'sm', width: 'content' } as unknown as Spacer['options'] },
    });

    const lgRadio = wrapper.findAll('button[role="radio"]').find(
      b => b.attributes('value') === 'lg',
    );
    await lgRadio!.trigger('click');

    const emitted = wrapper.emitted('update:options');
    const lastEmitted = emitted![emitted!.length - 1]![0];
    expect(lastEmitted).toMatchObject({ size: 'lg', width: 'content' });
  });
});
