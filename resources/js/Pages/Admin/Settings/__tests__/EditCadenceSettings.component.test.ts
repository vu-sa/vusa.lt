import { useForm } from '@inertiajs/vue3';
import { describe, it, expect, afterEach, beforeEach, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { nextTick } from 'vue';

import type { MockForm } from '@/tests/helpers/createMockForm';
import EditCadenceSettings from '@/Pages/Admin/Settings/EditCadenceSettings.vue';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

const SheetFormStub = {
  name: 'SheetForm',
  props: ['open', 'title'],
  emits: ['submit', 'update:open', 'cancel'],
  template: '<div v-if="open" data-testid="defaults-sheet"><slot /><button data-testid="save-defaults" @click="$emit(\'submit\')">save</button></div>',
};

function createWrapper(canEdit = true) {
  return mount(EditCadenceSettings, {
    props: {
      canEdit,
      cadences: [],
      settings: { default_start_month_day: '07-01', default_end_month_day: '06-30' },
    },
    global: {
      stubs: {
        SheetForm: SheetFormStub,
        CadenceList: true,
      },
    },
  });
}

describe('EditCadenceSettings.vue — cadence defaults', () => {
  let wrapper: ReturnType<typeof mount>;

  beforeEach(() => vi.useFakeTimers({ now: new Date('2026-03-10T12:00:00Z'), toFake: ['Date'] }));

  afterEach(() => {
    vi.useRealTimers();
    wrapper?.unmount();
  });

  it('shows the saved window on the page, spanning the new year', () => {
    wrapper = createWrapper();

    expect(wrapper.find('[data-testid="cadence-defaults"]').text()).toContain('2026-07-01 → 2027-06-30');
    expect(wrapper.find('[data-testid="defaults-sheet"]').exists()).toBe(false);
  });

  it('offers no way to change the defaults to someone who may only view settings', () => {
    wrapper = createWrapper(false);

    expect(wrapper.find('[data-testid="cadence-defaults"]').text()).toContain('07-01');
    expect(wrapper.find('[data-testid="cadence-defaults"] button').exists()).toBe(false);
  });

  it('edits the defaults in a sheet and closes it once saved', async () => {
    wrapper = createWrapper();

    await wrapper.find('[data-testid="cadence-defaults"] button').trigger('click');
    expect(wrapper.find('[data-testid="defaults-sheet"]').exists()).toBe(true);

    const form = vi.mocked(useForm).mock.results.at(-1)!.value as unknown as MockForm;
    const submit = vi.fn(() => [form.default_start_month_day, form.default_end_month_day]);
    form.post.mockImplementation(submit);
    await wrapper.get('#default_start_month_day').setValue('08-01');
    await wrapper.get('#default_end_month_day').setValue('07-31');
    await wrapper.get('[data-testid="save-defaults"]').trigger('click');

    expect(submit).toHaveReturnedWith(['08-01', '07-31']);
    expect(form.post).toHaveBeenCalledWith('/mocked-route/settings.cadences.defaults', expect.objectContaining({ preserveScroll: true }));

    form.post.mock.calls[0]?.[1].onSuccess();
    await nextTick();

    expect(wrapper.find('[data-testid="defaults-sheet"]').exists()).toBe(false);
  });
});
