import { afterEach, describe, expect, it } from 'vitest';
import { mount, flushPromises } from '@vue/test-utils';

import ReadingSizeControl from '../ReadingSizeControl.vue';

const STORAGE_KEY = 'vusa-reading-scale';

/**
 * The per-article reading-size stepper.
 *
 * What is deliberately NOT covered: whether text actually grows. `.reading-scale p` lives in
 * `app.css` and jsdom resolves no stylesheet, so the only honest assertion is that the component
 * publishes the `--reading-scale` the CSS reads. The visual check is Storybook's.
 */
function render() {
  return mount(ReadingSizeControl, {
    slots: { default: '<p class="body">Tekstas</p>' },
  });
}

/**
 * The variable lives on an inner element, not the root: that element redefines the `--text-*`
 * scale for everything inside it, and the stepper deliberately sits outside so the control does
 * not grow as you press it.
 */
function scaleOf(wrapper: ReturnType<typeof mount>): string | undefined {
  return (wrapper.find('.reading-scale').element as HTMLElement).style.getPropertyValue('--reading-scale');
}

const decrease = (wrapper: ReturnType<typeof mount>) => wrapper.findAll('button')[0]!;
const increase = (wrapper: ReturnType<typeof mount>) => wrapper.findAll('button')[1]!;

describe('ReadingSizeControl', () => {
  afterEach(() => localStorage.clear());

  it('starts at the base scale with decrease disabled', () => {
    const wrapper = render();

    expect(scaleOf(wrapper)).toBe('1');
    expect(decrease(wrapper).attributes('disabled')).toBeDefined();
    expect(increase(wrapper).attributes('disabled')).toBeUndefined();
  });

  it('steps up through the scale and stops at the largest', async () => {
    const wrapper = render();

    await increase(wrapper).trigger('click');
    expect(scaleOf(wrapper)).toBe('1.15');

    await increase(wrapper).trigger('click');
    await increase(wrapper).trigger('click');
    expect(scaleOf(wrapper)).toBe('1.5');
    expect(increase(wrapper).attributes('disabled')).toBeDefined();
  });

  it('steps back down again', async () => {
    const wrapper = render();

    await increase(wrapper).trigger('click');
    await decrease(wrapper).trigger('click');

    expect(scaleOf(wrapper)).toBe('1');
  });

  it('fills one segment per step reached', async () => {
    const wrapper = render();

    const filled = () => wrapper.findAll('.bg-brand-fill').length;

    expect(filled()).toBe(1);
    await increase(wrapper).trigger('click');
    expect(filled()).toBe(2);
  });

  it('persists the chosen step and restores it on the next article', async () => {
    const first = render();
    await increase(first).trigger('click');

    expect(localStorage.getItem(STORAGE_KEY)).toBe('1');

    const second = render();
    await flushPromises();

    expect(scaleOf(second)).toBe('1.15');
  });

  it('ignores a stored value that is out of range rather than rendering an undefined scale', async () => {
    localStorage.setItem(STORAGE_KEY, '99');

    const wrapper = render();
    await flushPromises();

    expect(scaleOf(wrapper)).toBe('1');
  });

  it('renders its children inside the scaled element, with the stepper outside it', () => {
    const wrapper = render();

    expect(wrapper.find('.reading-scale .body').text()).toBe('Tekstas');
    expect(wrapper.findAll('.reading-scale button')).toHaveLength(0);
  });
});
