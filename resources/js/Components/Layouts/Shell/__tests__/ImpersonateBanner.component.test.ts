import { flushPromises, mount } from '@vue/test-utils';
import { usePage } from '@inertiajs/vue3';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import ImpersonateBanner from '@/Components/Layouts/Shell/ImpersonateBanner.vue';
import { createMockPage } from '@/tests/helpers/createMockPage';
import { reloadDocument } from '@/Utils/reloadDocument';
import { scopedGlobals } from '@/tests/helpers/scopedGlobals';

afterEach(() => vi.useRealTimers());

const { stubGlobal, restoreGlobals } = scopedGlobals();

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));
vi.mock('@/Utils/reloadDocument', () => ({ reloadDocument: vi.fn() }));

const fetchMock = vi.fn();
const respond = (body: unknown, status = 200) => ({ ok: status < 300, status, json: async () => body });

const popoverStubs = {
  Popover: { template: '<div><slot /></div>' },
  PopoverTrigger: { template: '<div><slot /></div>' },
  PopoverContent: { template: '<div><slot /></div>' },
};

describe('ImpersonateBanner', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    stubGlobal('route', vi.fn((name: string) => `/routes/${name}`));
    fetchMock.mockResolvedValue(respond({ success: true }));
    stubGlobal('fetch', fetchMock);
  });

  afterEach(() => {
    document.body.innerHTML = '';
    restoreGlobals();
  });

  it('renders active impersonation as an in-flow status', () => {
    vi.mocked(usePage).mockReturnValue(createMockPage({
      auth: {
        user: {
          name: 'Impersonated User',
          isSuperAdmin: false,
        },
        impersonating: {
          impersonator_name: 'Original Admin',
        },
      },
    }));

    const wrapper = mount(ImpersonateBanner, {
      global: { stubs: popoverStubs },
    });

    const status = wrapper.get('[data-slot="impersonation-status"]');

    expect(status.text()).toContain('Impersonated User');
    expect(status.text()).toContain('Original Admin');
    expect(status.classes()).toContain('bg-status-attention-surface');
    expect(status.classes()).not.toContain('rounded-xl');
    expect(status.classes()).not.toContain('fixed');
    expect(document.body.querySelector('[data-slot="impersonation-launcher"]')).toBeNull();
  });

  it('stops impersonation with a full reload, so the persistent layout drops the impersonated user\'s state', async () => {
    vi.mocked(usePage).mockReturnValue(createMockPage({
      auth: {
        user: {
          name: 'Impersonated User',
          isSuperAdmin: false,
        },
        impersonating: {
          impersonator_name: 'Original Admin',
        },
      },
    }));

    const wrapper = mount(ImpersonateBanner, {
      global: { stubs: popoverStubs },
    });

    await wrapper.get('[data-slot="impersonation-status"] button').trigger('click');
    await flushPromises();

    expect(fetchMock).toHaveBeenCalledWith(
      '/routes/api.v1.admin.impersonate.stop',
      expect.objectContaining({ method: 'POST' }),
    );
    expect(reloadDocument).toHaveBeenCalledOnce();
  });

  it('shows the super-admin launcher in the shell flow', () => {
    vi.mocked(usePage).mockReturnValue(createMockPage({
      app: {
        env: 'local',
      },
      auth: {
        user: {
          name: 'Original Admin',
          isSuperAdmin: true,
        },
        impersonating: null,
      },
    }));

    const wrapper = mount(ImpersonateBanner, {
      global: { stubs: popoverStubs },
    });

    expect(wrapper.find('[data-slot="impersonation-status"]').exists()).toBe(false);
    expect(wrapper.get('[data-slot="impersonation-launcher"]').classes()).not.toContain('fixed');
    expect(wrapper.get('[data-slot="impersonation-launcher"] button').text()).toContain('Apsimesti nariu');
  });

  it('lists each result\'s current duties', async () => {
    vi.useFakeTimers();
    vi.mocked(usePage).mockReturnValue(createMockPage({
      app: { env: 'local' },
      auth: {
        user: { name: 'Original Admin', isSuperAdmin: true },
        impersonating: null,
      },
    }));
    fetchMock.mockResolvedValue(respond({
      success: true,
      data: [{
        id: 'u1',
        name: 'Ona Dabartinė',
        email: 'ona@vusa.lt',
        current_duties: [
          { id: 'd1', name: 'Koordinatorė', institution: 'MIF SA' },
          { id: 'd2', name: 'Narė', institution: null },
        ],
      }],
    }));

    const wrapper = mount(ImpersonateBanner, { global: { stubs: popoverStubs } });
    await wrapper.get('input').setValue('Koord');
    await vi.advanceTimersByTimeAsync(300);
    await flushPromises();

    const duties = wrapper.findAll('[data-slot="impersonation-duty"]').map(duty => duty.text());
    expect(duties).toEqual(['Koordinatorė · MIF SA', 'Narė']);
  });

  it('closes the idle bar until the component remounts', async () => {
    vi.mocked(usePage).mockReturnValue(createMockPage({
      app: { env: 'local' },
      auth: {
        user: { name: 'Original Admin', isSuperAdmin: true },
        impersonating: null,
      },
    }));

    const wrapper = mount(ImpersonateBanner, { global: { stubs: popoverStubs } });
    await wrapper.get('[data-slot="impersonation-bar-close"]').trigger('click');
    expect(wrapper.find('[data-slot="impersonation-launcher"]').exists()).toBe(false);

    wrapper.unmount();
    const refreshed = mount(ImpersonateBanner, { global: { stubs: popoverStubs } });
    expect(refreshed.find('[data-slot="impersonation-launcher"]').exists()).toBe(true);
  });

  it('shows an API failure without reloading', async () => {
    vi.mocked(usePage).mockReturnValue(createMockPage({
      auth: {
        user: { name: 'Impersonated User', isSuperAdmin: true },
        impersonating: { impersonator_name: 'Original Admin' },
      },
    }));
    fetchMock.mockResolvedValue(respond({ success: false, message: 'Could not return' }, 422));

    const wrapper = mount(ImpersonateBanner, { global: { stubs: popoverStubs } });
    await wrapper.get('[data-slot="impersonation-status"] button').trigger('click');
    await flushPromises();

    expect(wrapper.get('[role="alert"]').text()).toBe('Could not return');
    expect(reloadDocument).not.toHaveBeenCalled();
  });
});
