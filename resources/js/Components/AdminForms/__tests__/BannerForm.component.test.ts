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
          AdminForm: {
            template: '<form @submit.prevent><slot /></form>',
            props: ['model'],
          },
          FormElement: {
            template: '<section><slot /></section>',
          },
          FormFieldWrapper: {
            template: '<div><label>{{ label }}</label><slot /></div>',
            props: ['id', 'label', 'required'],
          },
          Input: {
            template: '<input data-testid="input" :value="modelValue" @input="$emit(\'update:modelValue\', $event.target.value)" />',
            props: ['modelValue', 'type'],
          },
          ImageUpload: {
            template: '<div data-testid="image-upload" />',
            props: ['url', 'mode', 'folder', 'cropper', 'existingUrl'],
          },
          // Mirrors the real reka-ui Switch: binds modelValue, emits update:modelValue.
          // This is exactly what the :checked anti-pattern failed to honour.
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

  it('renders the form', () => {
    wrapper = createWrapper();
    expect(wrapper.find('form').exists()).toBe(true);
  });

  it('reflects the initial is_active state on the switch', () => {
    wrapper = createWrapper({ banner: { ...defaultBanner, is_active: 1 } });
    const toggle = wrapper.find('[role="switch"]');
    expect(toggle.attributes('aria-checked')).toBe('true');
  });

  it('toggles form.is_active when the switch is clicked (model-value binding)', async () => {
    wrapper = createWrapper();
    const vm = wrapper.vm as any;
    expect(vm.form.is_active).toBe(0);

    await wrapper.find('[role="switch"]').trigger('click');
    expect(vm.form.is_active).toBe(1);

    await wrapper.find('[role="switch"]').trigger('click');
    expect(vm.form.is_active).toBe(0);
  });
});
