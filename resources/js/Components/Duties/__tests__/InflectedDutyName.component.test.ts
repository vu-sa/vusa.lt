import { describe, it, expect, afterEach, vi } from 'vitest';
import { mount } from '@vue/test-utils';

import InflectedDutyName from '@/Components/Duties/InflectedDutyName.vue';
import { commonStubs } from '@/tests/stubs';
import { documentWithMatch, type SearchMatchDocument } from '@/Shared/Search/matches';

describe('InflectedDutyName.vue', () => {
  let wrapper: ReturnType<typeof mount>;

  it('highlights a duty name in place while preserving both gender endings', () => {
    const doc = documentWithMatch({ document: { name_lt: 'Prezidentas' }, highlights: [{ field: 'name_lt', snippet: '⟦Prezidentas⟧' }] }) as SearchMatchDocument;
    wrapper = mount(InflectedDutyName, { props: { name: 'Prezidentas', matches: doc._searchTitleMatches }, global: { stubs: commonStubs } });
    expect(wrapper.get('[data-testid="duty-ending-group"] > span mark').text()).toBe('Prezident');
    expect(wrapper.get('[data-testid="duty-ending-masculine"] mark').text()).toBe('as');
    expect(wrapper.get('[data-testid="duty-ending-feminine"] mark').text()).toBe('ė');
    expect(wrapper.find('[data-slot="search-match"]').exists()).toBe(false);
    expect(wrapper.get('[data-testid="duty-gender-pair"]').classes()).toContain('sr-only');
  });

  afterEach(() => {
    wrapper?.unmount();
    vi.useRealTimers();
    vi.restoreAllMocks();
    vi.unstubAllGlobals();
  });

  it('uses a solid branded underline and inherits the surrounding text color', () => {
    wrapper = mount(InflectedDutyName, { props: { name: 'Koordinatorius' }, global: { stubs: commonStubs } });

    expect(wrapper.get('[data-testid="duty-ending-underline"]').classes()).toEqual(expect.arrayContaining(['bg-brand', 'self-end']));
    expect(wrapper.html()).not.toContain('gradient');
    expect(wrapper.get('[data-testid="duty-ending-masculine"]').classes()).toContain('motion-reduce:transition-none');
  });

  it('keeps a holder static and only subscribes to the timer when the holder is removed', async () => {
    vi.useFakeTimers();
    wrapper = mount(InflectedDutyName, {
      props: { name: 'Koordinatorius', holder: { name: 'Ona', pronouns: { en: 'she/her' } } },
      global: { stubs: commonStubs },
    });

    expect(wrapper.text()).toBe('Koordinatorė');
    expect(vi.getTimerCount()).toBe(0);
    await wrapper.setProps({ holder: null });
    expect(wrapper.find('[data-testid="duty-ending-group"]').exists()).toBe(true);
    expect(vi.getTimerCount()).toBe(1);
    await wrapper.setProps({ holder: { name: 'Ona' }, useOriginalDutyName: true });
    expect(wrapper.text()).toBe('Koordinatorius');
    expect(vi.getTimerCount()).toBe(0);
  });

  it('keeps reduced motion on the masculine form without a timer', async () => {
    vi.useFakeTimers();
    vi.stubGlobal('matchMedia', vi.fn(() => ({
      matches: true, media: '(prefers-reduced-motion: reduce)', onchange: null,
      addEventListener: vi.fn(), removeEventListener: vi.fn(),
      addListener: vi.fn(), removeListener: vi.fn(), dispatchEvent: vi.fn(),
    })));
    wrapper = mount(InflectedDutyName, { props: { name: 'Koordinatorė' }, global: { stubs: commonStubs } });
    await wrapper.vm.$nextTick();

    expect(wrapper.get('[data-testid="duty-ending-masculine"]').classes()).toContain('opacity-100');
    expect(vi.getTimerCount()).toBe(0);
    await vi.advanceTimersByTimeAsync(10000);
    expect(wrapper.get('[data-testid="duty-ending-masculine"]').classes()).toContain('opacity-100');
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

  it('renders the text after a mid-name head noun outside the animated ending', () => {
    wrapper = mount(InflectedDutyName, {
      props: { name: 'Studentų atstovas VU FF Taryboje', locale: 'lt' },
      global: { stubs: commonStubs },
    });

    const group = wrapper.get('[data-testid="duty-ending-group"]');

    // Only the head noun and its ending are locked together; the locative that follows is
    // plain text and free to wrap onto the next line.
    expect(group.text()).toContain('atstovas');
    expect(group.text()).not.toContain('Taryboje');
    expect(wrapper.find('[data-testid="duty-ending-masculine"]').text()).toBe('as');
    expect(wrapper.find('[data-testid="duty-ending-feminine"]').text()).toBe('ė');
    expect(wrapper.text()).toContain('VU FF Taryboje');
  });

  it('renders plain text when the name already spells both genders out', () => {
    wrapper = mount(InflectedDutyName, {
      props: { name: 'Studentų atstovas(-ė) VU Senate', locale: 'lt' },
      global: { stubs: commonStubs },
    });

    expect(wrapper.text()).toBe('Studentų atstovas(-ė) VU Senate');
    expect(wrapper.find('[data-testid="duty-ending-masculine"]').exists()).toBe(false);
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
    // The initial form is a weighted random roll (see useDutyGenderFlip); pin it masculine.
    vi.spyOn(Math, 'random').mockReturnValue(0.9);

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
    expect(otherMasculine()).toContain('opacity-100');

    // Just short of the flip interval, nothing has changed yet.
    await vi.advanceTimersByTimeAsync(4500);
    expect(masculine()).toContain('opacity-100');

    await vi.advanceTimersByTimeAsync(500);

    expect(masculine()).toContain('opacity-0');
    expect(feminine()).toContain('opacity-100');
    // The ending moves while the rest of the name stays fixed.
    expect(masculine()).toContain('-translate-y-[0.12em]');
    expect(feminine()).toContain('translate-y-0');
    // Both instances share one timer, so they flip in the same tick.
    expect(otherMasculine()).toContain('opacity-0');

    other.unmount();
  });

  it('starts with the feminine ending active when the weighted roll lands feminine', async () => {
    // Math.random() < 0.71 → feminine; a low return value pins it. See useDutyGenderFlip.
    vi.spyOn(Math, 'random').mockReturnValue(0.1);

    wrapper = mount(InflectedDutyName, {
      props: { name: 'Koordinatorius', locale: 'lt' },
      global: { stubs: commonStubs },
    });

    await wrapper.vm.$nextTick();

    expect(wrapper.get('[data-testid="duty-ending-masculine"]').classes()).toContain('opacity-0');
    expect(wrapper.get('[data-testid="duty-ending-feminine"]').classes()).toContain('opacity-100');
  });
});
