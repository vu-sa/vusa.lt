import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { nextTick } from 'vue';

import ResourceForm from '@/Components/AdminForms/ResourceForm.vue';
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

describe('ResourceForm.vue', () => {
  let wrapper: ReturnType<typeof mount>;

  const defaultResource = {
    id: 'resource-1',
    name: { lt: 'Testinis išteklius', en: 'Test resource' },
    description: { lt: 'Aprašymas', en: 'Description' },
    identifier: 'PRJ-01',
    location: 'Naugarduko g. 1',
    capacity: 5,
    is_reservable: true,
    tenant_id: 1,
    resource_category_id: null,
    media: [
      { id: 10, name: 'photo.jpg', type: 'image/jpeg', status: 'finished', url: 'https://example.com/photo.jpg' },
    ],
  };

  const assignableTenants = [{ id: 1, shortname: 'VU SA' }];

  const createWrapper = (props = {}) => {
    return mount(ResourceForm, {
      props: {
        resource: defaultResource,
        categories: [],
        assignableTenants,
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
            props: ['icon'],
          },
          FormFieldWrapper: {
            template: '<div><label>{{ label }}</label><slot /></div>',
            props: ['id', 'label', 'required', 'error'],
          },
          MultiLocaleInput: {
            template: '<input data-testid="multi-locale" />',
            props: ['input', 'inputType', 'placeholder'],
          },
          Select: {
            template: '<select data-testid="select" :value="modelValue" @change="$emit(\'update:modelValue\', $event.target.value)"><slot /></select>',
            props: ['modelValue'],
          },
          SelectTrigger: { template: '<div><slot /></div>' },
          SelectValue: { template: '<span>{{ placeholder }}</span>', props: ['placeholder'] },
          SelectContent: { template: '<div><slot /></div>' },
          SelectItem: {
            template: '<option :value="value"><slot /></option>',
            props: ['value'],
          },
          Input: {
            template: '<input data-testid="input" :value="modelValue" @input="$emit(\'update:modelValue\', $event.target.value)" />',
            props: ['modelValue'],
          },
          NumberField: {
            template: '<input data-testid="number-field" type="number" :value="modelValue" @input="$emit(\'update:modelValue\', Number($event.target.value))" />',
            props: ['modelValue', 'min'],
          },
          Switch: {
            template: '<button type="button" role="switch" :aria-checked="modelValue" @click="$emit(\'update:modelValue\', !modelValue)" />',
            props: ['modelValue'],
          },
          ImageUpload: {
            template: `
              <div data-testid="image-upload">
                <button type="button" data-testid="add-file" @click="emitAdd" />
                <button type="button" data-testid="remove-existing" @click="$emit('remove:existing', existingUrls[0])" />
              </div>
            `,
            props: ['files', 'max', 'mode', 'folder', 'accept', 'existingUrls'],
            methods: {
              emitAdd() {
                this.$emit('update:files', [new File(['x'], 'new-photo.jpg', { type: 'image/jpeg' })]);
              },
            },
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

  describe('is_reservable switch', () => {
    it('reflects a truthy initial state', () => {
      wrapper = createWrapper({ resource: { ...defaultResource, is_reservable: true } });
      const toggle = wrapper.find('[role="switch"]');
      expect(toggle.attributes('aria-checked')).toBe('true');
    });

    it('reflects a falsy initial state', () => {
      wrapper = createWrapper({ resource: { ...defaultResource, is_reservable: false } });
      const toggle = wrapper.find('[role="switch"]');
      expect(toggle.attributes('aria-checked')).toBe('false');
    });

    it('toggles form.is_reservable as a boolean when clicked', async () => {
      wrapper = createWrapper({ resource: { ...defaultResource, is_reservable: true } });
      const vm = wrapper.vm as any;

      await wrapper.find('[role="switch"]').trigger('click');
      expect(vm.form.is_reservable).toBe(false);

      await wrapper.find('[role="switch"]').trigger('click');
      expect(vm.form.is_reservable).toBe(true);
    });
  });

  describe('media', () => {
    it('passes existing media to ImageUpload as existingUrls', () => {
      wrapper = createWrapper();
      const upload = wrapper.find('[data-testid="image-upload"]');
      expect(upload.exists()).toBe(true);
      const vm = wrapper.vm as any;
      expect(vm.existingMediaItems).toEqual([
        { id: 10, name: 'photo.jpg', type: 'image/jpeg', status: 'finished', url: 'https://example.com/photo.jpg' },
      ]);
    });

    it('adds newly uploaded files to form.media while retaining existing media', async () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;

      await wrapper.find('[data-testid="add-file"]').trigger('click');

      expect(vm.form.media).toHaveLength(2);
      expect(vm.form.media[0]).toMatchObject({ id: 10, status: 'finished' });
      expect(vm.form.media[1]).toMatchObject({ status: 'pending' });
      expect(vm.form.media[1].file).toBeInstanceOf(File);
    });

    it('removes an existing media item from form.media on remove:existing', async () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;

      expect(vm.form.media).toHaveLength(1);

      await wrapper.find('[data-testid="remove-existing"]').trigger('click');
      await nextTick();

      expect(vm.form.media).toHaveLength(0);
    });
  });

  describe('identifier field', () => {
    it('binds the identifier input directly to form.identifier', async () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;
      expect(vm.form.identifier).toBe('PRJ-01');
    });
  });
});
