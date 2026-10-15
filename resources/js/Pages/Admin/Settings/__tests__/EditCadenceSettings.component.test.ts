import { describe, it, expect, afterEach, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { nextTick } from 'vue';

import EditCadenceSettings from '@/Pages/Admin/Settings/EditCadenceSettings.vue';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

const SheetFormStub = {
  name: 'SheetForm',
  props: ['open', 'title'],
  emits: ['submit', 'update:open', 'cancel'],
  template: '<div v-if="open" data-testid="defaults-sheet"><slot /><button data-testid="save-defaults" @click="$emit(\'submit\')">save</button></div>',
};

function createWrapper() {
  return mount(EditCadenceSettings, {
    props: {
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

interface DefaultsForm {
  post: ReturnType<typeof vi.fn>;
}

describe('EditCadenceSettings.vue — cadence defaults', () => {
  let wrapper: ReturnType<typeof mount>;

  afterEach(() => {
    wrapper?.unmount();
  });

  it('shows the saved window on the page, spanning the new year', () => {
    wrapper = createWrapper();
    const year = new Date().getFullYear();

    expect(wrapper.find('[data-testid="cadence-defaults"]').text()).toContain(`${year}-07-01 → ${year + 1}-06-30`);
    expect(wrapper.find('[data-testid="defaults-sheet"]').exists()).toBe(false);
  });

  it('edits the defaults in a sheet and closes it once saved', async () => {
    wrapper = createWrapper();

    await wrapper.find('[data-testid="cadence-defaults"] button').trigger('click');
    expect(wrapper.find('[data-testid="defaults-sheet"]').exists()).toBe(true);

    await wrapper.find('[data-testid="save-defaults"]').trigger('click');

    const form = (wrapper.vm as unknown as { defaultsForm: DefaultsForm }).defaultsForm;
    expect(form.post).toHaveBeenCalledWith('/mocked-route/settings.cadences.defaults', expect.objectContaining({ preserveScroll: true }));

    form.post.mock.calls[0][1].onSuccess();
    await nextTick();

    expect(wrapper.find('[data-testid="defaults-sheet"]').exists()).toBe(false);
  });
});
