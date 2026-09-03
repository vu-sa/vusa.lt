import { describe, it, expect, vi, afterEach } from 'vitest';
import { mount, config } from '@vue/test-utils';
import { defineComponent, h } from 'vue';
import { usePage } from '@inertiajs/vue3';

import PadalinysSelector from '../PadalinysSelector.vue';

import { createMockPage } from '@/tests/helpers/createMockPage';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

// Popover/PopoverTrigger/PopoverContent are stubbed rather than the real reka-ui
// implementation: reka-ui's focus-return-on-close runs through Teleport + a focus-trap
// utility that jsdom cannot reproduce reliably. What matters here is the wiring — that
// PadalinysSelector actually listens for `close-auto-focus` and prevents it — not the
// browser's real focus algorithm, which is exercised in production instead.
const popoverStubs = {
  Popover: { template: '<div><slot /></div>' },
  PopoverTrigger: { template: '<div><slot /></div>' },
  PopoverContent: {
    template: '<div><slot /></div>',
    emits: ['open-auto-focus', 'close-auto-focus', 'pointer-down-outside', 'focus-outside'],
  },
};

function mountSelector(stubs = popoverStubs) {
  const page = createMockPage({ app: { path: 'lt' } });
  vi.mocked(usePage).mockReturnValue(page);

  const originalPage = config.global.mocks.$page;
  config.global.mocks.$page = page;

  const wrapper = mount(PadalinysSelector, {
    props: { size: 'small' },
    global: { stubs },
  });

  return {
    wrapper,
    restore: () => {
      config.global.mocks.$page = originalPage;
    },
  };
}

// The chevron rotates only while `isPopoverOpen` is true — the one DOM-visible signal
// of that internal state without reaching into the script-setup instance.
function isOpen(wrapper: ReturnType<typeof mount>): boolean {
  return wrapper.findAll('svg').some(svg => svg.classes().includes('rotate-180'));
}

describe('PadalinysSelector.vue', () => {
  afterEach(() => {
    vi.useRealTimers();
    localStorage.removeItem('padalinysSelectorViewMode');
  });

  it('opens on mouseenter and closes only after the hover grace period elapses', async () => {
    vi.useFakeTimers();
    const { wrapper, restore } = mountSelector();

    // The stubbed PopoverTrigger's root carries the hover listeners — in the real
    // component reka-ui's `as-child` merges them onto the Button itself.
    const trigger = wrapper.findComponent(popoverStubs.PopoverTrigger);

    await trigger.trigger('mouseenter');
    expect(isOpen(wrapper)).toBe(true);

    await trigger.trigger('mouseleave');
    // Not closed yet — the 150ms grace period lets the cursor travel into the panel.
    await vi.advanceTimersByTimeAsync(100);
    expect(isOpen(wrapper)).toBe(true);

    await vi.advanceTimersByTimeAsync(100);
    expect(isOpen(wrapper)).toBe(false);

    restore();
  });

  it('stays open when multiple close signals are followed by re-entry', async () => {
    vi.useFakeTimers();
    const { wrapper, restore } = mountSelector();
    const trigger = wrapper.findComponent(popoverStubs.PopoverTrigger);
    const content = wrapper.findComponent(popoverStubs.PopoverContent);

    await trigger.trigger('mouseenter');
    await content.trigger('focusout');
    await content.trigger('mouseleave');
    await content.trigger('mouseenter');
    await vi.advanceTimersByTimeAsync(200);

    expect(isOpen(wrapper)).toBe(true);

    restore();
  });

  it('prevents reka-ui\'s default open-auto-focus, which would otherwise paint a focus ring around the List button on hover-open', () => {
    const { wrapper, restore } = mountSelector();

    const content = wrapper.findComponent(popoverStubs.PopoverContent);
    const event = { preventDefault: vi.fn() } as unknown as Event;
    content.vm.$emit('open-auto-focus', event);

    expect(event.preventDefault).toHaveBeenCalled();

    restore();
  });

  it('prevents reka-ui\'s default close-auto-focus, which would otherwise refocus the trigger and reopen the popover', () => {
    const { wrapper, restore } = mountSelector();

    const content = wrapper.findComponent(popoverStubs.PopoverContent);
    const event = { preventDefault: vi.fn() } as unknown as Event;
    content.vm.$emit('close-auto-focus', event);

    expect(event.preventDefault).toHaveBeenCalled();

    restore();
  });

  it('does not reinitialize the map when controls receive focus while the popover is open', async () => {
    vi.useFakeTimers();
    localStorage.setItem('padalinysSelectorViewMode', 'map');
    const initializeOrUpdateMap = vi.fn();
    const forceUpdateMap = vi.fn();
    const PadalinysMap = defineComponent({
      setup(_, { expose }) {
        expose({ initializeOrUpdateMap, forceUpdateMap });

        return () => h('div', { class: 'padalinys-map-stub' });
      },
    });
    const { wrapper, restore } = mountSelector({ ...popoverStubs, PadalinysMap });
    const trigger = wrapper.findComponent(popoverStubs.PopoverTrigger);
    const content = wrapper.findComponent(popoverStubs.PopoverContent);

    await trigger.trigger('mouseenter');
    await vi.advanceTimersByTimeAsync(100);
    await content.trigger('focusin');
    await content.trigger('mouseenter');
    await vi.advanceTimersByTimeAsync(100);

    // jsdom cannot run Leaflet's rendered zoom animation; repeated initialization is the reset trigger.
    expect(initializeOrUpdateMap).not.toHaveBeenCalled();
    expect(forceUpdateMap).toHaveBeenCalledTimes(1);

    restore();
  });
});
