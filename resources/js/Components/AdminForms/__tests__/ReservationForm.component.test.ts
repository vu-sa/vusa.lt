import { afterEach, describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { router } from '@inertiajs/vue3';

import ReservationForm from '@/Components/AdminForms/ReservationForm.vue';
import { commonStubs } from '@/tests/stubs';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

vi.stubGlobal('route', (name?: string) => (name === undefined ? { current: () => false } : `/mocked/${name}`));

/** FormPage is stubbed so the test can read whether saving is allowed and fire its submit. */
const stubs = {
  ...commonStubs,
  FormPage: {
    props: ['title', 'mode', 'disabled', 'saveLabel'],
    emits: ['submit'],
    template: '<form data-testid="form-page" :data-disabled="disabled" :data-mode="mode" @submit.prevent="$emit(\'submit\')"><slot /></form>',
  },
  FormSection: { props: ['title'], template: '<section :data-section="title"><slot /></section>' },
  DatePicker: {
    props: ['modelValue'],
    emits: ['update:modelValue'],
    template: '<input class="day" @input="$emit(\'update:modelValue\', new Date($event.target.value))" />',
  },
  TimePicker: {
    props: ['modelValue'],
    emits: ['update:modelValue'],
    template: '<input class="time" @input="$emit(\'update:modelValue\', { hour: Number($event.target.value.split(\':\')[0]), minute: Number($event.target.value.split(\':\')[1]) })" />',
  },
  ResourceSelectDialog: { template: '<div><slot name="trigger" /></div>' },
  MdSuspenseWrapper: true,
  EntityTypeMark: true,
  Checkbox: {
    props: ['modelValue'],
    emits: ['update:modelValue'],
    template: '<input id="condition" type="checkbox" :checked="modelValue" @change="$emit(\'update:modelValue\', $event.target.checked)" />',
  },
  NumberField: true,
};

const reservation = {
  id: undefined,
  name: '',
  description: '',
  start_time: null,
  end_time: null,
  resources: [],
};

const mountForm = (props: Record<string, unknown> = {}) =>
  mount(ReservationForm, {
    props: { reservation, allResources: [], modelRoute: 'reservations.store', rememberKey: 'CreateReservation', ...props } as never,
    global: { stubs },
  });

const setPeriod = async (wrapper: ReturnType<typeof mount>) => {
  const days = wrapper.findAll('input.day');
  const times = wrapper.findAll('input.time');

  await days[0].setValue('2026-09-21');
  await times[0].setValue('09:00');
  await days[1].setValue('2026-09-22');
  await times[1].setValue('17:15');
};

describe('ReservationForm.vue', () => {
  let wrapper: ReturnType<typeof mount>;

  afterEach(() => {
    wrapper?.unmount();
    vi.mocked(router.reload).mockClear?.();
  });

  it('is always a create form', () => {
    wrapper = mountForm();

    expect(wrapper.find('[data-testid="form-page"]').attributes('data-mode')).toBe('create');
  });

  it('asks for the period as a date and a time each, never a combined field', () => {
    wrapper = mountForm();

    expect(wrapper.findAll('input.day')).toHaveLength(2);
    expect(wrapper.findAll('input.time')).toHaveLength(2);
  });

  it('cannot be submitted until the user has acknowledged the terms', async () => {
    wrapper = mountForm();
    expect(wrapper.find('[data-testid="form-page"]').attributes('data-disabled')).toBe('true');

    await wrapper.find('#condition').setValue(true);

    expect(wrapper.find('[data-testid="form-page"]').attributes('data-disabled')).toBe('false');
  });

  it('composes the start and end timestamps from the date and time fields', async () => {
    wrapper = mountForm();

    await setPeriod(wrapper);

    const { form } = wrapper.vm as unknown as { form: { start_time: number; end_time: number } };

    expect(new Date(form.start_time).getHours()).toBe(9);
    expect(new Date(form.start_time).getDate()).toBe(21);
    expect(new Date(form.end_time).getHours()).toBe(17);
    expect(new Date(form.end_time).getMinutes()).toBe(15);
    expect(form.end_time).toBeGreaterThan(form.start_time);
  });

  it('reloads the capacities once a valid period exists, and drops resources picked for the old one', async () => {
    wrapper = mountForm();
    const { form } = wrapper.vm as unknown as { form: { resources: unknown[] } };
    form.resources.push({ id: 'r1', quantity: 1 });

    await setPeriod(wrapper);

    expect(router.reload).toHaveBeenCalledWith(expect.objectContaining({ only: ['resources'] }));
    expect(form.resources).toEqual([]);
  });

  it('does not reload for an end before the start', async () => {
    wrapper = mountForm();
    const days = wrapper.findAll('input.day');
    const times = wrapper.findAll('input.time');

    await days[0].setValue('2026-09-22');
    await times[0].setValue('09:00');
    await days[1].setValue('2026-09-21');
    await times[1].setValue('09:00');

    expect(router.reload).not.toHaveBeenCalled();
  });
});
