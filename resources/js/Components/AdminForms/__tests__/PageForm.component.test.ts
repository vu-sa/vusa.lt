import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { mount } from '@vue/test-utils';

import PageForm from '@/Components/AdminForms/PageForm.vue';
import FormPage from '@/Components/Layouts/FormPage.vue';
import PermalinkField from '@/Components/AdminForms/PermalinkField.vue';
import PermalinkPreviewHint from '@/Components/AdminForms/PermalinkPreviewHint.vue';
import { commonStubs } from '@/tests/stubs';

vi.mock('@inertiajs/vue3', async () => {
  const actual = await vi.importActual('@inertiajs/vue3');
  return {
    ...actual,
    usePage: () => ({
      props: {
        app: { locale: 'lt', url: 'https://www.vusa.test' },
      },
    }),
  };
});

interface PageFormVm {
  form: Record<string, unknown> & { validate: (field: string) => unknown };
}

describe('PageForm.vue — show_breadcrumbs toggle', () => {
  let wrapper: ReturnType<typeof mount>;

  const defaultPage = {
    id: 1,
    title: 'Test puslapis',
    content: { parts: [] },
    permalink: 'test-puslapis',
    text: null,
    lang: 'lt',
    tenant_id: 1,
    is_active: true,
    aside: null,
    layout: 'default',
    show_table_of_contents: true,
    show_title: true,
    show_breadcrumbs: true,
    highlights: [],
    meta_description: '',
    featured_image: '',
    tenant: { id: 1, alias: 'www', shortname: 'VU SA' },
  };

  const createWrapper = (props = {}) => {
    return mount(PageForm, {
      props: {
        page: defaultPage,
        submitUrl: '/test',
        submitMethod: 'patch' as const,
        ...props,
      },
      global: {
        stubs: {
          FormPage: {
            template: '<div data-testid="form-page"><slot name="title-status" /><slot name="header-actions" /><slot /><slot name="aside" /></div>',
            props: ['title', 'headTitle', 'lead', 'entityType', 'backHref', 'backLabel', 'processing', 'dirty', 'errors', 'fieldIds', 'mode', 'maxWidth', 'availableLocales'],
          },
          FormSection: {
            template: '<section><slot /></section>',
            props: ['title', 'description'],
          },
          DateTimePicker: { template: '<div />' },
          ContentAnalyticsCard: { template: '<div />' },
          PublicUrlHistoryCard: { template: '<div />' },
          ActivityLogSheet: { template: '<div />' },
          TagMultiSelect: { template: '<div />' },
          RichContentFormElement: { template: '<div />' },
          FormFieldWrapper: {
            props: ['id', 'label'],
            template: '<div :data-field="id"><slot /></div>',
          },
          PermalinkField: {
            props: ['permalink', 'baseUrl', 'disabled', 'viewUrl', 'explanation', 'warning', 'hint', 'validating', 'valid', 'invalid'],
            template: `
              <div data-testid="permalink-field">
                <span data-testid="permalink-disabled">{{ disabled }}</span>
                <span data-testid="permalink-warning">{{ warning }}</span>
              </div>
            `,
          },
          SEOPreview: { template: '<div />' },
          OrderedListInput: { template: '<div />' },
          Collapsible: { template: '<div><slot /></div>' },
          CollapsibleContent: { template: '<div><slot /></div>' },
          CollapsibleTrigger: { template: '<div><slot /></div>' },
          Input: { template: '<input />' },
          Textarea: { template: '<textarea />' },
          Label: { template: '<label><slot /></label>' },
          Button: { template: '<button><slot /></button>' },
          Alert: { template: '<div><slot /></div>' },
          AlertTitle: { template: '<div><slot /></div>' },
          AlertDescription: { template: '<div><slot /></div>' },
          Select: { template: '<div />' },
          SelectTrigger: { template: '<div />' },
          SelectValue: { template: '<div />' },
          SelectContent: { template: '<div />' },
          SelectItem: { template: '<div />' },
          ToggleGroup: { template: '<div><slot /></div>' },
          ToggleGroupItem: { template: '<div />' },
          ImageUpload: { template: '<div />' },
          CollectionSelectDialog: { template: '<div />' },
          // Mirrors the real reka-ui Switch: binds modelValue, emits update:modelValue.
          Switch: {
            template: '<button type="button" role="switch" :aria-checked="modelValue" @click="$emit(\'update:modelValue\', !modelValue)" />',
            props: ['modelValue'],
          },
        },
      },
    });
  };

  beforeEach(() => {
    vi.clearAllMocks();
  });

  afterEach(() => {
    wrapper?.unmount();
  });

  function findBreadcrumbsSwitch() {
    return wrapper.find('[data-testid="toggle-breadcrumbs"] [role="switch"]');
  }

  it('defaults show_breadcrumbs to true when the page omits it', () => {
    wrapper = createWrapper({
      page: { ...defaultPage, show_breadcrumbs: undefined },
    });
    const vm = wrapper.vm as unknown as PageFormVm;

    expect(vm.form.show_breadcrumbs).toBe(true);
  });

  it('reflects an explicit show_breadcrumbs=false on the switch', () => {
    wrapper = createWrapper({
      page: { ...defaultPage, show_breadcrumbs: false },
    });

    const toggle = findBreadcrumbsSwitch();
    expect(toggle.attributes('aria-checked')).toBe('false');
  });

  it('toggles form.show_breadcrumbs when the switch is clicked', async () => {
    wrapper = createWrapper();
    const vm = wrapper.vm as unknown as PageFormVm;
    expect(vm.form.show_breadcrumbs).toBe(true);

    const toggle = findBreadcrumbsSwitch();
    await toggle.trigger('click');
    expect(vm.form.show_breadcrumbs).toBe(false);

    await toggle.trigger('click');
    expect(vm.form.show_breadcrumbs).toBe(true);
  });

  it('allows editing the permalink, warning about the redirect only once it changes', async () => {
    wrapper = createWrapper({
      page: defaultPage,
      submitMethod: 'patch',
    });

    const field = wrapper.findComponent(PermalinkField);
    expect(field.exists()).toBe(true);
    expect(field.props('disabled')).toBe(false);
    expect(field.props('warning')).toBeUndefined();

    field.vm.$emit('update:permalink', 'naujas-adresas');
    await wrapper.vm.$nextTick();

    expect(field.props('warning')).toBe('Pakeitus nuorodą, sena nuoroda ir toliau nukreips į šį puslapį — nebereikalingas senas nuorodas galėsite ištrinti.');
  });

  it('switches between draft and published with the status segment', async () => {
    wrapper = createWrapper();
    const vm = wrapper.vm as unknown as PageFormVm;

    await wrapper.find('[data-testid="page-status-draft"]').trigger('click');
    expect(vm.form.is_active).toBe(false);
    // The bar keeps stating what is saved until the next save.
    expect(wrapper.find('[data-slot="status-badge"]').text()).toBe('Paskelbta');
    expect(wrapper.find('[data-testid="page-status-callout"]').text()).toContain('Juodraštis matomas tik sistemoje');

    await wrapper.find('[data-testid="page-status-published"]').trigger('click');
    expect(vm.form.is_active).toBe(true);
    expect(wrapper.find('[data-testid="page-status-published"]').attributes('aria-pressed')).toBe('true');
  });

  it('offers no publish time, since a page is only a draft or published', () => {
    wrapper = createWrapper();

    expect(wrapper.find('#publish_time').exists()).toBe(false);
  });

  it('sets the page language with the language segment', async () => {
    wrapper = createWrapper();
    const vm = wrapper.vm as unknown as PageFormVm;
    // Precognition would otherwise fire a real validation request.
    vi.spyOn(vm.form, 'validate').mockImplementation(() => vm.form);

    await wrapper.find('[data-testid="page-lang-en"]').trigger('click');

    expect(vm.form.lang).toBe('en');
    expect(wrapper.find('[data-testid="page-lang-en"]').attributes('aria-pressed')).toBe('true');
  });

  it('has no form-level LT | EN switch — a page is written in one language', () => {
    wrapper = createWrapper();

    expect(wrapper.findComponent(FormPage).props('availableLocales')).toEqual([]);
  });

  it('asks for the unit only on create', () => {
    wrapper = createWrapper();
    expect(wrapper.find('[data-field="tenant"]').exists()).toBe(false);
    wrapper.unmount();

    wrapper = createWrapper({ rememberKey: 'CreatePage', submitMethod: 'post' });
    expect(wrapper.find('[data-field="tenant"]').exists()).toBe(true);
  });

  it('does not double up the main tenant into the displayed base url', () => {
    // app.url ("https://www.vusa.test") already carries the "www." the main tenant's own
    // subdomain would add — regression for a bug that produced "vusa.www.vusa.test".
    wrapper = createWrapper({
      page: { ...defaultPage, tenant: { id: 16, alias: 'vusa', shortname: 'VU SA' } },
    });

    const field = wrapper.findComponent(PermalinkField);
    expect(field.props('baseUrl')).toBe('www.vusa.test');
  });

  it('hides the permalink field on create — the server generates it', () => {
    wrapper = createWrapper({
      page: defaultPage,
      rememberKey: 'CreatePage',
      submitMethod: 'post',
    });

    expect(wrapper.findComponent(PermalinkField).exists()).toBe(false);
    expect(wrapper.findComponent(PermalinkPreviewHint).exists()).toBe(true);
  });
});

