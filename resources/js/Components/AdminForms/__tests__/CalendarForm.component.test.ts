import { afterEach, describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';

import CalendarForm from '@/Components/AdminForms/CalendarForm.vue';

// Use the real Inertia useForm because CalendarForm relies on its Precognition helpers.
vi.mock('@inertiajs/vue3', async () => {
  const actual = await vi.importActual('@inertiajs/vue3');

  return {
    ...actual,
    usePage: () => ({ props: { app: { locale: 'lt' } } }),
  };
});

describe('CalendarForm.vue — create tenant default', () => {
  let wrapper: ReturnType<typeof mount>;

  const calendar: CalendarEventForm = {
    title: { lt: '', en: '' },
    date: null,
    end_date: null,
    description: { lt: '', en: '' },
    location: { lt: '', en: '' },
    organizer: { lt: '', en: '' },
    cto_url: { lt: '', en: '' },
    tenant_id: null,
    category_id: null,
    facebook_url: '',
    is_draft: false,
    is_all_day: false,
    is_international: false,
    is_remote: false,
    hero_style: 'card',
  };

  function createWrapper(assignableTenants: App.Entities.Tenant[], rememberKey: string) {
    return mount(CalendarForm, {
      shallow: true,
      props: {
        calendar,
        categories: [],
        assignableTenants,
        rememberKey,
        submitUrl: '/mano/calendar',
        submitMethod: 'post',
      },
    });
  }

  afterEach(() => {
    wrapper?.unmount();
  });

  it('prefers VU SA when it is available', () => {
    wrapper = createWrapper([
      { id: 2, shortname: 'VU SA FF', type: 'padalinys' },
      { id: 16, shortname: 'VU SA', type: 'pagrindinis' },
    ] as App.Entities.Tenant[], 'CreateCalendarMainTenant');

    const vm = wrapper.vm as unknown as { form: { tenant_id: number | null } };
    expect(vm.form.tenant_id).toBe(16);
  });

  it('uses the first available tenant when VU SA is unavailable', () => {
    wrapper = createWrapper([
      { id: 2, shortname: 'VU SA FF', type: 'padalinys' },
      { id: 11, shortname: 'VU SA MIF', type: 'padalinys' },
    ] as App.Entities.Tenant[], 'CreateCalendarFallbackTenant');

    const vm = wrapper.vm as unknown as { form: { tenant_id: number | null } };
    expect(vm.form.tenant_id).toBe(2);
  });
});
