import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { mount } from '@vue/test-utils';

import NewsForm from '@/Components/AdminForms/NewsForm.vue';
import { commonStubs } from '@/tests/stubs';

// Use the real Inertia useForm (which has withPrecognition); only override usePage
// with a minimal page object. The global inertia mock's useForm lacks withPrecognition.
vi.mock('@inertiajs/vue3', async () => {
  const actual = await vi.importActual('@inertiajs/vue3');
  return {
    ...actual,
    usePage: () => ({
      props: {
        app: { locale: 'lt', url: 'https://vusa.test' },
      },
    }),
  };
});

describe('NewsForm.vue — show_breadcrumbs toggle', () => {
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
    publish_time: null,
    layout: 'modern',
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
          ...commonStubs,
          AdminForm: {
            template: '<form @submit.prevent><slot name="status-header" /><slot /></form>',
            props: ['model'],
          },
          FormElement: {
            template: '<section><slot name="title" /><slot name="description" /><slot /></section>',
          },
          FormStatusHeader: { template: '<div />' },
          RichContentFormElement: { template: '<div />' },
          FormFieldWrapper: {
            template: '<div><slot /></div>',
          },
          TiptapEditor: { template: '<div />' },
          MultiSelect: { template: '<div />' },
          OrderedListInput: { template: '<div />' },
          Collapsible: { template: '<div><slot /></div>' },
          CollapsibleContent: { template: '<div><slot /></div>' },
          CollapsibleTrigger: { template: '<div><slot /></div>' },
          Input: { template: '<input />' },
          Label: { template: '<label><slot /></label>' },
          Button: { template: '<button><slot /></button>' },
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

  it('defaults show_breadcrumbs to true when the news omits it', () => {
    wrapper = createWrapper({ news: { ...defaultNews, show_breadcrumbs: undefined } });
    const vm = wrapper.vm as any;

    expect(vm.form.show_breadcrumbs).toBe(true);
  });

  it('reflects the initial show_breadcrumbs state on the switch', () => {
    wrapper = createWrapper({ news: { ...defaultNews, show_breadcrumbs: false } });
    const toggle = wrapper.find('[role="switch"]');

    expect(toggle.attributes('aria-checked')).toBe('false');
  });

  it('toggles form.show_breadcrumbs when the switch is clicked (model-value binding)', async () => {
    wrapper = createWrapper();
    const vm = wrapper.vm as any;
    expect(vm.form.show_breadcrumbs).toBe(true);

    await wrapper.find('[role="switch"]').trigger('click');
    expect(vm.form.show_breadcrumbs).toBe(false);

    await wrapper.find('[role="switch"]').trigger('click');
    expect(vm.form.show_breadcrumbs).toBe(true);
  });
});
