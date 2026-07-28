import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { mount } from '@vue/test-utils';

import PageForm from '@/Components/AdminForms/PageForm.vue';
import { commonStubs } from '@/tests/stubs';

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

describe('PageForm.vue — show_breadcrumbs toggle', () => {
  let wrapper: ReturnType<typeof mount>;

  const defaultPage = {
    id: 1,
    title: 'Test puslapis',
    content: { parts: [] },
    permalink: 'test-puslapis',
    text: null,
    lang: 'lt',
    category_id: 1,
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
    publish_time: null,
    tenant: { id: 1, alias: 'www', shortname: 'VU SA' },
  };

  const createWrapper = (props = {}) => {
    return mount(PageForm, {
      props: {
        categories: [{ id: 1, name: 'Kategorija' }],
        page: defaultPage,
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
          PermalinkField: { template: '<div />' },
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

  // PageForm renders several switches (ToC, title, breadcrumbs) inside identical
  // `.flex.items-center.gap-3` wrappers — pick the one whose sibling label matches.
  function findSwitchForLabel(substr: string) {
    const containers = wrapper.findAll('.flex.items-center.gap-3');
    const target = containers.find(c => c.text().toLowerCase().includes(substr));
    expect(target, `toggle row with label containing "${substr}"`).toBeTruthy();
    return target!.find('[role="switch"]');
  }

  it('defaults show_breadcrumbs to true when the page omits it', () => {
    wrapper = createWrapper({
      page: { ...defaultPage, show_breadcrumbs: undefined },
    });
    const vm = wrapper.vm as any;

    expect(vm.form.show_breadcrumbs).toBe(true);
  });

  it('reflects an explicit show_breadcrumbs=false on the switch', () => {
    wrapper = createWrapper({
      page: { ...defaultPage, show_breadcrumbs: false },
    });

    // The label text is `Rodyti puslapio kelią (breadcrumbs)`.
    const toggle = findSwitchForLabel('breadcrumbs');
    expect(toggle.attributes('aria-checked')).toBe('false');
  });

  it('toggles form.show_breadcrumbs when the switch is clicked', async () => {
    wrapper = createWrapper();
    const vm = wrapper.vm as any;
    expect(vm.form.show_breadcrumbs).toBe(true);

    const toggle = findSwitchForLabel('breadcrumbs');
    await toggle.trigger('click');
    expect(vm.form.show_breadcrumbs).toBe(false);

    await toggle.trigger('click');
    expect(vm.form.show_breadcrumbs).toBe(true);
  });
});
