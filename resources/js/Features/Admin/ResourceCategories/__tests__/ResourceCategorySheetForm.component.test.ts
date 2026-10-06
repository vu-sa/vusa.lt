import { mount } from '@vue/test-utils';
import { useForm } from '@inertiajs/vue3';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import ResourceCategorySheetForm from '@/Features/Admin/ResourceCategories/ResourceCategorySheetForm.vue';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

vi.stubGlobal('route', (name: string, id?: string | number) => `/mocked-route/${name}${id ? `/${id}` : ''}`);

const SheetFormStub = {
  props: ['open', 'title'],
  emits: ['submit', 'cancel', 'update:open'],
  template: '<section v-if="open"><h1>{{ title }}</h1><slot /><button type="button" data-testid="submit" @click="$emit(\'submit\')">save</button></section>',
};

const mountForm = (category = null) => mount(ResourceCategorySheetForm, {
  props: { open: true, category },
  global: {
    stubs: {
      SheetForm: SheetFormStub,
      MultiLocaleInput: { template: '<div />' },
      FluentIconSelect: { template: '<div />' },
    },
  },
});

describe('ResourceCategorySheetForm.vue', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('posts a new category from the collection sheet', async () => {
    const wrapper = mountForm();

    expect(wrapper.text()).toContain('Nauja kategorija');

    await wrapper.find('[data-testid="submit"]').trigger('click');

    expect(vi.mocked(useForm).mock.results[0]?.value.post).toHaveBeenCalledWith('/mocked-route/resourceCategories.store', expect.any(Object));
  });

  it('patches the selected category instead of creating another one', async () => {
    const wrapper = mountForm({
      id: 12,
      name: { lt: 'Projektoriai', en: 'Projectors' },
      description: { lt: '', en: '' },
      icon: null,
    });

    expect(wrapper.text()).toContain('Redaguoti kategoriją');

    await wrapper.find('[data-testid="submit"]').trigger('click');

    expect(vi.mocked(useForm).mock.results[0]?.value.patch).toHaveBeenCalledWith('/mocked-route/resourceCategories.update/12', expect.any(Object));
  });
});
