import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount } from '@vue/test-utils';
import DutyUserUpdateWizard from '../DutyUserUpdateWizard.vue';

// Mock Ziggy route
vi.mock('ziggy-js', () => ({
  default: (name: string) => `/${name}`,
  route: (name: string) => `/${name}`,
}));

describe('DutyUserUpdateWizard.vue', () => {
  const defaultProps = {
    institutions: [
      {
        id: 'inst-1',
        name: 'Fakulteto taryba',
        short_name: 'FT',
        tenant: { id: 1, shortname: 'TF' },
        duties: [
          {
            id: 'duty-1',
            name: { lt: 'Pirmininkas', en: 'Chair' },
            email: 'chair@vusa.lt',
            places_to_occupy: 1,
            current_users: [],
          },
        ],
      } as unknown as App.Entities.Institution,
    ],
    assignableTenants: [
      { id: 1, shortname: 'TF', title: 'Teisės fakultetas' } as unknown as App.Entities.Tenant,
    ],
    institutionTypes: [
      { id: 1, title: 'Taryba' } as unknown as App.Entities.Type,
    ],
  };

  it('renders the wizard page header and initial step', () => {
    const wrapper = mount(DutyUserUpdateWizard, {
      props: defaultProps,
      global: {
        stubs: {
          Teleport: true,
          Head: true,
          Link: true,
        },
      },
    });

    expect(wrapper.find('[data-slot="duty-wizard-page"]').exists()).toBe(true);
    expect(wrapper.text()).toContain('Pareigybių atnaujinimas');
    expect(wrapper.text()).toContain('Institucija');
    expect(wrapper.text()).toContain('Fakulteto taryba');
  });

  it('renders both mobile and desktop steppers', () => {
    const wrapper = mount(DutyUserUpdateWizard, {
      props: defaultProps,
      global: {
        stubs: {
          Teleport: true,
          Head: true,
          Link: true,
        },
      },
    });

    // Mobile stepper navigation
    const mobileNav = wrapper.find('nav[aria-label="Žingsniai"]');
    expect(mobileNav.exists()).toBe(true);
    expect(mobileNav.classes()).toContain('lg:hidden');

    // Desktop sidebar
    const desktopSidebar = wrapper.find('aside');
    expect(desktopSidebar.exists()).toBe(true);
    expect(desktopSidebar.classes()).toContain('hidden');
    expect(desktopSidebar.classes()).toContain('lg:block');
  });
});
