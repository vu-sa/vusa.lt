import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { nextTick } from 'vue';

import FormForm from '@/Components/AdminForms/FormForm.vue';

describe('FormForm.vue', () => {
  let wrapper: ReturnType<typeof mount>;

  const defaultProps = {
    form: {
      id: 1,
      name: { lt: 'Test forma', en: 'Test Form' },
      description: { lt: 'Aprašymas', en: 'Description' },
      path: { lt: 'testas', en: 'test' },
      tenant_id: 1,
      publish_time: null,
      registrations_count: 0,
      form_fields: [],
    },
    assignableTenants: [
      { id: 1, shortname: 'VU SA' },
      { id: 2, shortname: 'VU IF' },
    ],
    fieldModelOptions: [
      { value: 'App\\Models\\User', label: 'User' },
    ],
    fieldModelFields: [
      { value: 'name', label: 'Name' },
    ],
  };

  const createWrapper = (props = {}) => {
    return mount(FormForm, {
      props: {
        ...defaultProps,
        ...props,
      },
      global: {
        stubs: {
          // Dialog components: stubbed due to focus/teleport behavior in jsdom
          CardModal: {
            template: '<div v-if="show" class="card-modal"><slot /></div>',
            props: ['show'],
            emits: ['update:show', 'close'],
          },
          // Complex third-party components that may break in jsdom
          TiptapEditor: {
            template: '<textarea :value="modelValue" @input="$emit(\'update:modelValue\', $event.target.value)"></textarea>',
            props: ['modelValue'],
          },
          SortableFormFieldsTable: {
            template: '<div class="sortable-table"><div v-for="field in modelValue" :key="field.id" class="field-row"><slot :model="field" /></div></div>',
            props: ['modelValue'],
            emits: ['update:modelValue'],
          },
          FormFieldForm: {
            template: '<form @submit.prevent="$emit(\'submit\', { id: \'new-id\', type: \'string\', label: { lt: \'New\' } })"><button type="submit">Submit Field</button></form>',
          },
          // Icons: stubbed to reduce noise and avoid auto-import complexity
          IFluentAdd24Filled: {
            template: '<span class="icon-add" />',
          },
          IFluentEdit24Filled: {
            template: '<span class="icon-edit" />',
          },
          IFluentDelete24Filled: {
            template: '<span class="icon-delete" />',
          },
          IFluentTextT24Regular: {
            template: '<span class="icon-text" />',
          },
          IFluentTextAsterisk20Filled: {
            template: '<span class="icon-asterisk" />',
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

  describe('rendering', () => {
    it('renders form with name input', () => {
      wrapper = createWrapper();

      expect(wrapper.find('section').exists()).toBe(true);
      expect(wrapper.find('input').exists()).toBe(true);
    });

    it('renders tenant select when assignable tenants exist', () => {
      wrapper = createWrapper();

      // shadcn Select renders as a button trigger, not a native <select>
      const selectTriggers = wrapper.findAll('[data-slot="select-trigger"]');
      expect(selectTriggers.length).toBeGreaterThan(0);
    });

    it('renders form fields table', () => {
      wrapper = createWrapper();

      expect(wrapper.find('.sortable-table').exists()).toBe(true);
    });

    it('displays registration count', () => {
      wrapper = createWrapper();

      // $t is mocked to return the key, so assert the i18n key renders.
      expect(wrapper.text()).toContain('forms.helpers.registrations_count');
    });
  });

  describe('form state', () => {
    it('uses useForm internally (form has inertia methods)', () => {
      wrapper = createWrapper();

      const vm = wrapper.vm as any;
      expect(vm.form).toBeDefined();
      expect(typeof vm.form.patch).toBe('function');
      expect(typeof vm.form.post).toBe('function');
      expect(typeof vm.form.data).toBe('function');
    });

    it('form data is reactive and mutable', async () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;

      expect(vm.form.name.lt).toBe('Test forma');

      vm.form.name.lt = 'Changed name';
      await nextTick();

      expect(vm.form.name.lt).toBe('Changed name');
    });
  });

  describe('form fields', () => {
    it('opens modal when add button is clicked', async () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;

      const addButton = wrapper.findAll('button').find(b => b.text().includes('forms.add'));
      expect(addButton).toBeDefined();

      await addButton!.trigger('click');
      await nextTick();

      expect(vm.showFormFieldModal).toBe(true);
      expect(wrapper.find('.card-modal').exists()).toBe(true);
    });

    it('does not allow adding fields when there are registrations', async () => {
      const formWithRegistrations = {
        ...defaultProps.form,
        registrations_count: 5,
      };
      wrapper = createWrapper({ form: formWithRegistrations });

      const addButton = wrapper.findAll('button').find(b => b.text().includes('forms.add'));
      expect(addButton!.attributes('disabled')).toBeDefined();
    });

    it('renders existing form fields in the table', () => {
      const formWithFields = {
        ...defaultProps.form,
        form_fields: [
          { id: 'field-1', type: 'string', label: { lt: 'Field 1' }, order: 1, is_required: false },
        ],
      };
      wrapper = createWrapper({ form: formWithFields });

      expect(wrapper.findAll('.field-row')).toHaveLength(1);
      expect(wrapper.text()).toContain('Field 1');
    });

    it('removes a form field when handleDeleteFormField is called', async () => {
      const formWithFields = {
        ...defaultProps.form,
        form_fields: [
          { id: 'field-1', type: 'string', label: { lt: 'Field 1' }, order: 1, is_required: false },
        ],
      };
      wrapper = createWrapper({ form: formWithFields });
      const vm = wrapper.vm as any;

      expect(vm.form.form_fields).toHaveLength(1);

      // Call the component method directly (DOM event bubbling through stubs is unreliable)
      vm.handleDeleteFormField({ id: 'field-1' });
      await nextTick();

      expect(vm.form.form_fields).toHaveLength(0);
    });
  });

  describe('emits', () => {
    it('forwards submit:form event from AdminForm', async () => {
      wrapper = createWrapper();
      const vm = wrapper.vm as any;

      const adminForm = wrapper.findComponent({ name: 'AdminForm' });
      expect(adminForm.exists()).toBe(true);
      await adminForm.vm.$emit('submit:form');

      expect(wrapper.emitted('submit:form')).toBeTruthy();
      expect(wrapper.emitted('submit:form')?.[0]?.[0]).toBe(vm.form);
    });

    it('forwards delete event from AdminForm', async () => {
      wrapper = createWrapper();

      const adminForm = wrapper.findComponent({ name: 'AdminForm' });
      await adminForm.vm.$emit('delete');

      expect(wrapper.emitted('delete')).toBeTruthy();
    });
  });
});
