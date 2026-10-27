import { mount, flushPromises } from '@vue/test-utils';
import { defineComponent, h } from 'vue';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import { createStartFmProvider, useStartFm, type StartFmContext } from '../useStartFm';

let context: StartFmContext;
let audio: HTMLAudioElement;
let wrapper: ReturnType<typeof mount>;

beforeEach(() => {
  vi.spyOn(HTMLMediaElement.prototype, 'play').mockResolvedValue();
  vi.spyOn(HTMLMediaElement.prototype, 'pause').mockImplementation(() => undefined);
  vi.spyOn(HTMLMediaElement.prototype, 'load').mockImplementation(() => undefined);
  audio = document.createElement('audio');
  wrapper = mount(defineComponent({
    setup() {
      context = createStartFmProvider();
      return () => h('div');
    },
  }));
  context.attachAudio(audio);
});

afterEach(() => {
  wrapper.unmount();
  vi.useRealTimers();
  vi.restoreAllMocks();
});

describe('START FM playback', () => {
  it('does not load a stream until requested, starts at 35%, and shares one connection', async () => {
    expect(audio.hasAttribute('src')).toBe(false);
    expect(audio.volume).toBe(0.35);
    context.open();
    context.open();
    expect(context.status.value).toBe('connecting');
    await flushPromises();
    expect(audio.play).toHaveBeenCalledOnce();
    expect(context.status.value).toBe('playing');
  });

  it('pauses without closing and reloads the live stream on resume', async () => {
    context.open();
    await flushPromises();
    context.toggle();
    expect(context.status.value).toBe('paused');
    expect(context.isOpen.value).toBe(true);
    audio.src = 'https://example.com/stale-buffer';
    context.toggle();
    await flushPromises();
    expect(audio.src).toBe('https://eteris.startfm.lt/startfm.mp3');
    expect(audio.play).toHaveBeenCalledTimes(2);
  });

  it('releases the stream on close and preserves volume on reopening', async () => {
    context.setVolume(0.6);
    context.open();
    await flushPromises();
    context.close();
    expect(context.isOpen.value).toBe(false);
    expect(context.status.value).toBe('idle');
    expect(audio.hasAttribute('src')).toBe(false);
    expect(audio.load).toHaveBeenCalledOnce();
    context.open();
    expect(audio.volume).toBe(0.6);
  });

  it('ignores a play promise resolving after close', async () => {
    let complete: () => void = () => undefined;
    vi.mocked(audio.play).mockImplementation(() => new Promise<void>((resolve) => {
      complete = resolve;
    }));
    context.open();
    context.close();
    complete();
    await flushPromises();
    context.onPlaying();
    expect(context.isOpen.value).toBe(false);
    expect(context.status.value).toBe('idle');
    expect(audio.hasAttribute('src')).toBe(false);
  });

  it('keeps a pending connection paused if it completes late', async () => {
    let complete: () => void = () => undefined;
    vi.mocked(audio.play).mockImplementation(() => new Promise<void>((resolve) => {
      complete = resolve;
    }));
    context.open();
    context.toggle();
    complete();
    await flushPromises();
    context.onPlaying();
    expect(context.status.value).toBe('paused');
  });

  it('offers retry when a stream never finishes connecting', async () => {
    vi.useFakeTimers();
    vi.mocked(audio.play).mockImplementation(() => new Promise<void>(() => undefined));
    context.open();
    await vi.advanceTimersByTimeAsync(15_000);
    expect(context.status.value).toBe('error');
    expect(context.isOpen.value).toBe(true);
    expect(audio.hasAttribute('src')).toBe(false);
    vi.mocked(audio.play).mockResolvedValue();
    context.toggle();
    await vi.advanceTimersByTimeAsync(0);
    expect(context.status.value).toBe('playing');
  });

  it('handles rejected playback and allows a retry', async () => {
    vi.mocked(audio.play).mockRejectedValueOnce(new DOMException('Blocked', 'NotAllowedError'));
    context.open();
    await flushPromises();
    expect(context.status.value).toBe('error');
    context.toggle();
    await flushPromises();
    expect(context.status.value).toBe('playing');
  });

  it('tries the alternate format and reports failure after both streams fail', async () => {
    context.open();
    await flushPromises();
    context.onError();
    expect(audio.src).toBe('https://eteris.startfm.lt/startfm.m4a');
    await flushPromises();
    context.onError();
    expect(context.status.value).toBe('error');
  });

  it('tries the alternate format when play rejects an unsupported source', async () => {
    vi.mocked(audio.play).mockRejectedValueOnce(new DOMException('Format', 'NotSupportedError'));
    context.open();
    await flushPromises();
    expect(audio.src).toBe('https://eteris.startfm.lt/startfm.m4a');
    expect(context.status.value).toBe('playing');
  });

  it('reflects buffering and playback events but ignores errors after closing', async () => {
    context.open();
    await flushPromises();
    context.onWaiting();
    expect(context.status.value).toBe('connecting');
    context.onPlaying();
    expect(context.status.value).toBe('playing');
    context.onPause();
    expect(context.status.value).toBe('paused');
    context.close();
    context.onError();
    expect(context.status.value).toBe('idle');
  });

  it('detects browsers that require device volume controls', () => {
    context.attachAudio(null);
    Object.defineProperty(audio, 'volume', { get: () => 1, set: () => undefined });
    context.attachAudio(audio);
    expect(context.volumeSupported.value).toBe(false);
  });

  it('clamps volume and restores sound when adjusting a muted player', () => {
    context.toggleMute();
    expect(audio.muted).toBe(true);
    context.setVolume(2);
    expect(audio.volume).toBe(1);
    expect(audio.muted).toBe(false);
    context.setVolume(-1);
    expect(audio.volume).toBe(0);
    context.setVolume(Number.NaN);
    expect(audio.volume).toBe(0);
  });

  it('releases playback when the persistent layout is destroyed', async () => {
    context.open();
    await flushPromises();
    wrapper.unmount();
    expect(audio.hasAttribute('src')).toBe(false);
    expect(context.status.value).toBe('idle');
  });

  it('shares its context with descendants while keeping separate layouts isolated', () => {
    let child: StartFmContext | undefined;
    const Child = defineComponent({
      setup() {
        child = useStartFm();
        return () => h('span');
      },
    });
    let parent: StartFmContext | undefined;
    const other = mount(defineComponent({
      setup() {
        parent = createStartFmProvider();
        return () => h(Child);
      },
    }));
    expect(child).toBe(parent);
    expect(parent).not.toBe(context);
    parent?.setVolume(0.8);
    expect(context.volume.value).toBe(0.35);
    other.unmount();
  });
});
