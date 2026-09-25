import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { router, usePage } from '@inertiajs/vue3';

import ReservationForm from '@/Components/AdminForms/ReservationForm.vue';
import type { ReservationCart, ReservationCartItem } from '@/Components/Reservations/types';
import { createMockPage } from '@/tests/helpers/createMockPage';
import { commonStubs } from '@/tests/stubs';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

vi.stubGlobal('route', (name?: string, param?: string) => (name === undefined ? { current: () => false } : `/mocked/${name}${param ? `/${param}` : ''}`));

/** FormPage is stubbed so the test can read whether saving is allowed and fire its submit. */
const stubs = {
  ...commonStubs,
  FormPage: {
    props: ['title', 'mode', 'disabled', 'saveLabel'],
    emits: ['submit'],
    template: '<form data-testid="form-page" :data-disabled="disabled" :data-mode="mode" @submit.prevent="$emit(\'submit\')"><slot name="title-status" /><slot /><slot name="aside" /><slot name="danger-zone" /></form>',
  },
  FormFieldWrapper: { props: ['id', 'label'], template: '<div :data-field="id"><slot /></div>' },
  FormPanel: { props: ['title'], template: '<section :data-panel="title"><slot /></section>' },
  // Local "YYYY-MM-DDTHH:mm" in, a Date out — what the popover hands back once a day and time are picked.
  DateTimePicker: {
    props: ['modelValue', 'variant'],
    emits: ['update:modelValue'],
    template: '<input class="datetime" :data-variant="variant" @input="$emit(\'update:modelValue\', new Date($event.target.value))" />',
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

const item = (overrides: Partial<ReservationCartItem> = {}): ReservationCartItem => ({
  id: 1,
  resource_id: 'r1',
  name: 'Projektorius',
  tenant_shortname: 'VU SA',
  image_url: null,
  capacity: 3,
  quantity: 2,
  available: 3,
  problem: null,
  ...overrides,
});

const cartWith = (items: ReservationCartItem[], overrides: Partial<ReservationCart> = {}): ReservationCart => ({
  name: 'Renginys',
  description: 'Reikia renginiui',
  start_time: new Date(2026, 9, 2, 9, 0).getTime(),
  end_time: new Date(2026, 9, 4, 17, 0).getTime(),
  count: items.length,
  problemCount: items.filter(line => line.problem).length,
  expiresAt: null,
  ttlDays: 14,
  items,
  ...overrides,
});

type FormSpies = { form: { setError: ReturnType<typeof vi.fn>; post: ReturnType<typeof vi.fn> } };

const defaultPeriod = { start: new Date(2026, 9, 10, 9, 0).getTime(), end: new Date(2026, 9, 12, 17, 0).getTime() };

const mountForm = (cart: ReservationCart | null) => {
  vi.mocked(usePage).mockReturnValue(createMockPage({ reservationCart: cart }) as never);

  return mount(ReservationForm, {
    props: { cart, defaultPeriod },
    global: { stubs },
  });
};

describe('ReservationForm.vue', () => {
  let wrapper: ReturnType<typeof mount>;

  beforeEach(() => {
    vi.mocked(router.put).mockClear();
    vi.mocked(router.visit).mockClear();
    vi.mocked(router.delete).mockClear();
  });

  afterEach(() => {
    wrapper?.unmount();
  });

  it('is always a create form', () => {
    wrapper = mountForm(cartWith([item()]));

    expect(wrapper.find('[data-testid="form-page"]').attributes('data-mode')).toBe('create');
  });

  it('asks for the start and the end with the date-time popover', () => {
    wrapper = mountForm(cartWith([item()]));

    const pickers = wrapper.findAll('input.datetime');

    expect(pickers).toHaveLength(2);
    expect(pickers.every(picker => picker.attributes('data-variant') === 'popover')).toBe(true);
  });

  it('starts from the cart: its texts and its resources', () => {
    wrapper = mountForm(cartWith([item()]));

    const { form } = wrapper.vm as unknown as { form: { name: string; description: string } };

    expect(form.name).toBe('Renginys');
    expect(form.description).toBe('Reikia renginiui');
    expect(wrapper.findAll('[data-slot="reservation-cart-item"]')).toHaveLength(1);
  });

  it('keeps the submit button active and says what is missing on press', async () => {
    wrapper = mountForm(null);
    expect(wrapper.find('[data-testid="form-page"]').attributes('data-disabled')).toBeUndefined();

    await wrapper.find('[data-testid="form-page"]').trigger('submit');

    const { form } = wrapper.vm as unknown as FormSpies;
    expect(form.setError).toHaveBeenCalledWith({
      name: 'reservations.cart.name_required',
      description: 'reservations.cart.description_required',
      resources: 'reservations.cart.resources_required',
      condition: 'reservations.cart.terms_required',
    });
    expect(form.post).not.toHaveBeenCalled();
  });

  it('does not submit until the terms are acknowledged', async () => {
    wrapper = mountForm(cartWith([item()]));

    await wrapper.find('[data-testid="form-page"]').trigger('submit');

    const { form } = wrapper.vm as unknown as FormSpies;
    expect(form.setError).toHaveBeenCalledWith({ condition: 'reservations.cart.terms_required' });
    expect(form.post).not.toHaveBeenCalled();
  });

  it('does not submit while an item no longer fits, and says why', async () => {
    wrapper = mountForm(cartWith([item({ problem: 'unavailable', available: 1 })]));

    await wrapper.find('#condition').setValue(true);
    await wrapper.find('[data-testid="form-page"]').trigger('submit');

    const { form } = wrapper.vm as unknown as FormSpies;
    expect(form.setError).toHaveBeenCalledWith({ resources: 'reservations.cart.fix_before_submit' });
    expect(form.post).not.toHaveBeenCalled();
    expect(wrapper.find('[data-testid="reservation-cart-conflicts"]').exists()).toBe(true);
  });

  it('saves a new period to the cart and keeps the resources', async () => {
    wrapper = mountForm(cartWith([item()]));
    const pickers = wrapper.findAll('input.datetime');

    await pickers[0]!.setValue('2026-10-05T10:00');
    await pickers[1]!.setValue('2026-10-06T16:00');

    expect(router.put).toHaveBeenLastCalledWith(
      '/mocked/reservationCart.update',
      expect.objectContaining({ start_time: new Date(2026, 9, 5, 10, 0).getTime(), end_time: new Date(2026, 9, 6, 16, 0).getTime() }),
      expect.objectContaining({ only: ['reservationCart'] }),
    );
    expect(wrapper.findAll('[data-slot="reservation-cart-item"]')).toHaveLength(1);
  });

  it('stores the shown default period when the cart has none yet', () => {
    wrapper = mountForm(cartWith([item()], { start_time: null, end_time: null }));

    expect(router.put).toHaveBeenCalledWith(
      '/mocked/reservationCart.update',
      { start_time: defaultPeriod.start, end_time: defaultPeriod.end },
      expect.anything(),
    );
  });

  it('submits the cart lines as the reservation resources', async () => {
    wrapper = mountForm(cartWith([item(), item({ id: 2, resource_id: 'r2', quantity: 1 })]));

    await wrapper.find('#condition').setValue(true);
    await wrapper.find('[data-testid="form-page"]').trigger('submit');

    const { form } = wrapper.vm as unknown as { form: { transform: ReturnType<typeof vi.fn>; post: ReturnType<typeof vi.fn> } };
    const transform = form.transform.mock.calls[0]![0] as (data: Record<string, unknown>) => Record<string, unknown>;

    expect(transform({ name: 'Renginys' }).resources).toEqual([
      { id: 'r1', quantity: 2 },
      { id: 'r2', quantity: 1 },
    ]);
    expect(form.post).toHaveBeenCalledWith('/mocked/reservations.store', expect.anything());
  });

  it('tells the user the reservation is saved as a draft', () => {
    wrapper = mountForm(cartWith([item()]));

    expect(wrapper.find('[data-testid="reservation-draft-intro"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="reservation-draft-status"]').exists()).toBe(true);
  });

  it('claims no saved draft before anything is saved', () => {
    wrapper = mountForm(null);

    expect(wrapper.find('[data-testid="reservation-draft-status"]').exists()).toBe(false);
  });

  it('saves what was typed before leaving for the resource list', async () => {
    wrapper = mountForm(null);
    const { form } = wrapper.vm as unknown as { form: { name: string } };
    form.name = 'Stovykla';

    await wrapper.find('[data-testid="reservation-browse-resources"]').trigger('click');

    expect(router.put).toHaveBeenCalledWith(
      '/mocked/reservationCart.update',
      { name: 'Stovykla', description: null },
      expect.objectContaining({ onSuccess: expect.any(Function) }),
    );
    expect(router.visit).not.toHaveBeenCalled();

    const options = vi.mocked(router.put).mock.calls.at(-1)![2] as { onSuccess: () => void };
    options.onSuccess();

    expect(router.visit).toHaveBeenCalledWith('/mocked/resources.index');
  });

  it('starts the draft before browsing even when nothing was typed, so the list offers "Pridėti"', async () => {
    wrapper = mountForm(null);

    await wrapper.find('[data-testid="reservation-browse-resources"]').trigger('click');

    expect(router.put).toHaveBeenCalledWith('/mocked/reservationCart.update', { name: null, description: null }, expect.anything());
  });

  it('goes straight to the resource list when nothing new was typed', async () => {
    wrapper = mountForm(cartWith([item()]));

    await wrapper.find('[data-testid="reservation-browse-resources"]').trigger('click');

    expect(router.visit).toHaveBeenCalledWith('/mocked/resources.index');
  });

  it('does not save an end before the start', async () => {
    wrapper = mountForm(cartWith([item()]));
    const pickers = wrapper.findAll('input.datetime');

    await pickers[1]!.setValue('2026-10-01T09:00');

    expect(router.put).not.toHaveBeenCalled();
    expect(wrapper.text()).toContain('reservations.cart.end_before_start');
  });

  it('deletes the draft and leaves for the reservation list', async () => {
    wrapper = mountForm(cartWith([item()]));

    await wrapper.find('[data-testid="reservation-draft-delete"]').trigger('click');
    wrapper.findComponent({ name: 'ConfirmDialog' }).vm.$emit('confirm');

    const [url, options] = vi.mocked(router.delete).mock.calls.at(-1)! as [string, { onSuccess: () => void }];
    expect(url).toBe('/mocked/reservationCart.destroy');

    options.onSuccess();
    expect(router.visit).toHaveBeenCalledWith('/mocked/reservations.index');
  });

  it('offers no deletion before a draft exists', () => {
    wrapper = mountForm(null);

    expect(wrapper.find('[data-testid="reservation-draft-delete"]').exists()).toBe(false);
  });
});
