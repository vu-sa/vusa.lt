import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { ref } from 'vue';
import type * as Inertia from '@inertiajs/vue3';

import QuickLinkForm from '@/Components/AdminForms/QuickLinkForm.vue';

// Real useForm (reactive) — the global inertia mock's useForm returns a plain object,
// which would silently break `form.link` reactivity in the template.
vi.mock('@inertiajs/vue3', async () => {
  const actual = await vi.importActual<typeof Inertia>('@inertiajs/vue3');
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
  FormPage: {
    props: ['title', 'barTitle', 'entityType', 'backHref', 'backLabel', 'processing', 'dirty', 'errors', 'fieldIds', 'mode'],
    template: '<div data-testid="form-page"><form @submit.prevent><slot /><slot name="aside" /><slot name="danger-zone" /></form></div>',
  },
  FormPanel: { template: '<div data-testid="form-panel"><slot /></div>' },
  FormToggleRow: {
    props: ['modelValue', 'label', 'hint'],
    template: '<button type="button" data-testid="toggle-important" @click="$emit(\'update:modelValue\', !modelValue)">{{ label }}</button>',
  },
  FormSegmentedControl: {
    props: ['modelValue', 'options'],
    template: '<div data-testid="segmented-control"><button v-for="opt in options" :key="opt.value" :data-testid="\'lang-\' + opt.value" @click="$emit(\'update:modelValue\', opt.value)">{{ opt.label }}</button></div>',
  },
  FormFieldWrapper: {
    props: ['id', 'label', 'required', 'error', 'hint'],
    template: '<div><slot /><span v-if="error" class="field-error">{{ error }}</span></div>',
  },
  Input: { props: ['modelValue'], template: '<input :value="modelValue" @input="$emit(\'update:modelValue\', $event.target.value)">' },
  SingleSelect: { template: '<div />' },
  Suspense: { template: '<div><slot /></div>' },
  FluentIconSelect: { template: '<div />' },
  LocaleFlag: { template: '<span />' },
  StatusBadge: { template: '<span />' },
  ConfirmDialog: {
    props: ['open'],
    template: '<div v-if="open"><button data-testid="confirm-delete" @click="$emit(\'confirm\')">Confirm</button></div>',
  },
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
      topicOptions: [{ id: 3, name: 'Parama', alias: 'parama' }],
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

  it('fills the link after picking a topic', async () => {
    wrapper = createWrapper();
    resolveUrlData.value = { url: '/lt/tema/parama' };

    const topicSelect = wrapper.findAll('select').find(s => s.findAll('option').some(o => o.text() === 'Parama'));
    await topicSelect?.setValue('3');
    await wrapper.vm.$nextTick();

    expect(executeResolveUrl).toHaveBeenCalled();
    const linkInput = wrapper.find('#link');
    expect((linkInput.element as HTMLInputElement).value).toBe('/lt/tema/parama');
  });

  it('updates lang and toggle important in aside', async () => {
    wrapper = createWrapper();
    const vm = wrapper.vm as unknown as { form: { lang: string; is_important: boolean } };

    expect(vm.form.lang).toBe('lt');
    await wrapper.find('[data-testid="lang-en"]').trigger('click');
    expect(vm.form.lang).toBe('en');

    expect(vm.form.is_important).toBe(false);
    await wrapper.find('[data-testid="toggle-important"]').trigger('click');
    expect(vm.form.is_important).toBe(true);
  });

  it('emits delete when confirm delete is clicked', async () => {
    wrapper = createWrapper({ enableDelete: true, quickLink: { id: 5, text: 'Nuoroda', link: 'https://vu.lt' } });

    // Click delete button to open confirm dialog
    const deleteBtn = wrapper.find('button.border-destructive\\/40');
    expect(deleteBtn.exists()).toBe(true);
    await deleteBtn.trigger('click');

    // Click confirm in dialog
    const confirmBtn = wrapper.find('[data-testid="confirm-delete"]');
    expect(confirmBtn.exists()).toBe(true);
    await confirmBtn.trigger('click');

    expect(wrapper.emitted('delete')).toBeTruthy();
  });
});
