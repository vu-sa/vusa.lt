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

describe('ShowAdministration tools section', () => {
  it('renders the duty wizard and duty-periods tools as the first section, with gradient icon tiles', () => {
    vi.mocked(usePage).mockReturnValue(
      createMockPage({ auth: { can: { create: { duty: true } } } }),
    );

    wrapper = createWrapper();

    const wizardLink = wrapper.find('a[href="/mocked-route/duties.updateUsersWizard"]');
    const periodsLink = wrapper.find('a[href="/mocked-route/dutiables.timeline"]');
    expect(wizardLink.exists()).toBe(true);
    expect(periodsLink.exists()).toBe(true);

    // Tools render their icon in a gradient tile; other cards don't.
    expect(wizardLink.find('.bg-gradient-to-br').exists()).toBe(true);

    // "Pareigybių laikotarpiai" moved out of Žmonės into the tools section, not duplicated.
    expect(wrapper.findAll('a[href="/mocked-route/dutiables.timeline"]')).toHaveLength(1);
  });

  it('does not render the category filter dropdown or item-count badges', () => {
    wrapper = createWrapper();

    expect(wrapper.text()).not.toContain('Filtrai');
    expect(wrapper.findComponent({ name: 'DropdownMenu' }).exists()).toBe(false);
  });
});
