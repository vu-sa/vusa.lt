import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { ref } from 'vue';

import NavigationForm from '@/Components/AdminForms/NavigationForm.vue';

// Use the real Inertia useForm so nested `extra_attributes.*` mutations are properly
// reactive (the global mock in resources/js/mocks/inertia.mock.ts returns a plain,
// non-reactive object — see NewsForm.component.test.ts for the same workaround).
// useForm is wrapped to capture the created instance, so a test can simulate a
// server validation error via `capturedForm.errors = {...}` the way a failed
// Inertia submission normally would.
let capturedForm: Record<string, any> | null = null;

vi.mock('@inertiajs/vue3', async () => {
  const actual = await vi.importActual<typeof import('@inertiajs/vue3')>('@inertiajs/vue3');
  return {
    ...actual,
    usePage: () => ({
      props: { app: { locale: 'lt', url: 'https://vusa.test' } },
    }),
    useForm: (...args: unknown[]) => {
      // @ts-expect-error — passthrough to the real implementation with variadic args
      const form = actual.useForm(...args);
      capturedForm = form;
      return form;
    },
  };
});

const resolveUrlData = ref<{ url: string } | null>(null);
const executeResolveUrl = vi.fn(async () => {});

vi.mock('@/Composables/useApi', () => ({
  useApiMutation: vi.fn(() => ({
    execute: executeResolveUrl,
    isFetching: ref(false),
    data: resolveUrlData,
    error: ref(null),
  })),
}));

const formStubs = {
  AdminForm: { props: ['model'], template: '<form @submit.prevent><slot /></form>' },
  FormElement: { template: '<section><slot name="title" /><slot /></section>' },
  FormFieldWrapper: {
    props: ['id', 'label', 'required', 'error', 'helperText'],
    template: '<div><span class="field-label">{{ label }}</span><slot /><span v-if="error" class="field-error">{{ error }}</span></div>',
  },
  Collapsible: { template: '<div><slot /></div>' },
  CollapsibleContent: { template: '<div><slot /></div>' },
  CollapsibleTrigger: { template: '<div><slot /></div>' },
  Input: { props: ['modelValue', 'disabled'], template: '<input :disabled="disabled" :value="modelValue" @input="$emit(\'update:modelValue\', $event.target.value)">' },
  Textarea: { template: '<textarea />' },
  Label: { template: '<label><slot /></label>' },
  Switch: { props: ['modelValue'], template: '<button role="switch" :aria-checked="modelValue" @click="$emit(\'update:modelValue\', !modelValue)" />' },
  Badge: { template: '<span><slot /></span>' },
  ButtonGroup: { template: '<div><slot /></div>' },
  ToggleGroup: {
    props: ['modelValue'],
    template: '<div><slot /></div>',
  },
  ToggleGroupItem: { props: ['value'], template: '<button type="button" @click="$parent.$emit(\'update:modelValue\', value)"><slot /></button>' },
  Select: {
    props: ['modelValue'],
    emits: ['update:modelValue'],
    template: '<select :value="modelValue" @change="$emit(\'update:modelValue\', $event.target.value)"><slot /></select>',
  },
  SelectTrigger: { template: '<div><slot /></div>' },
  SelectValue: { template: '<div />' },
  SelectContent: { template: '<slot />' },
  SelectItem: { props: ['value'], template: '<option :value="value"><slot /></option>' },
  SingleSelect: { template: '<div />' },
  FocalPointPicker: { template: '<div data-testid="focal-point-picker" />' },
  FluentIconSelect: { template: '<div />' },
  TiptapImageButton: { template: '<div><slot /></div>' },
  MultiCollectionSelectDialog: {
    props: ['open'],
    emits: ['confirm', 'update:open'],
    template: `
      <div>
        <slot name="trigger" />
        <button data-testid="confirm-picker" @click="$emit('confirm', [{ collection: 'pages', recordId: '42', title: 'Stipendijos ir parama' }])">confirm</button>
      </div>
    `,
  },
};

function createWrapper(props: Record<string, unknown> = {}) {
  return mount(NavigationForm, {
    props: {
      navigation: {
        id: 1,
        name: 'Studentams',
        url: '/studentams',
        parent_id: 0,
        lang: 'lt',
        is_active: true,
        extra_attributes: {},
      },
      parentElements: [],
      categoryOptions: [{ id: 5, name: 'Renginiai', alias: 'renginiai' }],
      ...props,
    },
    global: { stubs: formStubs },
  });
}

describe('NavigationForm.vue', () => {
  let wrapper: ReturnType<typeof mount>;

  beforeEach(() => {
    vi.clearAllMocks();
    resolveUrlData.value = null;
  });

  afterEach(() => {
    wrapper?.unmount();
  });

  it('hides the image section when there is no image and shows the "show" label', () => {
    wrapper = createWrapper();

    expect(wrapper.text()).toContain('navigation.form.show_image');
    expect(wrapper.text()).not.toContain('navigation.form.hide_image');
  });

  it('starts with advanced settings collapsed', () => {
    wrapper = createWrapper();

    expect(wrapper.text()).toContain('navigation.form.show_advanced');
  });

  it('labels the icon field', () => {
    wrapper = createWrapper();

    expect(wrapper.text()).toContain('navigation.form.icon');
  });

  it('picks the element type visually and marks the selected option', async () => {
    wrapper = createWrapper();

    const typeButtons = wrapper.findAll('button').filter(btn => btn.text().includes('navigation.form.type_'));
    const headingButton = typeButtons.find(btn => btn.text().includes('navigation.form.type_heading'));
    await headingButton?.trigger('click');

    expect(capturedForm?.extra_attributes.type).toBe('heading');
  });

  it('surfaces a server validation error on the url field', async () => {
    wrapper = createWrapper();

    expect(capturedForm).toBeTruthy();
    capturedForm!.errors = { url: 'Nuoroda yra privaloma.' };
    await wrapper.vm.$nextTick();

    expect(wrapper.text()).toContain('Nuoroda yra privaloma.');
  });

  it('disables the name field and hides the link target for a divider', async () => {
    wrapper = createWrapper({
      navigation: {
        id: 2,
        name: '',
        url: '#',
        parent_id: 0,
        lang: 'lt',
        is_active: true,
        extra_attributes: { type: 'divider' },
      },
    });

    const nameInput = wrapper.find('#name');
    expect((nameInput.element as HTMLInputElement).disabled).toBe(true);
    expect(wrapper.text()).not.toContain('navigation.form.link_target');
  });

  it('fills the url after confirming a picked link target', async () => {
    wrapper = createWrapper();
    resolveUrlData.value = { url: '/lt/stipendijos-ir-parama' };

    await wrapper.find('[data-testid="confirm-picker"]').trigger('click');
    await wrapper.vm.$nextTick();

    expect(executeResolveUrl).toHaveBeenCalled();
    const urlInput = wrapper.find('#url');
    expect((urlInput.element as HTMLInputElement).value).toBe('/lt/stipendijos-ir-parama');
  });

  it('fills the url after picking a category', async () => {
    wrapper = createWrapper();
    resolveUrlData.value = { url: '/lt/kategorija/renginiai' };

    const categorySelect = wrapper.findAll('select').find(s => s.findAll('option').some(o => o.text() === 'Renginiai'));
    await categorySelect?.setValue('5');
    await wrapper.vm.$nextTick();

    expect(executeResolveUrl).toHaveBeenCalled();
    const urlInput = wrapper.find('#url');
    expect((urlInput.element as HTMLInputElement).value).toBe('/lt/kategorija/renginiai');
  });
});
