import { describe, it, expect, afterEach, vi } from 'vitest';
import { mount } from '@vue/test-utils';

import InflectedDutyName from '@/Components/Duties/InflectedDutyName.vue';
import { commonStubs } from '@/tests/stubs';

// The visual roll itself (opacity/translate easing, the gradient's swept background
// position) is intentionally not asserted — jsdom has no layout/animation engine to verify
// it against, and it can't tell whether a long name actually wraps either. What's covered
// instead is the wiring: which ending is "active" per the shared class binding, that it
// flips when the shared timer fires, and that the last stem word is grouped with the ending
// so wrapping can't split them. See resources/js/CLAUDE.md.
describe('InflectedDutyName.vue', () => {
  let wrapper: ReturnType<typeof mount>;

  afterEach(() => {
    wrapper?.unmount();
    vi.useRealTimers();
  });

  it('renders plain text for a name with no detectable gendered ending', () => {
    wrapper = mount(InflectedDutyName, {
      props: { name: 'Grupė' },
      global: { stubs: commonStubs },
    });

    expect(wrapper.text()).toBe('Grupė');
    expect(wrapper.find('[data-testid="duty-ending-masculine"]').exists()).toBe(false);
  });

  it('renders plain text when locale is not Lithuanian', () => {
    wrapper = mount(InflectedDutyName, {
      props: { name: 'Koordinatorius', locale: 'en' },
      global: { stubs: commonStubs },
    });

    expect(wrapper.text()).toBe('Koordinatorius');
    expect(wrapper.find('[data-testid="duty-ending-masculine"]').exists()).toBe(false);
  });

  it('renders both endings for a gendered name, stem once', () => {
    wrapper = mount(InflectedDutyName, {
      props: { name: 'Koordinatorius', locale: 'lt' },
      global: { stubs: commonStubs },
    });

    expect(wrapper.text()).toContain('Koordinator');
    expect(wrapper.find('[data-testid="duty-ending-masculine"]').text()).toBe('ius');
    expect(wrapper.find('[data-testid="duty-ending-feminine"]').text()).toBe('ė');
  });

  it('detects the pair the same way when the duty is stored in the feminine form', () => {
    wrapper = mount(InflectedDutyName, {
      props: { name: 'Koordinatorė', locale: 'lt' },
      global: { stubs: commonStubs },
    });

    expect(wrapper.find('[data-testid="duty-ending-masculine"]').text()).toBe('ius');
    expect(wrapper.find('[data-testid="duty-ending-feminine"]').text()).toBe('ė');
  });

  it('keeps only the last stem word on one line with the ending, leaving the rest free to wrap', () => {
    wrapper = mount(InflectedDutyName, {
      props: { name: 'Studentų atstovas', locale: 'lt' },
      global: { stubs: commonStubs },
    });

    const group = wrapper.get('[data-testid="duty-ending-group"]');

    expect(group.classes()).toContain('whitespace-nowrap');
    expect(group.text()).toContain('atstov');
    expect(group.find('[data-testid="duty-ending-masculine"]').exists()).toBe(true);
    // The leading words stay outside the nowrap group, so a narrow column breaks there.
    expect(wrapper.text()).toContain('Studentų');
  });

  it('anchors the tooltip on the ending alone, so it points at the letters that change', () => {
    wrapper = mount(InflectedDutyName, {
      props: { name: 'Koordinatorius', locale: 'lt' },
      global: { stubs: commonStubs },
    });

    // No stem text inside the trigger — the tooltip's arrow would otherwise land in the
    // middle of the whole name rather than by the ending.
    expect(wrapper.get('[data-testid="duty-ending-trigger"]').text()).toBe('iusė');
  });

  it('carries a screen-reader-only label naming both forms', () => {
    wrapper = mount(InflectedDutyName, {
      props: { name: 'Koordinatorius', locale: 'lt' },
      global: { stubs: commonStubs },
    });

    // The i18n mock in tests/setup.ts returns the key unchanged rather than interpolating
    // — this asserts the key is wired up, not the rendered Lithuanian/English copy.
    expect(wrapper.find('[data-testid="duty-gender-pair"]').text()).toBe('forms.helpers.duty_name_gender_pair');
  });

  it('starts with the masculine ending active, and flips both instances together on the shared timer', async () => {
    vi.useFakeTimers();

    wrapper = mount(InflectedDutyName, {
      props: { name: 'Koordinatorius', locale: 'lt' },
      global: { stubs: commonStubs },
    });
    const other = mount(InflectedDutyName, {
      props: { name: 'Pirmininkas', locale: 'lt' },
      global: { stubs: commonStubs },
    });

    const masculine = () => wrapper.get('[data-testid="duty-ending-masculine"]').classes();
    const feminine = () => wrapper.get('[data-testid="duty-ending-feminine"]').classes();
    const otherMasculine = () => other.get('[data-testid="duty-ending-masculine"]').classes();

    expect(masculine()).toContain('opacity-100');
    expect(feminine()).toContain('opacity-0');
    // The settled ending sits at the end of its gradient, i.e. the inherited text colour.
    expect(masculine()).toContain('bg-right');
    expect(feminine()).toContain('bg-left');
    expect(otherMasculine()).toContain('opacity-100');

    // Just short of the flip interval, nothing has changed yet.
    await vi.advanceTimersByTimeAsync(4500);
    expect(masculine()).toContain('opacity-100');

    await vi.advanceTimersByTimeAsync(500);

    expect(masculine()).toContain('opacity-0');
    expect(feminine()).toContain('opacity-100');
    // Both endings move the same way (up) on this flip, and the gradient sweeps with them.
    expect(masculine()).toContain('-translate-y-[0.12em]');
    expect(feminine()).toContain('translate-y-0');
    expect(feminine()).toContain('bg-right');
    // Both instances share one timer, so they flip in the same tick.
    expect(otherMasculine()).toContain('opacity-0');

    other.unmount();
  });
});
