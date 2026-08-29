import { describe, it, expect, afterEach, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { nextTick } from 'vue';

import SpotlightPopover from '@/Components/Onboarding/SpotlightPopover.vue';

describe('Onboarding/SpotlightPopover.vue', () => {
  let wrapper: ReturnType<typeof mount> | null = null;

  afterEach(() => {
    // Teleported content lives outside the wrapper's own element — clean it up
    // regardless of whether the test's assertions passed, or it leaks into the next test.
    wrapper?.unmount();
    wrapper = null;
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

  it('renders the pulsing badge inline by default', () => {
    mountPopover();

    const inlineBadge = wrapper!.find('.absolute.-top-3.-right-3');
    expect(inlineBadge.exists()).toBe(true);
    // No float: nothing should be teleported to the body.
    expect(document.body.querySelector('.fixed.z-50.h-8.w-8')).toBeNull();
  });

  it('teleports the badge to the body when float is set, instead of clipping inline', async () => {
    mountPopover({ float: true });
    await nextTick();

    // The overflow-hidden-prone inline badge must not render when float takes over.
    expect(wrapper!.find('.absolute.-top-3.-right-3').exists()).toBe(false);

    const teleportedBadge = document.body.querySelector('.fixed.z-50.h-8.w-8') as HTMLElement | null;
    expect(teleportedBadge).not.toBeNull();
    // positionBadge() sets explicit left/top from the trigger's rect.
    expect(teleportedBadge?.style.left).toBeTruthy();
    expect(teleportedBadge?.style.top).toBeTruthy();
  });

  it('hides the badge entirely once dismissed, float or not', async () => {
    mountPopover({ float: true, isDismissed: true });
    await nextTick();

    expect(document.body.querySelector('.fixed.z-50.h-8.w-8')).toBeNull();
  });

  it('anchors the panel to the trigger\'s right edge for position="bottom-right", not centered', async () => {
    vi.useFakeTimers();
    mountPopover({ position: 'bottom-right' });

    await wrapper!.find('.relative.inline-block').trigger('mouseenter');
    await vi.advanceTimersByTimeAsync(200);
    await nextTick();

    const panel = wrapper!.find('.absolute.z-50.w-80');
    expect(panel.exists()).toBe(true);
    expect(panel.classes()).toContain('right-0');
    expect(panel.classes()).not.toContain('left-1/2');

    vi.useRealTimers();
  });
});
