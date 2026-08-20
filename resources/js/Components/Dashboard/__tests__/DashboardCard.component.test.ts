import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';

import DashboardCard from '../DashboardCard.vue';

import { urgencyPalette } from '@/Composables/useDashboardCardStyles';

const mountCard = (props: Record<string, unknown> = {}, slots: Record<string, string> = {}) =>
  mount(DashboardCard, {
    props: { title: 'Užduotys', ...props },
    slots: { default: '<p class="body">body</p>', ...slots },
  });

describe('DashboardCard', () => {
  it('renders the title and default slot', () => {
    const wrapper = mountCard();

    expect(wrapper.text()).toContain('Užduotys');
    expect(wrapper.find('.body').exists()).toBe(true);
  });

  it('labels the region with the title unless an explicit label is given', () => {
    expect(mountCard().find('[role="region"]').attributes('aria-label')).toBe('Užduotys');
    expect(mountCard({ ariaLabel: 'Tavo užduotys' }).find('[role="region"]').attributes('aria-label'))
      .toBe('Tavo užduotys');
  });

  it('renders the header-action and footer slots only when provided', () => {
    expect(mountCard().find('.action').exists()).toBe(false);

    const wrapper = mountCard({}, {
      'header-action': '<span class="action">3</span>',
      'footer': '<span class="foot">note</span>',
    });

    expect(wrapper.find('.action').exists()).toBe(true);
    expect(wrapper.find('.foot').exists()).toBe(true);
  });

  it('renders an icon from either the prop or the slot', () => {
    const fromProp = mountCard({ icon: { template: '<i class="prop-icon" />' } });
    expect(fromProp.find('.prop-icon').exists()).toBe(true);

    const fromSlot = mountCard({ icon: { template: '<i class="prop-icon" />' } }, {
      icon: '<i class="slot-icon" />',
    });
    expect(fromSlot.find('.slot-icon').exists()).toBe(true);
    expect(fromSlot.find('.prop-icon').exists()).toBe(false);
  });

  /**
   * Class bindings only — jsdom cannot resolve Tailwind, so the actual painted
   * colour of the corner accent is out of reach here.
   */
  it('tints the corner accent from urgency, and lets accentClass override it', () => {
    const accent = (w: ReturnType<typeof mount>) => w.find('[aria-hidden="true"]').classes().join(' ');

    expect(accent(mountCard({ urgency: 'success' })))
      .toContain(urgencyPalette.statusIndicator.success.split(' ')[0]);

    const overridden = accent(mountCard({ urgency: 'success', accentClass: 'bg-rose-300/40' }));
    expect(overridden).toContain('bg-rose-300/40');
    expect(overridden).not.toContain(urgencyPalette.statusIndicator.success.split(' ')[0]);
    // The wedge geometry survives the tint override.
    expect(overridden).toContain('rotate-45');
  });
});
