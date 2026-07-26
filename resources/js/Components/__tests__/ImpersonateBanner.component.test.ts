import { flushPromises, mount } from '@vue/test-utils';
import { router, usePage } from '@inertiajs/vue3';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { ref } from 'vue';

import ImpersonateBanner from '@/Components/ImpersonateBanner.vue';
import { createMockPage } from '@/tests/helpers/createMockPage';

const { jsonMock, useFetchMock } = vi.hoisted(() => ({
  jsonMock: vi.fn(),
  useFetchMock: vi.fn(),
}));

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));
vi.mock('@vueuse/core', async (importOriginal) => {
  const actual = await importOriginal();

  return {
    ...actual,
    useFetch: useFetchMock,
  };
});

const popoverStubs = {
  Popover: { template: '<div><slot /></div>' },
  PopoverTrigger: { template: '<div><slot /></div>' },
  PopoverContent: { template: '<div><slot /></div>' },
};

describe('ImpersonateBanner', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    vi.stubGlobal('route', vi.fn((name: string) => `/routes/${name}`));
    jsonMock.mockResolvedValue({ data: ref(null) });
    useFetchMock.mockReturnValue({ json: jsonMock });
  });

  afterEach(() => {
    document.body.innerHTML = '';
    vi.unstubAllGlobals();
  });

  it('renders active impersonation as an in-flow amber status', () => {
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
    expect(status.classes()).toContain('bg-amber-50');
    expect(status.classes()).toContain('rounded-xl');
    expect(status.classes()).not.toContain('fixed');
    expect(document.body.querySelector('[data-slot="impersonation-launcher"]')).toBeNull();
  });

  it('stops impersonation and reloads the page', async () => {
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

    expect(useFetchMock).toHaveBeenCalledWith(
      '/routes/api.v1.admin.impersonate.stop',
      expect.objectContaining({ method: 'POST' }),
    );
    expect(jsonMock).toHaveBeenCalled();
    expect(router.reload).toHaveBeenCalledOnce();
  });

  it('keeps the inactive super-admin launcher teleported to the page body', () => {
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
    expect(document.body.querySelector('[data-slot="impersonation-launcher"]')).not.toBeNull();
  });
});
