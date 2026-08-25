import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import type { z } from 'zod';

import RegistrationForm from '@/Features/Registrations/RegistrationForm.vue';

/**
 * The enum options only reach the DOM through reka-ui's teleported SelectContent, which jsdom
 * cannot open. The zod schema AutoForm receives carries the same resolved labels, so it is
 * asserted instead — the rendered dropdown text is intentionally left uncovered.
 */
const AutoFormStub = {
  name: 'AutoForm',
  props: ['form', 'schema', 'fieldConfig'],
  emits: ['submit'],
  template: '<div class="auto-form"><slot /></div>',
};

// Mirrors the member registration form: model-backed tenants, numeric course labels,
// and translated activity fields all live side by side.
const formFields = [
  {
    id: 4,
    label: 'Kur nori užregistruoti?',
    type: 'enum',
    is_required: true,
    options: [
      { value: 1, label: 'Vilniaus universiteto Studentų atstovybė Istorijos fakultete' },
      { value: 2, label: 'Vilniaus universiteto Studentų atstovybė Fizikos fakultete' },
    ],
  },
  {
    id: 5,
    label: 'Studijų kursas',
    type: 'enum',
    is_required: true,
    options: [
      { value: 1, label: { lt: 1, en: 1 } },
      { value: 2, label: { lt: 2, en: 2 } },
    ],
  },
  {
    id: 6,
    label: 'Labiausiai dominanti veiklos sritis',
    type: 'enum',
    is_required: true,
    options: [
      { value: 'Representation', label: { lt: 'Atstovavimo veikla', en: 'Representation' } },
      { value: 'Marketing', label: { lt: 'Marketingo sritis', en: 'Marketing' } },
    ],
  },
];

const createWrapper = (fields = formFields, props = {}) => mount(RegistrationForm, {
  props: {
    form: { id: 1, form_fields: fields },
    ...props,
  },
  global: {
    stubs: {
      AutoForm: AutoFormStub,
      AutoFormField: { template: '<div class="auto-form-field" />' },
    },
  },
});

const enumOptions = (wrapper: ReturnType<typeof mount>, fieldId: number): string[] => {
  const schema = wrapper.findComponent({ name: 'AutoForm' }).props('schema') as z.ZodObject<any>;

  return (schema.shape[`form-field-${fieldId}`] as z.ZodEnum<any>).options;
};

describe('RegistrationForm.vue', () => {
  it('labels model-backed options with their plain string label', () => {
    expect(enumOptions(createWrapper(), 4)).toEqual([
      'Vilniaus universiteto Studentų atstovybė Istorijos fakultete',
      'Vilniaus universiteto Studentų atstovybė Fizikos fakultete',
    ]);
  });

  it('labels numeric translations with the number, not "[object Object]"', () => {
    expect(enumOptions(createWrapper(), 5)).toEqual(['1', '2']);
  });

  it('labels translated options in the current locale', () => {
    expect(enumOptions(createWrapper(), 6)).toEqual(['Atstovavimo veikla', 'Marketingo sritis']);
  });

  it('resolves each field independently of the first field option shape', () => {
    const wrapper = createWrapper();

    expect(enumOptions(wrapper, 4)).not.toContain('undefined');
    expect(enumOptions(wrapper, 5)).not.toContain('[object Object]');
  });

  it('falls back to a string schema when a model-backed field has no options', () => {
    const wrapper = createWrapper([{ id: 9, label: 'Institucija', type: 'enum', is_required: true, options: [] }]);
    const schema = wrapper.findComponent({ name: 'AutoForm' }).props('schema') as z.ZodObject<any>;

    expect(schema.shape['form-field-9'].safeParse('anything').success).toBe(true);
  });

  it('maps every option shape back to its value on submit', async () => {
    const wrapper = createWrapper();

    await wrapper.findComponent({ name: 'AutoForm' }).vm.$emit('submit', {
      'form-field-4': 'Vilniaus universiteto Studentų atstovybė Fizikos fakultete',
      'form-field-5': '2',
      'form-field-6': 'Atstovavimo veikla',
    });

    expect(wrapper.emitted('submit')?.[0][0]).toEqual({
      4: { value: 2 },
      5: { value: 2 },
      6: { value: 'Representation' },
    });
  });

  it('prefills an enum field with the label of the given value', () => {
    const wrapper = createWrapper(formFields, {
      prefilledValues: { 4: { value: 1, hidden: true } },
    });
    const schema = wrapper.findComponent({ name: 'AutoForm' }).props('schema') as z.ZodObject<any>;

    expect(schema.shape['form-field-4'].parse(undefined)).toBe(
      'Vilniaus universiteto Studentų atstovybė Istorijos fakultete',
    );
  });
});
