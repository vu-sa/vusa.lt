import { usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import { flushPromises, mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import { createMockPage } from '@/tests/helpers/createMockPage';
import { NativeSelect } from '@/Components/ui/native-select';

import InstallationInstructions from '../InstallationInstructions.vue';

const install = vi.hoisted(() => ({ promptInstall: vi.fn() }));
const state = { canInstall: ref(false), isInstalled: ref(false) };
vi.mock('@/Composables/usePWA', () => ({ usePWA: () => ({ ...state, ...install }) }));

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));
vi.mocked(usePage).mockReturnValue(createMockPage({ app: { locale: 'lt' } }));
beforeEach(() => {
  state.canInstall.value = false;
  state.isInstalled.value = false;
  install.promptInstall.mockReset().mockResolvedValue(true);
});

describe('InstallationInstructions', () => {
  it('keeps written instructions available without an install prompt or recording', async () => {
    const wrapper = mount(InstallationInstructions);
    expect(wrapper.findAll('ol li')).toHaveLength(3);
    expect(wrapper.find('video').exists()).toBe(false);
    wrapper.findComponent(NativeSelect).vm.$emit('update:modelValue', 'ios');
    await wrapper.vm.$nextTick();
    expect(wrapper.text()).toContain('learning.path.installation.ios.step_1');
    expect(wrapper.text()).toContain('learning.path.installation.ios.note');
    wrapper.unmount();
  });

  it('only opens the install prompt after a click and reports a failure', async () => {
    state.canInstall.value = true;
    install.promptInstall.mockRejectedValue(new Error('unavailable'));
    const wrapper = mount(InstallationInstructions);
    expect(install.promptInstall).not.toHaveBeenCalled();
    await wrapper.get('button').trigger('click');
    await flushPromises();
    expect(install.promptInstall).toHaveBeenCalledOnce();
    expect(wrapper.get('[role="alert"]').text()).toBe('learning.path.installation.failed');
    wrapper.unmount();
  });

  it('shows installed status without offering another install', () => {
    state.isInstalled.value = true;
    const wrapper = mount(InstallationInstructions);
    expect(wrapper.get('[role="status"]').text()).toBe('learning.path.installation.installed_here');
    expect(wrapper.find('button').exists()).toBe(false);
    wrapper.unmount();
  });
});
