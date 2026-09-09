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
          Alert: {
            template: '<div data-testid="alert"><slot /></div>',
          },
          AlertTitle: { template: '<strong><slot /></strong>' },
          AlertDescription: { template: '<div data-testid="alert-description"><slot /></div>' },
          FormElement: {
            props: ['sectionNumber'],
            template: '<section :data-section="sectionNumber"><slot name="title" /><slot name="description" /><slot /></section>',
          },
          FormStatusHeader: { template: '<div />' },
          RichContentFormElement: { template: '<div data-testid="rich-content-form-element" />' },
          FormFieldWrapper: {
            template: '<div><slot /></div>',
          },
          TiptapEditor: { template: '<div data-testid="tiptap-editor" />' },
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
          IFluentWarning24Regular: { template: '<span class="icon-warning" />' },
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
    const vm = wrapper.vm as unknown as { form: { show_breadcrumbs: boolean } };

    expect(vm.form.show_breadcrumbs).toBe(true);
  });

  it('reflects the initial show_breadcrumbs state on the switch', () => {
    wrapper = createWrapper({ news: { ...defaultNews, show_breadcrumbs: false } });
    const toggle = wrapper.find('[role="switch"]');

    expect(toggle.attributes('aria-checked')).toBe('false');
  });

  it('toggles form.show_breadcrumbs when the switch is clicked (model-value binding)', async () => {
    wrapper = createWrapper();
    const vm = wrapper.vm as unknown as { form: { show_breadcrumbs: boolean } };
    expect(vm.form.show_breadcrumbs).toBe(true);

    await wrapper.find('[role="switch"]').trigger('click');
    expect(vm.form.show_breadcrumbs).toBe(false);

    await wrapper.find('[role="switch"]').trigger('click');
    expect(vm.form.show_breadcrumbs).toBe(true);
  });

  it('renders a serious warning below the permalink field', () => {
    wrapper = createWrapper();

    const alert = wrapper.find('[data-testid="alert"]');
    expect(alert.exists()).toBe(true);
    expect(alert.text()).toContain('Pakeitus nuorodą, sena nuoroda ir toliau nukreips į šį puslapį — nebereikalingas senas nuorodas galėsite ištrinti.');
  });

  it('renders intro text above rich content with updated description', () => {
    wrapper = createWrapper();

    const sections = wrapper.findAll('section[data-section]');
    const section3 = sections.find(s => s.attributes('data-section') === '3');
    const section4 = sections.find(s => s.attributes('data-section') === '4');

    expect(section3).toBeDefined();
    expect(section4).toBeDefined();

    expect(section3!.text()).toContain('Įvadinis tekstas');
    expect(section3!.text()).toContain('Naudojamas naujienos įvade ir paieškos rezultatuose (SEO)');
    expect(section3!.find('[data-testid="tiptap-editor"]').exists()).toBe(true);

    expect(section4!.text()).toContain('Turinys');
    expect(section4!.find('[data-testid="rich-content-form-element"]').exists()).toBe(true);

    const html = wrapper.html();
    expect(html.indexOf('data-section="3"')).toBeLessThan(html.indexOf('data-section="4"'));
  });
});
