import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { effectScope, nextTick, ref, type EffectScope } from 'vue';

import { useAccessibilityPreferences } from '../useAccessibilityPreferences';
import { useMotionAwareAutoplay } from '../useMotionAwareAutoplay';

let scope: EffectScope;
const preferences = useAccessibilityPreferences();

beforeEach(() => {
  preferences.reset();
  vi.useFakeTimers();
  scope = effectScope();
});

afterEach(() => {
  scope.stop();
  preferences.reset();
  vi.useRealTimers();
});

function setup() {
  const advance = vi.fn();
  const enabled = ref(true);
  const delay = ref(1000);
  const autoplay = scope.run(() => useMotionAwareAutoplay(advance, enabled, delay))!;
  return { advance, enabled, delay, ...autoplay };
}

describe('motion-aware autoplay', () => {
  it('honours OS-only motion changes without a saved in-app preference', async () => {
    const query = Object.assign(new EventTarget(), {
      matches: true,
      media: '(prefers-reduced-motion: reduce)',
      onchange: null,
      addListener: vi.fn(),
      removeListener: vi.fn(),
    });
    const originalMatchMedia = window.matchMedia;
    window.matchMedia = vi.fn(() => query);
    const autoplay = setup();
    try {
      await vi.advanceTimersByTimeAsync(2000);
      expect(autoplay.advance).not.toHaveBeenCalled();
      Object.assign(query, { matches: false });
      query.dispatchEvent(Object.assign(new Event('change'), { matches: false }));
      await vi.advanceTimersByTimeAsync(1000);
      expect(autoplay.advance).toHaveBeenCalledOnce();
    }
    finally {
      scope.stop();
      window.matchMedia = originalMatchMedia;
    }
  });

  it('cancels pending advancement on reduction and waits a full delay after restoring motion', async () => {
    const autoplay = setup();
    await vi.advanceTimersByTimeAsync(500);
    preferences.reduceMotion.value = true;
    await vi.advanceTimersByTimeAsync(2000);
    expect(autoplay.advance).not.toHaveBeenCalled();

    preferences.reduceMotion.value = false;
    await vi.advanceTimersByTimeAsync(999);
    expect(autoplay.advance).not.toHaveBeenCalled();
    await vi.advanceTimersByTimeAsync(1);
    expect(autoplay.advance).toHaveBeenCalledOnce();
  });

  it('does not bypass a hover pause when motion is restored', async () => {
    const autoplay = setup();
    autoplay.events.mouseenter();
    preferences.reduceMotion.value = true;
    preferences.reduceMotion.value = false;
    await vi.advanceTimersByTimeAsync(3000);
    expect(autoplay.advance).not.toHaveBeenCalled();
    autoplay.events.mouseleave();
    await vi.advanceTimersByTimeAsync(1000);
    expect(autoplay.advance).toHaveBeenCalledOnce();
  });

  it('keeps independent focus and pointer pauses until both interactions end', async () => {
    const autoplay = setup();
    autoplay.events.focusin();
    autoplay.pause();
    autoplay.resume();
    autoplay.restart();
    await vi.advanceTimersByTimeAsync(2000);
    expect(autoplay.advance).not.toHaveBeenCalled();
    const region = document.createElement('div');
    autoplay.events.focusout({ currentTarget: region, relatedTarget: null } as unknown as FocusEvent);
    await vi.advanceTimersByTimeAsync(1000);
    expect(autoplay.advance).toHaveBeenCalledOnce();
  });

  it('does not resume when focus moves between controls in the same region', async () => {
    const autoplay = setup();
    const region = document.createElement('div');
    const button = document.createElement('button');
    region.append(button);
    autoplay.events.focusin();
    autoplay.events.focusout({ currentTarget: region, relatedTarget: button } as unknown as FocusEvent);
    await vi.advanceTimersByTimeAsync(2000);
    expect(autoplay.advance).not.toHaveBeenCalled();
  });

  it('cancels work when disabled or disposed and never duplicates restart timers', async () => {
    const autoplay = setup();
    autoplay.restart();
    autoplay.restart();
    autoplay.enabled.value = false;
    await vi.advanceTimersByTimeAsync(2000);
    expect(autoplay.advance).not.toHaveBeenCalled();
    autoplay.enabled.value = true;
    await vi.advanceTimersByTimeAsync(1000);
    expect(autoplay.advance).toHaveBeenCalledOnce();
    scope.stop();
    autoplay.restart();
    await vi.advanceTimersByTimeAsync(2000);
    expect(autoplay.advance).toHaveBeenCalledOnce();
    await nextTick();
    expect(vi.getTimerCount()).toBe(0);
  });
});
