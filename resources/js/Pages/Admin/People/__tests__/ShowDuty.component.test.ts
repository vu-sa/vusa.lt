import { describe, it, expect, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import ShowDuty from '@/Pages/Admin/People/ShowDuty.vue';
import { commonStubs } from '@/tests/stubs';

vi.mock('@inertiajs/vue3', async () => {
  const actual = await vi.importActual<any>('@inertiajs/vue3');
  return {
    ...actual,
    Head: { name: 'Head', template: '<div style="display:none"><slot /></div>' },
    Link: { name: 'Link', template: '<a><slot /></a>' },
    usePage: () => ({
      props: {
        app: { locale: 'lt', url: 'https://vusa.test' },
        auth: { user: { isSuperAdmin: true } },
      },
    }),
  };
});

describe('ShowDuty.vue', () => {
  const stubs = {
    ...commonStubs,
    Head: true,
    Link: { template: '<a><slot /></a>' },
    RecordPage: {
      props: ['title', 'status', 'facts', 'primaryAction'],
      template: `
        <div>
          <h1>{{ title }}</h1>
          <div data-testid="status">{{ status?.label }}</div>
          <slot name="subtitle" />
          <slot name="alert" />
          <slot name="members" />
          <slot name="about" />
          <slot name="activity" />
        </div>
      `,
    },
    UserAvatar: { template: '<div class="avatar" />' },
    InflectedDutyName: { props: ['name'], template: '<span>{{ typeof name === "string" ? name : name?.lt }}</span>' },
    AssignDutyUserSheet: { template: '<div />' },
    DutiableTimelineDialog: { template: '<div />' },
    RecordActivity: { template: '<div data-testid="record-activity" />' },
  };

  const dummyDuty = {
    id: 'duty-1',
    name: { lt: 'Komunikacijos koordinatorius', en: 'Communication Coordinator' },
    email: 'komunikacija@vusa.lt',
    places_to_occupy: 1,
    description: '<p>Aprašymas</p>',
    institution: {
      id: 'inst-1',
      name: 'VU SA MIF',
      tenant: { id: 11, shortname: 'VU SA MIF' },
    },
    users: [
      {
        id: 'user-1',
        name: 'Vardenis Pavardenis',
        email: 'vardenis@vusa.lt',
        pivot: {
          id: 'dutiable-1',
          duty_id: 'duty-1',
          dutiable_id: 'user-1',
          start_date: '2026-01-01',
          end_date: null,
        },
      },
    ],
    types: [{ id: 1, title: 'Koordinatoriai' }],
    other_duties: [],
  };

  it('renders record page with duty title, occupied status, and member details', () => {
    const wrapper = mount(ShowDuty, {
      props: {
        duty: dummyDuty,
        can: { update: true, managePeople: true },
      },
      global: { stubs },
    });

    expect(wrapper.text()).toContain('Komunikacijos koordinatorius');
    expect(wrapper.find('[data-testid="status"]').text()).toBe('Užimta');
    expect(wrapper.text()).toContain('VU SA MIF');
    expect(wrapper.text()).toContain('Vardenis Pavardenis');
    expect(wrapper.text()).toContain('Dabartiniai nariai');
    expect(wrapper.find('[data-testid="record-activity"]').exists()).toBe(true);
  });

  it('shows vacant status when there are no active holders', () => {
    const vacantDuty = {
      ...dummyDuty,
      users: [],
    };

    const wrapper = mount(ShowDuty, {
      props: {
        duty: vacantDuty,
        can: { update: true, managePeople: true },
      },
      global: { stubs },
    });

    expect(wrapper.find('[data-testid="status"]').text()).toBe('Neužimta');
    expect(wrapper.text()).toContain('Pareigos neužimtos');
  });
});
