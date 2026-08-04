import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { ref } from 'vue';

import QuickLinkForm from '@/Components/AdminForms/QuickLinkForm.vue';

// Real useForm (reactive) — the global inertia mock's useForm returns a plain object,
// which would silently break `form.link` reactivity in the template.
vi.mock('@inertiajs/vue3', async () => {
  const actual = await vi.importActual<typeof import('@inertiajs/vue3')>('@inertiajs/vue3');
  return {
    ...actual,
    usePage: () => ({ props: { app: { locale: 'lt', url: 'https://vusa.test' } } }),
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
  FormElement: { template: '<section><slot name="title" /><slot name="subtitle" /><slot /></section>' },
  FormFieldWrapper: {
    props: ['id', 'label', 'required', 'error', 'helperText'],
    template: '<div><slot /><span v-if="error" class="field-error">{{ error }}</span></div>',
  },
  Input: { props: ['modelValue'], template: '<input :value="modelValue" @input="$emit(\'update:modelValue\', $event.target.value)">' },
  Switch: { props: ['modelValue'], template: '<button role="switch" @click="$emit(\'update:modelValue\', !modelValue)" />' },
  SingleSelect: { template: '<div />' },
  Suspense: { template: '<div><slot /></div>' },
  FluentIconSelect: { template: '<div />' },
  ToggleGroup: { template: '<div><slot /></div>' },
  ToggleGroupItem: { template: '<button type="button"><slot /></button>' },
  Select: {
    props: ['modelValue'],
    emits: ['update:modelValue'],
    template: '<select :value="modelValue" @change="$emit(\'update:modelValue\', $event.target.value)"><slot /></select>',
  },
  SelectTrigger: { template: '<div><slot /></div>' },
  SelectValue: { template: '<div />' },
  SelectContent: { template: '<slot />' },
  SelectItem: { props: ['value'], template: '<option :value="value"><slot /></option>' },
  MultiCollectionSelectDialog: {
    props: ['open'],
    emits: ['confirm', 'update:open'],
    template: `
      <div>
        <slot name="trigger" />
        <button data-testid="confirm-picker" @click="$emit('confirm', [{ collection: 'news', recordId: '7', title: 'Nauja stipendija' }])">confirm</button>
      </div>
    `,
  },
};

function createWrapper(props: Record<string, unknown> = {}) {
  return mount(QuickLinkForm, {
    props: {
      quickLink: { id: 1, text: 'Stipendijos', link: '/stipendijos', lang: 'lt', icon: '', is_important: false },
      tenantOptions: [],
      categoryOptions: [{ id: 3, name: 'Parama', alias: 'parama' }],
      ...props,
    },
    global: { stubs: formStubs },
  });
}

describe('QuickLinkForm.vue', () => {
  let wrapper: ReturnType<typeof mount>;

  beforeEach(() => {
    vi.clearAllMocks();
    resolveUrlData.value = null;
  });

  afterEach(() => {
    wrapper?.unmount();
  });

  it('keeps the link field editable regardless of how it was filled', () => {
    wrapper = createWrapper();

    const linkInput = wrapper.find('#link');
    expect((linkInput.element as HTMLInputElement).disabled).toBeFalsy();
  });

  it('fills the link after confirming a picked target', async () => {
    wrapper = createWrapper();
    resolveUrlData.value = { url: '/lt/naujiena/nauja-stipendija' };

    await wrapper.find('[data-testid="confirm-picker"]').trigger('click');
    await wrapper.vm.$nextTick();

    expect(executeResolveUrl).toHaveBeenCalled();
    const linkInput = wrapper.find('#link');
    expect((linkInput.element as HTMLInputElement).value).toBe('/lt/naujiena/nauja-stipendija');
  });

  it('fills the link after picking a category', async () => {
    wrapper = createWrapper();
    resolveUrlData.value = { url: '/lt/kategorija/parama' };

    const categorySelect = wrapper.findAll('select').find(s => s.findAll('option').some(o => o.text() === 'Parama'));
    await categorySelect?.setValue('3');
    await wrapper.vm.$nextTick();

    expect(executeResolveUrl).toHaveBeenCalled();
    const linkInput = wrapper.find('#link');
    expect((linkInput.element as HTMLInputElement).value).toBe('/lt/kategorija/parama');
  });
});
