import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { flushPromises, mount } from '@vue/test-utils';
import { router as coreRouter } from '@inertiajs/core';
import { router, useForm, usePage, type InertiaPrecognitiveForm } from '@inertiajs/vue3';

import RegistrationForm from '../RegistrationForm.vue';
import RegistrationField from '../RegistrationField.vue';
import type { RegistrationFieldDefinition, RegistrationValue } from '../types';

import { Select } from '@/Components/ui/select';
import { commonStubs } from '@/tests/stubs';
import { createMockPage } from '@/tests/helpers/createMockPage';

const real = await vi.importActual<{ useForm: typeof useForm }>('@inertiajs/vue3');
type FormState = InertiaPrecognitiveForm<{ responses: Record<string, { value: RegistrationValue }> }>;
const fields: RegistrationFieldDefinition[] = [
  { id: 4, label: 'Padalinys', type: 'enum', is_required: true, options: [{ value: 0, label: 'Same label' }, { value: 2, label: 'Same label' }] },
  { id: 5, label: 'Kursas', type: 'number', is_required: true, default_value: 0 },
  { id: 6, label: 'Sritis', type: 'string', is_required: true, placeholder: 'Įrašyk atsakymą', description: '<p>Paaiškinimas</p>' },
  { id: 7, label: 'Sutinku', type: 'boolean', is_required: false, default_value: false },
  { id: 8, label: 'Data', type: 'date', is_required: false },
];
const wrappers: ReturnType<typeof mount>[] = [];
let form: FormState;
let validate: ReturnType<typeof vi.spyOn>;

function createWrapper(overrides: { prefilledValues?: Record<string, { value: string | number | boolean; hidden?: boolean }> } = {}, formFields = fields) {
  const wrapper = mount(RegistrationForm, {
    props: { form: { id: 1, name: 'Registration', form_fields: formFields }, submitUrl: '/registration/1', ...overrides },
    attachTo: document.body,
    global: { stubs: {
      ...commonStubs,
      ConfirmDialog: { props: ['open'], emits: ['confirm', 'update:open'], template: '<div v-if="open"><button data-testid="leave" @click="$emit(\'confirm\')">Leave</button><button data-testid="stay" @click="$emit(\'update:open\', false)">Stay</button></div>' },
    } },
  });
  wrappers.push(wrapper);
  form = vi.mocked(useForm).mock.results.at(-1)!.value as FormState;
  validate = vi.spyOn(form.validator(), 'validate').mockReturnValue(form.validator());
  return wrapper;
}

beforeEach(() => {
  vi.mocked(useForm).mockImplementation(real.useForm);
  vi.mocked(usePage).mockReturnValue(createMockPage({ app: { locale: 'lt' } }));
});
afterEach(() => {
  wrappers.splice(0).forEach(wrapper => wrapper.unmount());
  vi.restoreAllMocks();
});

describe('registration fields', () => {
  it('keeps numeric and false defaults and renders labels, descriptions and placeholders', () => {
    const wrapper = createWrapper();
    expect(form.responses['5'].value).toBe(0);
    expect(form.responses['7'].value).toBe(false);
    expect(wrapper.text()).toContain('Paaiškinimas');
    expect(wrapper.find('#registration-field-6').attributes('placeholder')).toBe('Įrašyk atsakymą');
  });

  it('keeps hidden prefills in the submission payload and binds options by raw value', async () => {
    const wrapper = createWrapper({ prefilledValues: { 4: { value: 0, hidden: true } } });
    expect(wrapper.find('[data-registration-field="4"]').exists()).toBe(false);
    const post = vi.spyOn(coreRouter, 'post').mockImplementation(() => {});
    await wrapper.find('form').trigger('submit');
    expect(post).toHaveBeenCalledWith('/registration/1', { data: {
      4: { value: 0 }, 5: { value: 0 }, 6: { value: null }, 7: { value: false }, 8: { value: null },
    } }, expect.any(Object));
  });

  it('validates the updated option value rather than its duplicate label', async () => {
    const wrapper = createWrapper();
    wrapper.findComponent(RegistrationField).findComponent(Select).vm.$emit('update:modelValue', 2);
    await flushPromises();
    expect(form.responses['4'].value).toBe(2);
    expect(validate).toHaveBeenCalledWith('data.4.value', 2);
  });

  it('validates typed values on blur and keeps dates independent of time zones', async () => {
    const wrapper = createWrapper();
    await wrapper.find('#registration-field-6').setValue('Jonas');
    expect(validate).not.toHaveBeenCalled();
    await wrapper.find('#registration-field-6').trigger('blur');
    await wrapper.find('#registration-field-8').setValue('2026-10-27');
    await wrapper.find('#registration-field-8').trigger('blur');
    expect(validate).toHaveBeenCalledWith('data.6.value', 'Jonas');
    expect(validate).toHaveBeenCalledWith('data.8.value', '2026-10-27');
  });

  it('shows server field errors with accessible associations', async () => {
    const wrapper = createWrapper();
    form.setErrors({ 'data.6.value': 'Reikia atsakymo' });
    await flushPromises();
    expect(wrapper.find('#registration-field-6').attributes('aria-invalid')).toBe('true');
    expect(wrapper.find('#registration-field-6').attributes('aria-describedby')).toContain('registration-field-6-error');
    expect(wrapper.find('#registration-field-6-error').text()).toBe('Reikia atsakymo');
  });
});

