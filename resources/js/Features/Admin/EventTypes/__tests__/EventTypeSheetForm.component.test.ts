import { mount } from '@vue/test-utils';
import { useForm } from '@inertiajs/vue3';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import EventTypeSheetForm from '@/Features/Admin/EventTypes/EventTypeSheetForm.vue';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

vi.stubGlobal('route', (name: string, id?: string | number) => `/mocked-route/${name}${id ? `/${id}` : ''}`);

const SheetFormStub = {
  props: ['open', 'title'],
  emits: ['submit', 'cancel', 'update:open'],
  template: '<section v-if="open"><h1>{{ title }}</h1><slot /><button type="button" data-testid="submit" @click="$emit(\'submit\')">save</button></section>',
};

const mountForm = (eventType: any = null) => mount(EventTypeSheetForm, {
  props: { open: true, eventType },
  global: {
    stubs: {
      SheetForm: SheetFormStub,
      MultiLocaleInput: { template: '<div />' },
      Switch: { template: '<div />' },
    },
  },
});

describe('EventTypeSheetForm.vue', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('posts a new event type from the collection sheet', async () => {
    const wrapper = mountForm();

    expect(wrapper.text()).toContain('Naujas renginio tipas');

    await wrapper.find('[data-testid="submit"]').trigger('click');

    expect(vi.mocked(useForm).mock.results[0]?.value.post).toHaveBeenCalledWith('/mocked-route/eventTypes.store', expect.any(Object));
  });

  it('patches the selected event type instead of creating another one', async () => {
    const wrapper = mountForm({
      id: 5,
      name: { lt: 'Mokymai', en: 'Trainings' },
      slug: 'mokymai',
      description: { lt: '', en: '' },
      sort_order: 1,
      is_active: 1,
    });

    expect(wrapper.text()).toContain('Redaguoti renginio tipą');

    await wrapper.find('[data-testid="submit"]').trigger('click');

    expect(vi.mocked(useForm).mock.results[0]?.value.patch).toHaveBeenCalledWith('/mocked-route/eventTypes.update/5', expect.any(Object));
  });
});
