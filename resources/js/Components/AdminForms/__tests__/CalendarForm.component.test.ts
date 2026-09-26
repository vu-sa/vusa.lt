import { afterEach, describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';

import CalendarForm from '@/Components/AdminForms/CalendarForm.vue';

// Use the real Inertia useForm because CalendarForm relies on its Precognition helpers.
vi.mock('@inertiajs/vue3', async () => {
  const actual = await vi.importActual('@inertiajs/vue3');

  return {
    ...actual,
    usePage: () => ({ props: { app: { locale: 'lt', url: 'https://www.vusa.test' } } }),
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
    event_type_id: null,
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
        eventTypes: [],
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

  it('treats the event type as optional and allows clearing it', async () => {
    wrapper = createWrapper([
      { id: 16, shortname: 'VU SA', type: 'pagrindinis' },
    ] as App.Entities.Tenant[], 'CreateCalendarOptionalEventType');

    const vm = wrapper.vm as unknown as {
      eventTypeIdString: string;
      form: { event_type_id: number | null; title: { lt: string } };
    };

    vm.form.title.lt = 'Renginys';
    vm.form.event_type_id = 5;
    await wrapper.vm.$nextTick();
    expect(vm.form.event_type_id).toBe(5);

    vm.eventTypeIdString = '__none__';
    expect(vm.form.event_type_id).toBeNull();
  });
});

describe('CalendarForm.vue — public URL status link and meeting integration', () => {
  let wrapper: ReturnType<typeof mount>;

  afterEach(() => {
    wrapper?.unmount();
  });

  interface MeetingProp {
    id: string;
    start_time: string;
    title: string;
    trashed: boolean;
    agenda_items_count: number;
    institution_name: string | null;
  }

  function createEditWrapper(
    calendar: Partial<CalendarEventForm> & { id: number },
    readOnly = false,
    meeting: MeetingProp | null = null,
  ) {
    return mount(CalendarForm, {
      shallow: true,
      global: {
        stubs: {
          FormPage: {
            name: 'FormPage',
            props: ['mode', 'disabled', 'publicUrl', 'availableLocales', 'locale', 'barTitle', 'title'],
            template: '<div><slot name="title-status" /><slot name="locale-addon" /><slot /><slot name="aside" /><slot name="danger-zone" /></div>',
          },
          FormPanel: {
            name: 'FormPanel',
            props: ['title', 'icon'],
            template: '<div data-testid="form-panel"><slot /></div>',
          },
          ContentPublishPanel: {
            name: 'ContentPublishPanel',
            props: ['published', 'callout', 'hidePublishTime'],
            template: '<div><slot /></div>',
          },
          TenantSelectField: {
            name: 'TenantSelectField',
            props: ['modelValue', 'disabled'],
            template: '<div />',
          },
          ImageUpload: {
            name: 'ImageUpload',
            template: '<div />',
          },
          TiptapEditor: {
            name: 'TiptapEditor',
            template: '<div />',
          },
          PublicUrlHistoryCard: {
            name: 'PublicUrlHistoryCard',
            template: '<div />',
          },
          PermalinkField: {
            name: 'PermalinkField',
            props: ['permalink', 'baseUrl', 'viewUrl', 'label', 'disabled'],
            template: '<div />',
          },
          PermalinkPreviewHint: {
            name: 'PermalinkPreviewHint',
            template: '<div data-testid="permalink-preview-hint" />',
          },
          FormFieldWrapper: {
            props: ['id', 'label'],
            template: '<div :data-field="id"><slot /></div>',
          },
          DateTimePicker: {
            name: 'DateTimePicker',
            props: {
              modelValue: [Date, String],
              clearable: Boolean,
              disabled: Boolean,
            },
            template: '<div />',
          },
          TagMultiSelect: {
            name: 'TagMultiSelect',
            template: '<div />',
          },
        },
      },
      props: {
        calendar: calendar as CalendarEventForm,
        eventTypes: [],
        assignableTenants: [],
        meeting,
        submitUrl: '/mano/calendar/1',
        submitMethod: 'patch',
        readOnly,
      },
    });
  }

  it('uses non-mutating form controls in read-only mode', () => {
    wrapper = createEditWrapper({
      id: 5,
      title: { lt: 'Renginys', en: 'Event' },
      permalink: { lt: 'renginys', en: 'event' },
      description: { lt: '<p>Aprašymas</p>', en: '<p>Description</p>' },
      date: '2026-05-01T10:00:00',
      images: [{ id: 1, name: 'gallery.jpg', url: '/gallery.jpg' }],
      main_image_url: '/main.jpg',
    }, true);

    expect(wrapper.findComponent({ name: 'FormPage' }).props()).toMatchObject({ mode: 'view', disabled: true });
    expect(wrapper.findComponent({ name: 'ImageUpload' }).exists()).toBe(false);
    expect(wrapper.findComponent({ name: 'TiptapEditor' }).exists()).toBe(false);
  });

  it('links to the URL built from permalink and year once both exist', () => {
    wrapper = createEditWrapper({
      id: 5,
      title: { lt: 'Renginys', en: 'Event' },
      permalink: { lt: 'renginys', en: '' },
      date: '2026-05-01T10:00:00',
      is_draft: false,
    });

    const publicUrl = wrapper.findComponent({ name: 'FormPage' }).props('publicUrl') as string;
    expect(publicUrl).toContain('permalink=renginys');
    expect(publicUrl).toContain('year=2026');
  });

  it('shows no link while the event has no permalink for the active locale yet', () => {
    wrapper = createEditWrapper({ id: 7, title: { lt: 'Renginys', en: 'Event' }, permalink: { lt: '', en: '' }, is_draft: false });

    expect(wrapper.findComponent({ name: 'FormPage' }).props('publicUrl')).toBeUndefined();
  });

  it('shows no link before the event has been saved', () => {
    wrapper = createEditWrapper({ id: 0, title: { lt: '', en: '' }, permalink: { lt: '', en: '' }, is_draft: false });

    expect(wrapper.findComponent({ name: 'FormPage' }).props('publicUrl')).toBeUndefined();
  });

  it('shows no link for a saved draft, which visitors cannot open', () => {
    wrapper = createEditWrapper({
      id: 5,
      title: { lt: 'Renginys', en: 'Event' },
      permalink: { lt: 'renginys', en: '' },
      date: '2026-05-01T10:00:00',
      is_draft: true,
    });

    expect(wrapper.findComponent({ name: 'FormPage' }).props('publicUrl')).toBeUndefined();
  });

  it('provides form-level LT and EN availableLocales', () => {
    wrapper = createEditWrapper({ id: 5, title: { lt: 'Renginys', en: 'Event' } });

    expect(wrapper.findComponent({ name: 'FormPage' }).props('availableLocales')).toEqual(['lt', 'en']);
  });

  it('tracks missing translations per locale in missingLocaleCounts', async () => {
    wrapper = createEditWrapper({ id: 5, title: { lt: 'Renginys', en: '' } });
    const vm = wrapper.vm as unknown as { missingLocaleCounts: { lt: number; en: number } };

    expect(vm.missingLocaleCounts.lt).toBe(0);
    expect(vm.missingLocaleCounts.en).toBe(1);
  });

  it('follows the typed title in the heading but keeps the saved one in the bar', async () => {
    wrapper = createEditWrapper({ id: 5, title: { lt: 'Išsaugotas renginys', en: '' } });
    const vm = wrapper.vm as unknown as { form: { title: { lt: string } } };

    vm.form.title.lt = 'Pakeistas pavadinimas';
    await wrapper.vm.$nextTick();

    expect(wrapper.findComponent({ name: 'FormPage' }).props('barTitle')).toBe('Išsaugotas renginys');
    expect(wrapper.findComponent({ name: 'FormPage' }).props('title')).toBe('Pakeistas pavadinimas');
  });

  it('stores the status segment as the inverse draft flag', async () => {
    wrapper = createEditWrapper({ id: 5, is_draft: false });
    const vm = wrapper.vm as unknown as { form: { is_draft: boolean } };

    expect(vm.form.is_draft).toBe(false);
    expect(wrapper.findComponent({ name: 'ContentPublishPanel' }).props('published')).toBe(true);

    wrapper.findComponent({ name: 'ContentPublishPanel' }).vm.$emit('update:published', false);
    await wrapper.vm.$nextTick();

    expect(vm.form.is_draft).toBe(true);
  });

  it('displays a quick status tag next to the language switcher when announcing a meeting', () => {
    wrapper = createEditWrapper({
      id: 5,
      title: { lt: 'Renginys', en: 'Event' },
    }, false, {
      id: 'meeting-123',
      title: 'Valdybos posėdis',
      institution_name: 'VU SA Valdyba',
      start_time: '2026-05-01 10:00:00',
      trashed: false,
      agenda_items_count: 3,
    });

    const meetingTag = wrapper.find('a[title="Peržiūrėti posėdį naujame lange"]');
    expect(meetingTag.exists()).toBe(true);
    expect(meetingTag.attributes('target')).toBe('_blank');
    expect(meetingTag.text()).toContain('Susietas su posėdžiu');
  });

  it('renders the announced meeting details in the sider panel', () => {
    wrapper = createEditWrapper({
      id: 5,
      title: { lt: 'Renginys', en: 'Event' },
    }, false, {
      id: 'meeting-123',
      title: 'Valdybos posėdis',
      institution_name: 'VU SA Valdyba',
      start_time: '2026-05-01 10:00:00',
      trashed: false,
      agenda_items_count: 3,
    });

    expect(wrapper.text()).toContain('VU SA Valdyba');
    expect(wrapper.text()).toContain('3 darbotvarkės punktai');
  });

  it('configures the end date picker as clearable', () => {
    wrapper = createEditWrapper({ id: 5, date: '2026-05-01 10:00:00', end_date: '2026-05-01 12:00:00' });
    const pickers = wrapper.findAllComponents({ name: 'DateTimePicker' });
    expect(pickers[1].props('clearable')).toBe(true);
  });
});

describe('CalendarForm.vue — all-day default', () => {
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
    event_type_id: null,
    facebook_url: '',
    is_draft: false,
    is_all_day: false,
    is_international: false,
    is_remote: false,
    hero_style: 'card',
  };

  interface FormVm {
    form: { date: string | null; end_date: string | null; is_all_day: boolean };
    isAllDayTouched: boolean;
  }

  function createWrapper(rememberKey: string) {
    return mount(CalendarForm, {
      shallow: true,
      props: {
        calendar,
        eventTypes: [],
        assignableTenants: [],
        rememberKey,
        submitUrl: '/mano/calendar',
        submitMethod: 'post',
      },
    });
  }

  afterEach(() => {
    wrapper?.unmount();
  });

  it('defaults to all-day once a new event spans more than one calendar day', async () => {
    wrapper = createWrapper('CreateCalendarAllDayMultiDay');
    const vm = wrapper.vm as unknown as FormVm;

    vm.form.date = '2026-08-25 09:00:00';
    vm.form.end_date = '2026-08-27 17:00:00';
    await wrapper.vm.$nextTick();

    expect(vm.form.is_all_day).toBe(true);
  });

  it('leaves a same-day event timed', async () => {
    wrapper = createWrapper('CreateCalendarAllDaySameDay');
    const vm = wrapper.vm as unknown as FormVm;

    vm.form.date = '2026-08-25 09:00:00';
    vm.form.end_date = '2026-08-25 17:00:00';
    await wrapper.vm.$nextTick();

    expect(vm.form.is_all_day).toBe(false);
  });

  it('stops auto-deriving once the admin has touched the switch directly', async () => {
    wrapper = createWrapper('CreateCalendarAllDayTouched');
    const vm = wrapper.vm as unknown as FormVm;

    vm.isAllDayTouched = true;
    vm.form.date = '2026-08-25 09:00:00';
    vm.form.end_date = '2026-08-27 17:00:00';
    await wrapper.vm.$nextTick();

    expect(vm.form.is_all_day).toBe(false);
  });
});