describe('submission and navigation', () => {
  function attemptVisit() {
    const visit = { id: 'old-visit', url: new URL('https://www.vusa.test/en'), method: 'get', prefetch: false, preserveScroll: true };
    const preventDefault = vi.fn();
    (router as unknown as { __triggerBefore: (event: unknown) => void }).__triggerBefore({ detail: { visit }, preventDefault });
    return { visit, preventDefault };
  }

  it('protects dirty answers and resumes navigation only after confirmation', async () => {
    const wrapper = createWrapper();
    await wrapper.find('#registration-field-6').setValue('Jonas');
    const { visit, preventDefault } = attemptVisit();
    await flushPromises();
    expect(preventDefault).toHaveBeenCalledOnce();
    await wrapper.find('[data-testid="stay"]').trigger('click');
    attemptVisit();
    await flushPromises();
    vi.mocked(router.visit).mockClear();
    await wrapper.find('[data-testid="leave"]').trigger('click');
    expect(router.visit).toHaveBeenCalledWith(visit.url, { method: 'get', prefetch: false, preserveScroll: true });
  });

  it('preserves answers and dirty state on flashed application errors and permits retry', async () => {
    const wrapper = createWrapper();
    await wrapper.find('#registration-field-6').setValue('Jonas');
    const post = vi.spyOn(coreRouter, 'post').mockImplementation(() => {});
    await wrapper.find('form').trigger('submit');
    const options = post.mock.calls[0][2]!;
    await options.onSuccess?.({ props: { flash: { error: 'Failed', success: null } } } as never);
    options.onFinish?.({} as never);
    await flushPromises();
    expect(form.isDirty).toBe(true);
    expect(form.responses['6'].value).toBe('Jonas');
    expect(attemptVisit().preventDefault).toHaveBeenCalledOnce();
    await wrapper.find('form').trigger('submit');
    expect(post).toHaveBeenCalledTimes(2);
  });

  it('keeps successful answers and clears dirty state', async () => {
    const wrapper = createWrapper();
    await wrapper.find('#registration-field-6').setValue('Jonas');
    const post = vi.spyOn(coreRouter, 'post').mockImplementation(() => {});
    await wrapper.find('form').trigger('submit');
    await post.mock.calls[0][2]!.onSuccess?.({ props: { flash: { success: 'Saved', error: null } } } as never);
    expect(form.isDirty).toBe(false);
    expect(form.responses['6'].value).toBe('Jonas');
  });

  it('disables duplicate submissions while processing', async () => {
    const wrapper = createWrapper();
    form.processing = true;
    const post = vi.spyOn(coreRouter, 'post').mockImplementation(() => {});
    await flushPromises();
    expect(wrapper.find('button[type="submit"]').attributes('disabled')).toBeDefined();
    await wrapper.find('form').trigger('submit');
    expect(post).not.toHaveBeenCalled();
  });
});
