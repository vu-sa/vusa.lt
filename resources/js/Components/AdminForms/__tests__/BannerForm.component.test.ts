import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { mount } from '@vue/test-utils';

import BannerForm from '@/Components/AdminForms/BannerForm.vue';
import { commonStubs } from '@/tests/stubs';

vi.mock('@inertiajs/vue3', async () => {
  const actual = await vi.importActual('@inertiajs/vue3');
  return {
    ...actual,
    usePage: () => ({
      props: {
        app: { locale: 'lt' },
      },
    }),
  };
});

describe('BannerForm.vue', () => {
  let wrapper: ReturnType<typeof mount>;

  const defaultBanner = {
    title: 'Testinis baneris',
    link_url: 'https://vu.lt',
    is_active: 0,
    image_url: '',
  };

  const createWrapper = (props = {}) => {
    return mount(BannerForm, {
      props: {
        banner: defaultBanner,
        ...props,
      },
      global: {
        stubs: {
          ...commonStubs,
          FormPage: {
            template: '<div data-testid="form-page"><form @submit.prevent><slot name="title-status" /><slot name="header-actions" /><slot /><slot name="aside" /><slot name="danger-zone" /></form></div>',
            props: ['title', 'barTitle', 'backHref', 'backLabel', 'processing', 'dirty', 'errors', 'fieldIds', 'mode', 'entityType'],
          },
          FormFieldWrapper: {
            template: '<div><label>{{ label }}</label><slot /></div>',
            props: ['id', 'label', 'required', 'hint', 'error'],
          },
          Input: {
            template: '<input data-testid="input" :value="modelValue" @input="$emit(\'update:modelValue\', $event.target.value)" />',
            props: ['modelValue', 'type'],
          },
          ImageUpload: {
            template: '<div data-testid="image-upload" />',
            props: ['url', 'mode', 'folder', 'cropper', 'existingUrl', 'fullWidth'],
          },
          ConfirmDialog: {
            props: ['open'],
            template: '<div v-if="open"><button data-testid="confirm-delete" @click="$emit(\'confirm\')">Confirm</button></div>',
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

  it('renders the form', () => {
    wrapper = createWrapper();
    expect(wrapper.find('form').exists()).toBe(true);
  });

  it('reflects the initial is_active state in ContentPublishPanel', () => {
    wrapper = createWrapper({ banner: { ...defaultBanner, is_active: 1 } });
    const vm = wrapper.vm as unknown as { form: { is_active: number } };
    expect(vm.form.is_active).toBe(1);
    expect(wrapper.find('[data-testid="banner-status-published"]').exists()).toBe(true);
  });

  it('toggles form.is_active when published status is clicked', async () => {
    wrapper = createWrapper();
    const vm = wrapper.vm as unknown as { form: { is_active: number } };
    expect(vm.form.is_active).toBe(0);

    await wrapper.find('[data-testid="banner-status-published"]').trigger('click');
    expect(vm.form.is_active).toBe(1);

    await wrapper.find('[data-testid="banner-status-draft"]').trigger('click');
    expect(vm.form.is_active).toBe(0);
  });

  it('emits delete when confirm delete is clicked', async () => {
    wrapper = createWrapper({ enableDelete: true, banner: { ...defaultBanner, id: 10 } });

    const deleteBtn = wrapper.find('button.border-destructive\\/40');
    expect(deleteBtn.exists()).toBe(true);
    await deleteBtn.trigger('click');

    const confirmBtn = wrapper.find('[data-testid="confirm-delete"]');
    expect(confirmBtn.exists()).toBe(true);
    await confirmBtn.trigger('click');

    expect(wrapper.emitted('delete')).toBeTruthy();
  });
});
