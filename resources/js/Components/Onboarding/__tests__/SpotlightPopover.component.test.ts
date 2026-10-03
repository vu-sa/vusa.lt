import { describe, it, expect, afterEach, vi } from 'vitest';
import { flushPromises, mount } from '@vue/test-utils';
import { nextTick } from 'vue';

import SpotlightPopover from '@/Components/Onboarding/SpotlightPopover.vue';

describe('Onboarding/SpotlightPopover.vue', () => {
  let wrapper: ReturnType<typeof mount> | null = null;

  afterEach(() => {
    // The panel is portaled to the body, outside the wrapper — unmount so it cannot leak.
    wrapper?.unmount();
    wrapper = null;
    vi.useRealTimers();
  });

  function mountPopover(props: Record<string, unknown> = {}) {
    wrapper = mount(SpotlightPopover, {
      props: {
        title: 'New feature',
        description: 'Try it out',
        ...props,
      },
      slots: { default: '<button>Trigger</button>' },
      attachTo: document.body,
    });

    return wrapper;
  }

  const panel = () => document.body.querySelector('[data-slot="popover-content"]') as HTMLElement | null;

  async function hover(type: 'pointerenter' | 'pointerleave', pointerType = 'mouse') {
    const event = new Event(type);
    Object.assign(event, { pointerType });
    wrapper!.find('[data-slot="spotlight-popover"]').element.dispatchEvent(event);
    await nextTick();
  }

  it('frames the trigger with a notch until dismissed', async () => {
    mountPopover();
    expect(wrapper!.find('[data-slot="spotlight-frame"]').exists()).toBe(true);
    expect(wrapper!.find('[data-slot="spotlight-notch"]').exists()).toBe(true);

    await wrapper!.setProps({ isDismissed: true });
    expect(wrapper!.find('[data-slot="spotlight-frame"]').exists()).toBe(false);
    expect(wrapper!.find('[data-slot="spotlight-notch"]').exists()).toBe(false);
  });

  it('opens immediately on mouse hover and closes after leaving', async () => {
    vi.useFakeTimers();
    mountPopover();

    await hover('pointerenter');
    expect(panel()?.textContent).toContain('New feature');
    expect(panel()?.textContent).toContain('Try it out');

    await hover('pointerleave');
    await vi.advanceTimersByTimeAsync(500);
    expect(panel()).toBeNull();
  });

  it('ignores touch pointerenter so a tap on the trigger keeps its own action', async () => {
    vi.useFakeTimers();
    mountPopover();

    await hover('pointerenter', 'touch');
    await vi.advanceTimersByTimeAsync(200);
    expect(panel()).toBeNull();
  });

  it('opens from the notch button, the touch and keyboard path', async () => {
    mountPopover();

    await wrapper!.find('[data-slot="spotlight-notch"]').trigger('click');
    await nextTick();

    expect(panel()?.textContent).toContain('New feature');
  });

  it('forwards the preferred side and alignment to the positioned panel', async () => {
    mountPopover({ side: 'top', align: 'end' });

    await wrapper!.find('[data-slot="spotlight-notch"]').trigger('click');
    await nextTick();

    expect(panel()?.getAttribute('data-side')).toBe('top');
    expect(panel()?.getAttribute('data-align')).toBe('end');
  });

  it('emits dismiss from the panel button, but not when closed with Escape', async () => {
    mountPopover();

    await wrapper!.find('[data-slot="spotlight-notch"]').trigger('click');
    await nextTick();

    panel()!.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape', bubbles: true }));
    await flushPromises();
    expect(panel()).toBeNull();
    expect(wrapper!.emitted('dismiss')).toBeUndefined();

    await wrapper!.find('[data-slot="spotlight-notch"]').trigger('click');
    await nextTick();
    panel()!.querySelector('button')!.click();
    await nextTick();

    expect(wrapper!.emitted('dismiss')).toHaveLength(1);
  });

  describe('on a device without hover', () => {
    let observerCallback: ((entries: Partial<IntersectionObserverEntry>[]) => void) | null = null;

    function stubTouchDevice() {
      vi.stubGlobal('matchMedia', (query: string) => ({
        matches: false,
        media: query,
        addEventListener: () => {},
        removeEventListener: () => {},
      }));
      vi.stubGlobal('IntersectionObserver', class {
        constructor(callback: typeof observerCallback) {
          observerCallback = callback;
        }

        observe() {}
        disconnect() {}
      });
    }

    async function scroll(isIntersecting: boolean) {
      observerCallback!([{ isIntersecting, intersectionRatio: isIntersecting ? 1 : 0, intersectionRect: { height: 40 } as DOMRectReadOnly }]);
      await nextTick();
    }

    afterEach(() => {
      vi.unstubAllGlobals();
      observerCallback = null;
    });

    it('opens once the trigger rests in view and steps aside when scrolled past', async () => {
      vi.useFakeTimers();
      stubTouchDevice();
      mountPopover();
      await nextTick();

      await scroll(true);
      expect(panel()).toBeNull();

      await vi.advanceTimersByTimeAsync(700);
      expect(panel()?.textContent).toContain('New feature');

      await scroll(false);
      await flushPromises();
      expect(panel()).toBeNull();
      expect(wrapper!.emitted('dismiss')).toBeUndefined();

      await scroll(true);
      await vi.advanceTimersByTimeAsync(700);
      expect(panel()).toBeNull();
    });

    it('does not open when the trigger is only scrolled through', async () => {
      vi.useFakeTimers();
      stubTouchDevice();
      mountPopover();
      await nextTick();

      await scroll(true);
      await vi.advanceTimersByTimeAsync(300);
      await scroll(false);
      await vi.advanceTimersByTimeAsync(700);

      expect(panel()).toBeNull();
    });

    it('stays closed once dismissed', async () => {
      vi.useFakeTimers();
      stubTouchDevice();
      mountPopover({ isDismissed: true });
      await nextTick();

      await scroll(true);
      await vi.advanceTimersByTimeAsync(700);

      expect(panel()).toBeNull();
    });
  });
});
