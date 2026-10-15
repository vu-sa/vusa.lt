import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { mount } from '@vue/test-utils';

import NewsForm from '@/Components/AdminForms/NewsForm.vue';
import FormPage from '@/Components/Layouts/FormPage.vue';
import PermalinkField from '@/Components/AdminForms/PermalinkField.vue';
import PermalinkPreviewHint from '@/Components/AdminForms/PermalinkPreviewHint.vue';
import SEOPreview from '@/Components/AdminForms/SEOPreview.vue';

// Use the real Inertia useForm (which has withPrecognition); only override usePage
// with a minimal page object. The global inertia mock's useForm lacks withPrecognition.
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

interface NewsFormVm {
  form: Record<string, unknown> & { validate: (field: string) => unknown };
}

describe('NewsForm.vue', () => {
  let wrapper: ReturnType<typeof mount>;

  const defaultNews = {
    id: 1,
    title: 'Testinė naujiena',
    permalink: 'testine-naujiena',
    short: 'Trumpas aprašymas',
    lang: 'lt',
    content: { parts: [] },
    image: 'image.jpg',
    image_author: 'Autorius',
    draft: 0,
    publish_time: '2020-01-01T10:00:00Z',
    show_breadcrumbs: true,
    highlights: [],
    tenant_id: 1,
    tenant: { id: 1, shortname: 'VU SA' },
  };

  const createWrapper = (props = {}) => {
    return mount(NewsForm, {
      props: {
        news: defaultNews,
        submitUrl: '/test',
        submitMethod: 'patch' as const,
        ...props,
      },
      global: {
        stubs: {
          FormPage: {
            template: '<div data-testid="form-page"><slot name="title-status" /><slot /><slot name="aside" /><slot name="danger-zone" /></div>',
            props: ['title', 'headTitle', 'entityType', 'backHref', 'backLabel', 'processing', 'dirty', 'errors', 'fieldIds', 'mode', 'availableLocales', 'barTitle', 'publicUrl', 'activitySubject', 'createdAt', 'updatedAt'],
          },
          DateTimePicker: { template: '<div />' },
          ContentAnalyticsCard: { template: '<div />' },
          PublicUrlHistoryCard: { template: '<div />' },
          TagMultiSelect: { template: '<div />' },
          RichContentFormElement: { template: '<div />' },
          TiptapEditor: { template: '<div />' },
          FormFieldWrapper: {
            props: ['id', 'label'],
            template: '<div :data-field="id"><slot /></div>',
          },
          PermalinkField: {
            props: ['permalink', 'baseUrl', 'viewUrl', 'warning', 'hint', 'validating', 'valid', 'invalid'],
            template: '<div data-testid="permalink-field" />',
          },
          SEOPreview: { template: '<div />', props: ['description'] },
          OrderedListInput: { template: '<div />' },
          Input: { template: '<input />' },
          Button: { template: '<button><slot /></button>' },
          Select: { template: '<div />' },
          SelectTrigger: { template: '<div />' },
          SelectValue: { template: '<div />' },
          SelectContent: { template: '<div />' },
          SelectItem: { template: '<div />' },
          ImageUpload: { template: '<div />' },
          CollectionSelectDialog: { template: '<div />' },
          ConfirmDialog: { template: '<div />' },
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

  it('defaults show_breadcrumbs to true when the news omits it', () => {
    wrapper = createWrapper({ news: { ...defaultNews, show_breadcrumbs: undefined } });
    const vm = wrapper.vm as unknown as NewsFormVm;

    expect(vm.form.show_breadcrumbs).toBe(true);
  });

  it('toggles show_breadcrumbs with its display setting row', async () => {
    wrapper = createWrapper({ news: { ...defaultNews, show_breadcrumbs: false } });
    const vm = wrapper.vm as unknown as NewsFormVm;
    expect(findBreadcrumbsSwitch().attributes('aria-checked')).toBe('false');

    await findBreadcrumbsSwitch().trigger('click');

    expect(vm.form.show_breadcrumbs).toBe(true);
  });

  it('stores the status segment as the inverse draft flag', async () => {
    wrapper = createWrapper();
    const vm = wrapper.vm as unknown as NewsFormVm;
    expect(wrapper.find('[data-testid="news-status-published"]').attributes('aria-pressed')).toBe('true');
    expect(wrapper.find('[data-slot="status-badge"]').text()).toBe('Paskelbta');
    expect(wrapper.find('[data-testid="news-status-published"]').classes()).toContain('bg-status-success-surface');

    await wrapper.find('[data-testid="news-status-draft"]').trigger('click');

    expect(vm.form.draft).toBe(true);
    expect(wrapper.find('[data-testid="news-status-draft"]').attributes('aria-pressed')).toBe('true');
    // The bar keeps stating what is saved until the next save.
    expect(wrapper.find('[data-slot="status-badge"]').text()).toBe('Paskelbta');
    // Same grey/green as the status tags in the news list, not the brand fill.
    expect(wrapper.find('[data-testid="news-status-draft"]').classes()).toContain('bg-status-neutral-surface');
    expect(wrapper.find('[data-testid="news-status-draft"]').classes()).not.toContain('bg-brand-fill');
  });

  it('follows the typed title in the heading but keeps the saved one in the bar', async () => {
    wrapper = createWrapper();
    const vm = wrapper.vm as unknown as NewsFormVm;

    vm.form.title = 'Naujas pavadinimas';
    await wrapper.vm.$nextTick();

    expect(wrapper.findComponent(FormPage).props('title')).toBe('Naujas pavadinimas');
    expect(wrapper.findComponent(FormPage).props('barTitle')).toBe('Testinė naujiena');
  });

  it('shows no saved status while creating', () => {
    wrapper = createWrapper({ news: { ...defaultNews, id: undefined }, rememberKey: 'CreateNews', submitMethod: 'post' });

    expect(wrapper.find('[data-slot="status-badge"]').exists()).toBe(false);
  });

  it('offers no public link for a saved draft, which visitors cannot open', () => {
    wrapper = createWrapper({ news: { ...defaultNews, draft: 1 } });

    expect(wrapper.find('[data-testid="news-status-callout"]').text()).toContain('svetainės lankytojai jo nemato');
    expect(wrapper.findComponent(FormPage).props('publicUrl')).toBeUndefined();
    expect(wrapper.findComponent(PermalinkField).props('viewUrl')).toBeUndefined();
  });

  it('warns about the redirect only once the permalink changes', async () => {
    wrapper = createWrapper();

    const field = wrapper.findComponent(PermalinkField);
    expect(field.props('warning')).toBeUndefined();
    expect(field.props('baseUrl')).toBe('www.vusa.test/lt/naujiena');

    field.vm.$emit('update:permalink', 'naujas-adresas');
    await wrapper.vm.$nextTick();

    expect(field.props('warning')).toContain('Pakeitus nuorodą');
  });

  it('shows plain text in the search preview even with nested markup', () => {
    wrapper = createWrapper({ news: { ...defaultNews, short: '<p>Labas <scr<b>ipt>pasauli</scr<b>ipt></p>' } });

    expect(wrapper.findComponent(SEOPreview).props('description')).toBe('Labas pasauli');
  });

  it('sets the article language with the language segment', async () => {
    wrapper = createWrapper();
    const vm = wrapper.vm as unknown as NewsFormVm;
    // Precognition would otherwise fire a real validation request.
    vi.spyOn(vm.form, 'validate').mockImplementation(() => vm.form);

    await wrapper.find('[data-testid="news-lang-en"]').trigger('click');

    expect(vm.form.lang).toBe('en');
  });

  it('has no form-level LT | EN switch — an article is written in one language', () => {
    wrapper = createWrapper();

    expect(wrapper.findComponent(FormPage).props('availableLocales')).toEqual([]);
  });

  it('previews the permalink and asks for the unit only on create', () => {
    wrapper = createWrapper();
    expect(wrapper.find('[data-field="tenant"]').exists()).toBe(false);
    wrapper.unmount();

    wrapper = createWrapper({ news: { ...defaultNews, id: undefined }, rememberKey: 'CreateNews', submitMethod: 'post' });

    expect(wrapper.find('[data-field="tenant"]').exists()).toBe(true);
    expect(wrapper.findComponent(PermalinkField).exists()).toBe(false);
    expect(wrapper.findComponent(PermalinkPreviewHint).exists()).toBe(true);
  });
});

describe('NewsForm.vue — create mode tenant selection', () => {
  let wrapper: ReturnType<typeof mount>;

  // useForm('CreateNews', ...) remembers form state in Inertia's shared page singleton keyed by
  // that literal string — every test here reuses it (NewsForm's isCreate requires the exact
  // 'CreateNews' key), so a value set by one test would otherwise leak into the next.
  beforeEach(async () => {
    const { router } = await vi.importActual<typeof import('@inertiajs/vue3')>('@inertiajs/vue3');
    router.remember(undefined, 'CreateNews');
  });

  function createWrapper(assignableTenants: App.Entities.Tenant[], rememberKey?: string) {
    return mount(NewsForm, {
      shallow: true,
      props: {
        news: { title: '', lang: 'lt', content: { parts: [] } },
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
    wrapper = createWrapper([{ id: 2, shortname: 'VU SA FF', type: 'padalinys' }] as App.Entities.Tenant[]);

    const vm = wrapper.vm as unknown as { form: { tenant_id: number | null } };
    expect(vm.form.tenant_id).toBeNull();
  });

  it('defaults to the sole assignable tenant', () => {
    wrapper = createWrapper([{ id: 2, shortname: 'VU SA FF', type: 'padalinys' }] as App.Entities.Tenant[], 'CreateNews');

    const vm = wrapper.vm as unknown as { form: { tenant_id: number | null } };
    expect(vm.form.tenant_id).toBe(2);
  });

  it('prefers the main tenant when several are assignable (e.g. a super admin)', () => {
    wrapper = createWrapper([
      { id: 2, shortname: 'VU SA FF', type: 'padalinys' },
      { id: 16, shortname: 'VU SA', type: 'pagrindinis' },
    ] as App.Entities.Tenant[], 'CreateNews');

    const vm = wrapper.vm as unknown as { form: { tenant_id: number | null } };
    expect(vm.form.tenant_id).toBe(16);
  });

  it('leaves tenant_id unset when nothing is assignable, so the required field blocks submit', () => {
    wrapper = createWrapper([], 'CreateNews');

    const vm = wrapper.vm as unknown as { form: { tenant_id: number | null } };
    expect(vm.form.tenant_id).toBeNull();
  });
});