describe('PageForm.vue — create mode tenant selection', () => {
  let wrapper: ReturnType<typeof mount>;

  // useForm('CreatePage', ...) remembers form state in Inertia's shared page singleton keyed by
  // that literal string — every test here reuses it (PageForm's isCreate requires the exact
  // 'CreatePage' key), so a value set by one test would otherwise leak into the next.
  beforeEach(async () => {
    const { router } = await vi.importActual<typeof import('@inertiajs/vue3')>('@inertiajs/vue3');
    router.remember(undefined, 'CreatePage');
  });

  function createShallowWrapper(assignableTenants: App.Entities.Tenant[], rememberKey?: string) {
    return mount(PageForm, {
      shallow: true,
      props: {
        page: { title: '', lang: 'lt', content: { parts: [] }, tenant_id: null },
        assignableTenants,
        rememberKey,
        submitUrl: '/test',
        submitMethod: 'post' as const,
      },
    });
  }

  afterEach(() => {
    wrapper?.unmount();
  });

  it('leaves tenant_id unset outside create mode, even with a single assignable tenant', () => {
    wrapper = createShallowWrapper([{ id: 2, shortname: 'VU SA FF', type: 'padalinys' }] as App.Entities.Tenant[]);

    const vm = wrapper.vm as unknown as { form: { tenant_id: number | null } };
    expect(vm.form.tenant_id).toBeNull();
  });

  it('defaults to the sole assignable tenant', () => {
    wrapper = createShallowWrapper([{ id: 2, shortname: 'VU SA FF', type: 'padalinys' }] as App.Entities.Tenant[], 'CreatePage');

    const vm = wrapper.vm as unknown as { form: { tenant_id: number | null } };
    expect(vm.form.tenant_id).toBe(2);
  });

  it('prefers the main tenant when several are assignable (e.g. a super admin)', () => {
    wrapper = createShallowWrapper([
      { id: 2, shortname: 'VU SA FF', type: 'padalinys' },
      { id: 16, shortname: 'VU SA', type: 'pagrindinis' },
    ] as App.Entities.Tenant[], 'CreatePage');

    const vm = wrapper.vm as unknown as { form: { tenant_id: number | null } };
    expect(vm.form.tenant_id).toBe(16);
  });
});
