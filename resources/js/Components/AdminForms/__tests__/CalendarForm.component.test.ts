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

describe('CalendarForm.vue — public URL status link', () => {
  let wrapper: ReturnType<typeof mount>;

  afterEach(() => {
    wrapper?.unmount();
  });

  function createEditWrapper(calendar: Partial<CalendarEventForm> & { id: number }) {
    return mount(CalendarForm, {
      shallow: true,
      props: {
        calendar: calendar as CalendarEventForm,
        categories: [],
        assignableTenants: [],
        submitUrl: '/mano/calendar/1',
        submitMethod: 'patch',
      },
    });
  }

  it('links to the URL built from permalink and year once both exist', () => {
    wrapper = createEditWrapper({
      id: 5,
      title: { lt: 'Renginys', en: 'Event' },
      permalink: { lt: 'renginys', en: '' },
      date: '2026-05-01T10:00:00',
    });

    const vm = wrapper.vm as unknown as { statusLinks: { url: string; label: string }[] };
    expect(vm.statusLinks).toHaveLength(1);
    expect(vm.statusLinks[0].label).toBe('Public');
    expect(vm.statusLinks[0].url).toContain('permalink=renginys');
    expect(vm.statusLinks[0].url).toContain('year=2026');
  });

  it('shows no link while the event has no permalink for the active locale yet', () => {
    wrapper = createEditWrapper({ id: 7, title: { lt: 'Renginys', en: 'Event' }, permalink: { lt: '', en: '' } });

    const vm = wrapper.vm as unknown as { statusLinks: { url: string; label: string }[] };
    expect(vm.statusLinks).toEqual([]);
  });

  it('shows no link before the event has been saved', () => {
    wrapper = createEditWrapper({ id: 0, title: { lt: '', en: '' }, permalink: { lt: '', en: '' } });

    const vm = wrapper.vm as unknown as { statusLinks: { url: string; label: string }[] };
    expect(vm.statusLinks).toEqual([]);
  });
});
