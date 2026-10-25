import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { nextTick, ref } from 'vue';

import InstallBanner from '../InstallBanner.vue';

const canInstall = ref(true);
const isPWA = ref(false);
const promptInstall = vi.fn();

vi.mock('@/Composables/usePWA', () => ({ usePWA: () => ({ canInstall, isPWA, promptInstall }) }));

const banner = (wrapper: ReturnType<typeof mount>) => wrapper.find('[data-slot="pwa-install-banner"]');

describe('InstallBanner', () => {
  beforeEach(() => {
    vi.useFakeTimers();
    localStorage.clear();
    // The visit this mount counts is the third.
    localStorage.setItem('pwa_visit_count', '2');
    canInstall.value = true;
    isPWA.value = false;
  });

  afterEach(() => {
    vi.useRealTimers();
  });

  it('offers the install once the session has lasted two minutes', async () => {
    const wrapper = mount(InstallBanner);
    await nextTick();

    expect(banner(wrapper).exists()).toBe(false);

    vi.advanceTimersByTime(120_000);
    await nextTick();

    expect(banner(wrapper).exists()).toBe(true);
  });

  it('stays hidden after the user dismisses it', async () => {
    const wrapper = mount(InstallBanner);
    vi.advanceTimersByTime(120_000);
    await nextTick();

    await banner(wrapper).findAll('button')[0].trigger('click');

    expect(banner(wrapper).exists()).toBe(false);
    expect(localStorage.getItem('pwa_install_prompt')).toBe('dismissed');
  });

  it('is never offered inside the installed app', async () => {
    isPWA.value = true;
    const wrapper = mount(InstallBanner);
    vi.advanceTimersByTime(120_000);
    await nextTick();

    expect(banner(wrapper).exists()).toBe(false);
  });
});
