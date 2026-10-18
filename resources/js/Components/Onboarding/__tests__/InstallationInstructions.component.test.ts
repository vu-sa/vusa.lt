import { ref } from 'vue';
import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';

import InstallationInstructions from '../InstallationInstructions.vue';

import { NativeSelect } from '@/Components/ui/native-select';

vi.mock('@/Composables/usePWA', () => ({ usePWA: () => ({ canInstall: ref(false), isInstalled: ref(false), promptInstall: vi.fn() }) }));

describe('InstallationInstructions', () => {
  it('keeps written instructions available without an install prompt or recording', async () => {
    const wrapper = mount(InstallationInstructions);
    expect(wrapper.findAll('ol li')).toHaveLength(3);
    expect(wrapper.find('video').exists()).toBe(false);
    wrapper.findComponent(NativeSelect).vm.$emit('update:modelValue', 'ios');
    await wrapper.vm.$nextTick();
    expect(wrapper.text()).toContain('onboarding.installation.ios.step_1');
    expect(wrapper.text()).toContain('onboarding.installation.ios.note');
  });

  it('renders an available recording with captions and a transcript', () => {
    const wrapper = mount(InstallationInstructions, { props: { tutorials: { desktop: {
      src: '/tutorial.mp4', captions: '/tutorial.vtt', transcript: '/tutorial-transcript',
    } } } });
    expect(wrapper.find('video').attributes('controls')).toBeDefined();
    expect(wrapper.find('video').attributes('autoplay')).toBeUndefined();
    expect(wrapper.find('track').attributes('src')).toBe('/tutorial.vtt');
    expect(wrapper.find('a[href="/tutorial-transcript"]').exists()).toBe(true);
  });
});
