import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { usePage } from '@inertiajs/vue3';

import ShowAdministration from '@/Pages/Admin/ShowAdministration.vue';
import { createMockPage } from '@/tests/helpers/createMockPage';

let wrapper: ReturnType<typeof mount>;

function createWrapper() {
  return mount(ShowAdministration, {
    global: {
      stubs: {
        AdminContentPage: { template: '<div><slot /></div>' },
        DropdownMenu: { template: '<div><slot /></div>' },
        DropdownMenuTrigger: { template: '<div><slot /></div>' },
        DropdownMenuContent: { template: '<div v-if="false"><slot /></div>' },
        DropdownMenuLabel: { template: '<div><slot /></div>' },
        DropdownMenuSeparator: { template: '<div />' },
        DropdownMenuCheckboxItem: { template: '<div><slot /></div>' },
      },
    },
  });
}

beforeEach(() => {
  vi.mocked(usePage).mockReturnValue(createMockPage());
});

afterEach(() => {
  wrapper.unmount();
  vi.mocked(usePage).mockReset();
});

describe('ShowAdministration quick actions', () => {
  it('does not render the "Naujausi įrankiai" section or "Nauja" badge', () => {
    wrapper = createWrapper();

    expect(wrapper.text()).not.toContain('Naujausi įrankiai');
    expect(wrapper.text()).not.toContain('Nauja');
    expect(wrapper.find('a[href="/mocked-route/problems.index"]').exists()).toBe(false);
  });

  it('still lists problems under Atstovavimas for users with the problem permission', () => {
    vi.mocked(usePage).mockReturnValue(
      createMockPage({ auth: { can: { create: { problem: true } } } }),
    );

    wrapper = createWrapper();

    const problemLink = wrapper.find('a[href="/mocked-route/problems.index"]');
    expect(problemLink.exists()).toBe(true);
    expect(wrapper.text()).toContain('Atstovavimas');

    // The featured quick-action card must not come back with the permission.
    expect(wrapper.text()).not.toContain('Naujausi įrankiai');
  });
});
