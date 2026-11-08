import { mount } from '@vue/test-utils';
import { router } from '@inertiajs/vue3';
import { afterEach, describe, expect, it, vi } from 'vitest';

import { DatePicker } from '@/Components/ui/date-picker';
import { commonStubs } from '@/tests/stubs';

import AddCheckInDialog from '../AddCheckInDialog.vue';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

const mountDialog = (props: { initialStartDate?: Date; initialEndDate?: Date } = {}) => mount(AddCheckInDialog, {
  props: { institutionId: 'inst-1', open: true, ...props },
  global: { stubs: commonStubs },
});

async function submit(wrapper: ReturnType<typeof mountDialog>) {
  await wrapper.findAll('button').find(button => button.text().includes('Išsaugoti pranešimą'))!.trigger('click');
}

describe('AddCheckInDialog calendar dates', () => {
  afterEach(() => {
    vi.useRealTimers();
    vi.clearAllMocks();
  });

  it('shows and submits the local calendar days from a drag selection near midnight', async () => {
    const wrapper = mountDialog({
      initialStartDate: new Date(2026, 2, 15, 0, 15),
      initialEndDate: new Date(2026, 2, 29, 0, 15),
    });
    const pickers = wrapper.findAllComponents(DatePicker);

    expect((pickers[0]!.props('modelValue') as Date).toISOString()).toBe('2026-03-15T12:00:00.000Z');
    expect((pickers[1]!.props('modelValue') as Date).toISOString()).toBe('2026-03-29T12:00:00.000Z');
    await submit(wrapper);
    expect(router.post).toHaveBeenLastCalledWith(expect.any(String), expect.objectContaining({
      start_date: '2026-03-15', end_date: '2026-03-29',
    }), expect.any(Object));
    wrapper.unmount();
  });

  it('submits the day emitted at UTC noon by the picker', async () => {
    const wrapper = mountDialog();
    const pickers = wrapper.findAllComponents(DatePicker);

    pickers[0]!.vm.$emit('update:modelValue', new Date('2026-03-29T12:00:00Z'));
    pickers[1]!.vm.$emit('update:modelValue', new Date('2026-04-02T12:00:00Z'));
    await submit(wrapper);

    expect(router.post).toHaveBeenLastCalledWith(expect.any(String), expect.objectContaining({
      start_date: '2026-03-29', end_date: '2026-04-02',
    }), expect.any(Object));
    wrapper.unmount();
  });

  it('uses Vilnius today just after midnight and adds fourteen calendar days across DST', async () => {
    vi.useFakeTimers();
    vi.setSystemTime(new Date('2026-03-28T22:30:00Z'));
    const wrapper = mountDialog();

    expect((wrapper.findComponent(DatePicker).props('modelValue') as Date).toISOString()).toBe('2026-03-29T12:00:00.000Z');
    await submit(wrapper);
    expect(router.post).toHaveBeenLastCalledWith(expect.any(String), expect.objectContaining({
      start_date: '2026-03-29', end_date: '2026-04-12',
    }), expect.any(Object));
    wrapper.unmount();
  });

  it('updates the picker and submitted dates when a new drag selection arrives', async () => {
    const wrapper = mountDialog();
    await wrapper.setProps({ initialStartDate: new Date(2026, 10, 8), initialEndDate: new Date(2026, 10, 9) });

    expect((wrapper.findComponent(DatePicker).props('modelValue') as Date).toISOString()).toBe('2026-11-08T12:00:00.000Z');
    await submit(wrapper);
    expect(router.post).toHaveBeenLastCalledWith(expect.any(String), expect.objectContaining({
      start_date: '2026-11-08', end_date: '2026-11-09',
    }), expect.any(Object));
    wrapper.unmount();
  });
});
