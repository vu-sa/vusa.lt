import { describe, it, expect, vi, afterEach } from 'vitest';
import { mount, config } from '@vue/test-utils';
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
  PopoverContent: { template: '<div><slot /></div>', emits: ['close-auto-focus'] },
};

function mountSelector() {
  const page = createMockPage({ app: { path: 'lt' } });
  vi.mocked(usePage).mockReturnValue(page);

  const originalPage = config.global.mocks.$page;
  config.global.mocks.$page = page;

  const wrapper = mount(PadalinysSelector, {
    props: { size: 'small' },
    global: { stubs: popoverStubs },
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

  it('prevents reka-ui\'s default close-auto-focus, which would otherwise refocus the trigger and reopen the popover', () => {
    const { wrapper, restore } = mountSelector();

    const content = wrapper.findComponent(popoverStubs.PopoverContent);
    const event = { preventDefault: vi.fn() } as unknown as Event;
    content.vm.$emit('close-auto-focus', event);

    expect(event.preventDefault).toHaveBeenCalled();

    restore();
  });
});
