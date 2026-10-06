import { mount } from '@vue/test-utils';
import { afterEach, describe, expect, it, vi } from 'vitest';
import { usePage } from '@inertiajs/vue3';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

import StudentRepInstitutionCard from '../StudentRepInstitutionCard.vue';

import { createMockPage } from '@/tests/helpers/createMockPage';

const stubs = {
  SmartLink: {
    props: ['href'],
    template: '<a :href="href"><slot /></a>',
  },
};

describe('StudentRepInstitutionCard', () => {
  afterEach(() => {
    vi.mocked(usePage).mockReset();
  });

  const createMockInstitution = (usersCount = 2): App.Entities.Institution => {
    const firstNames = ['Vardenis', 'Vardenė', 'Jonas', 'Petras', 'Ona', 'Agnė', 'Tomas', 'Lukas'];
    const lastNames = ['Pavardenis', 'Pavardenytė', 'Jonaitis', 'Petraitis', 'Onaitė', 'Agnaitė', 'Tomaitis', 'Lukaitis'];

    const users: App.Entities.User[] = Array.from({ length: usersCount }, (_, i) => ({
      id: `user-${i + 1}`,
      name: `${firstNames[i % firstNames.length]} ${lastNames[i % lastNames.length]}`,
      profile_photo_path: i === 0 ? '/photos/vardenis.jpg' : null,
    } as unknown as App.Entities.User));

    return {
      id: 'inst-1',
      name: 'VU SA MIF Taryba',
      alias: 'mif-taryba',
      tenant: {
        id: 1,
        shortname: 'VU SA MIF',
        alias: 'mif',
      },
      duties: [
        {
          id: 'duty-1',
          name: 'Narys',
          current_users: users,
        } as unknown as App.Entities.Duty,
      ],
    } as unknown as App.Entities.Institution;
  };

  it('renders institution name and link correctly', () => {
    const institution = createMockInstitution(2);
    const wrapper = mount(StudentRepInstitutionCard, {
      props: { institution },
      global: { stubs },
    });

    expect(wrapper.text()).toContain('VU SA MIF Taryba');
    expect(wrapper.text()).toContain('Plačiau');
    expect(wrapper.find('a').attributes('href')).toContain('/mocked-route/contacts.institution');
  });

  it('renders student representatives with photo or initials', () => {
    const institution = createMockInstitution(2);
    const wrapper = mount(StudentRepInstitutionCard, {
      props: { institution },
      global: { stubs },
    });

    // First user has photo
    const img = wrapper.find('img');
    expect(img.exists()).toBe(true);
    expect(img.attributes('src')).toBe('/photos/vardenis.jpg');
    expect(img.classes()).toContain('grayscale');
    expect(img.classes()).toContain('group-hover:grayscale-0');

    // Second user has initials fallback (Vardenė Pavardenytė => VP)
    expect(wrapper.text()).toContain('VP');
    expect(wrapper.text()).toContain('Vardenis Pavardenis');
    expect(wrapper.text()).toContain('Vardenė Pavardenytė');
  });

  it('limits to 6 representatives and shows +N daugiau counter', () => {
    const institution = createMockInstitution(8);
    const wrapper = mount(StudentRepInstitutionCard, {
      props: { institution },
      global: { stubs },
    });

    // Exactly 6 rep items rendered
    expect(wrapper.text()).toContain('Vardenis Pavardenis');
    expect(wrapper.text()).toContain('Agnė Agnaitė');
    expect(wrapper.text()).not.toContain('Tomas Tomaitis');

    // Remaining counter: 8 - 6 = 2
    expect(wrapper.text()).toContain('+2 daugiau');
  });

  it('renders fallback criteria text when institution has no contacts', () => {
    const institution = {
      id: 'inst-empty',
      name: 'Tuščia institucija',
      duties: [],
    } as unknown as App.Entities.Institution;

    const wrapper = mount(StudentRepInstitutionCard, {
      props: { institution },
      global: { stubs },
    });

    expect(wrapper.text()).toContain('Tuščia institucija');
    expect(wrapper.text()).toContain('Šiuo metu kontaktų nėra');
  });

  it('renders student representatives from contacts array (Typesense format)', () => {
    const institution = {
      id: 'inst-typesense',
      name: 'PKP Taryba',
      tenant_id: 1,
      tenant: { alias: 'vusa' },
      contacts: [
        {
          id: 'user-ts-1',
          name: 'Austėja Austytė',
          duty_id: 'duty-1',
          duty_name: 'Kuratorė',
          profile_photo_path: '/photos/austeja.jpg',
        },
        {
          id: 'user-ts-2',
          name: 'Benas Benaitis',
          duty_id: 'duty-2',
          duty_name: 'Atstovas',
          profile_photo_path: null,
        },
      ],
    } as unknown as App.Entities.Institution;

    const wrapper = mount(StudentRepInstitutionCard, {
      props: { institution },
      global: { stubs },
    });

    expect(wrapper.text()).toContain('PKP Taryba');
    expect(wrapper.text()).toContain('Austėja Austytė');
    expect(wrapper.text()).toContain('Benas Benaitis');
    expect(wrapper.text()).toContain('BB');
    expect(wrapper.find('img').attributes('src')).toBe('/photos/austeja.jpg');
  });

  it.each([
    ['links a PKP tenant\'s institution to www', { id: 7, alias: 'the-coins', type: 'pkp' }, 'subdomain=www'],
    ['links a padalinys institution to its tenant subdomain', { id: 3, alias: 'mif', type: 'padalinys' }, 'subdomain=mif'],
  ])('%s', (_, tenant, expectedSubdomain) => {
    vi.mocked(usePage).mockReturnValue(createMockPage({ tenants: [tenant] }));

    const wrapper = mount(StudentRepInstitutionCard, {
      props: { institution: { id: 'inst-1', name: 'Institucija', tenant, duties: [] } as unknown as App.Entities.Institution },
      global: { stubs },
    });

    expect(wrapper.find('a').attributes('href')).toContain(expectedSubdomain);
  });
});
