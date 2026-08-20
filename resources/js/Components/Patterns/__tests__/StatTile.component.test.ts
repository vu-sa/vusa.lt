import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';

import StatTile from '../StatTile.vue';

import { urgencyPalette } from '@/Composables/useDashboardCardStyles';

const mountTile = (props: Record<string, unknown> = {}, slots: Record<string, string> = {}) =>
  mount(StatTile, { props: { value: 12, ...props }, slots });

describe('StatTile', () => {
  it('renders the value', () => {
    expect(mountTile().text()).toContain('12');
  });

  it('renders a denominator only when a total is given', () => {
    expect(mountTile().text()).not.toContain('/');
    expect(mountTile({ total: 30 }).text()).toContain('/30');
  });

  it('renders an optional label and badge', () => {
    const wrapper = mountTile({ label: 'aktyvūs', badge: 'Viskas tvarkoje' });

    expect(wrapper.text()).toContain('aktyvūs');
    expect(wrapper.text()).toContain('Viskas tvarkoje');
  });

  it('omits the badge element when no badge is given', () => {
    expect(mountTile().find('[role="status"]').exists()).toBe(false);
  });

  /**
   * jsdom has no Tailwind pipeline, so this asserts the class *binding* the
   * urgency drives — not the colour that would actually paint in a browser.
   */
  it('binds the urgency palette classes to the value', () => {
    const warning = mountTile({ urgency: 'warning' });

    expect(warning.find('span').classes().join(' ')).toContain(urgencyPalette.text.warning.split(' ')[0]);
  });

  it('lets the badge slot replace the default pill', () => {
    const wrapper = mountTile({ badge: 'default pill' }, { badge: '<em class="custom">custom</em>' });

    expect(wrapper.find('.custom').exists()).toBe(true);
    expect(wrapper.text()).not.toContain('default pill');
  });
});
