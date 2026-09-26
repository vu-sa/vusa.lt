import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import PaletteField from '../PaletteField.vue';

const toggle = vi.hoisted(() => vi.fn());

vi.mock('@/Composables/useCommandPalette', () => ({
  useCommandPalette: () => ({ toggle }),
}));

beforeEach(() => toggle.mockClear());

describe('PaletteField', () => {
  it('uses an icon trigger below lg and opens the same palette from both triggers', async () => {
    const buttons = mount(PaletteField).findAll('button');

    expect(buttons).toHaveLength(2);
    expect(buttons[0].classes()).toContain('lg:flex');
    expect(buttons[1].classes()).toContain('lg:hidden');
    expect(buttons[1].attributes('aria-label')).toBeTruthy();

    await buttons[0].trigger('click');
    await buttons[1].trigger('click');
    expect(toggle).toHaveBeenCalledTimes(2);
  });
});
