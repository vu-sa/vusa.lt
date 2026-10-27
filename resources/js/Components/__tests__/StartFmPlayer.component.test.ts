import { mount, flushPromises } from '@vue/test-utils';
import { defineComponent, h } from 'vue';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import StartFmPlayer from '../StartFmPlayer.vue';
import StartFM from '../Public/Nav/StartFM.vue';

import { createStartFmProvider, type StartFmContext } from '@/Composables/useStartFm';

let radio: StartFmContext;
let wrapper: ReturnType<typeof mount>;

beforeEach(() => {
  vi.spyOn(HTMLMediaElement.prototype, 'play').mockResolvedValue();
  vi.spyOn(HTMLMediaElement.prototype, 'pause').mockImplementation(() => undefined);
  vi.spyOn(HTMLMediaElement.prototype, 'load').mockImplementation(() => undefined);
  wrapper = mount(defineComponent({
    setup() {
      radio = createStartFmProvider();
      return () => h('div', [h(StartFM, { iconOnly: true }), h(StartFmPlayer)]);
    },
  }));
});

afterEach(() => {
  wrapper.unmount();
  vi.restoreAllMocks();
});

describe('shared START FM player', () => {
  it('starts from the public footer trigger and keeps just one audio element', async () => {
    expect(wrapper.find('[data-slot="start-fm-player"]').exists()).toBe(false);
    await wrapper.get('button[aria-label="radio.listen"]').trigger('click');
    await flushPromises();
    expect(wrapper.findAll('audio')).toHaveLength(1);
    expect(wrapper.get('[role="status"]').text()).toBe('radio.playing');
    await wrapper.get('button[aria-label="radio.pause"]').trigger('click');
    expect(wrapper.get('[role="status"]').text()).toBe('radio.paused');
  });

  it('exposes volume on phones and supports volume, mute and close through controls', async () => {
    radio.open();
    await flushPromises();
    const disclosure = wrapper.get('button[aria-controls]');
    expect(disclosure.attributes('aria-expanded')).toBe('false');
    await disclosure.trigger('click');
    expect(disclosure.attributes('aria-expanded')).toBe('true');
    expect(wrapper.get(`#${disclosure.attributes('aria-controls')}`).classes()).not.toContain('hidden');
    await wrapper.get('input[type="range"]').setValue('0.7');
    expect(radio.volume.value).toBe(0.7);
    expect(wrapper.get('audio').element.volume).toBe(0.7);
    await wrapper.get('button[aria-label="radio.mute"]').trigger('click');
    expect(wrapper.get('button[aria-label="radio.unmute"]').attributes('aria-pressed')).toBe('true');
    await wrapper.get('button[aria-label="radio.close"]').trigger('click');
    expect(wrapper.find('[data-slot="start-fm-player"]').exists()).toBe(false);
    expect(wrapper.get('audio').attributes('src')).toBeUndefined();
  });

  it('explains device volume controls when the browser cannot adjust volume', async () => {
    radio.volumeSupported.value = false;
    radio.open();
    await flushPromises();
    await wrapper.get('button[aria-controls]').trigger('click');
    expect(wrapper.find('input[type="range"]').exists()).toBe(false);
    expect(wrapper.text()).toContain('radio.device_volume');
  });

  it('shows a recoverable error and retries through the player', async () => {
    vi.mocked(HTMLMediaElement.prototype.play).mockRejectedValueOnce(new Error('Offline'));
    radio.open();
    await flushPromises();
    expect(wrapper.get('[role="status"]').text()).toBe('radio.error');
    await wrapper.get('button[aria-label="radio.retry"]').trigger('click');
    await flushPromises();
    expect(wrapper.get('[role="status"]').text()).toBe('radio.playing');
  });
});
