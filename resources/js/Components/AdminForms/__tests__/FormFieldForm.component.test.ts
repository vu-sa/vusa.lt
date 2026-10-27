import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { flushPromises, mount } from '@vue/test-utils';
import { useForm } from '@inertiajs/vue3';

import FormFieldForm from '../FormFieldForm.vue';

const real = await vi.importActual<{ useForm: typeof useForm }>('@inertiajs/vue3');
const wrappers: ReturnType<typeof mount>[] = [];

function createWrapper(overrides = {}, hasRegistrations = false) {
  const wrapper = mount(FormFieldForm, {
    props: {
      formField: {
        id: 'new-1', type: 'string', subtype: null, label: { lt: 'Klausimas', en: 'Question' },
        description: { lt: '', en: '' }, options: [], is_required: false,
        use_model_options: false, option_source: null, default_value: { lt: '', en: '' }, placeholder: { lt: '', en: '' },
        ...overrides,
      },
      hasRegistrations,
      fieldModels: [{ value: 'tenant', label: 'Padaliniai' }, { value: 'institution', label: 'Institucijos' }],
    },
    global: { stubs: { MultiLocaleTiptapFormItem: { template: '<div />' } } },
  });
  wrappers.push(wrapper);
  return wrapper;
}

beforeEach(() => vi.mocked(useForm).mockImplementation(real.useForm));
afterEach(() => {
  wrappers.splice(0).forEach(wrapper => wrapper.unmount());
  vi.restoreAllMocks();
});

describe('form field native selects', () => {
  it('edits the field type and nullable subtype through native controls', async () => {
    const wrapper = createWrapper();
    await wrapper.get('select#subtype').setValue('email');
    const submit = wrapper.findAll('button').find(button => button.text().includes('Pateikti'))!;
    await submit.trigger('click');
    expect(wrapper.emitted('submit')?.[0][0]).toMatchObject({ type: 'string', subtype: 'email' });
    await wrapper.get('select#subtype').setValue('');
    await submit.trigger('click');
    expect(wrapper.emitted('submit')?.[1][0]).toMatchObject({ subtype: null });
    await wrapper.get('select#type').setValue('number');
    await flushPromises();
    expect(wrapper.find('select#subtype').exists()).toBe(false);
  });

  it('edits model option sources with a native select', async () => {
    const wrapper = createWrapper({ type: 'enum', use_model_options: true });
    await wrapper.find('button[aria-expanded]').trigger('click');
    await wrapper.get('select#option_source').setValue('institution');
    const submit = wrapper.findAll('button').find(button => button.text().includes('Pateikti'))!;
    await submit.trigger('click');
    expect(wrapper.emitted('submit')?.[0][0]).toMatchObject({ option_source: 'institution' });
  });

  it('keeps field types locked once registrations exist', () => {
    const wrapper = createWrapper({}, true);
    expect(wrapper.get('select#type').attributes('disabled')).toBeDefined();
    expect(wrapper.get('select#subtype').attributes('disabled')).toBeDefined();
  });
});
