import { mount } from '@vue/test-utils';
import { useForm } from '@inertiajs/vue3';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import ProblemCategorySheetForm from '@/Features/Admin/ProblemCategories/ProblemCategorySheetForm.vue';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

vi.stubGlobal('route', (name: string, id?: string | number) => `/mocked-route/${name}${id ? `/${id}` : ''}`);

const SheetFormStub = {
  props: ['open', 'title'],
  emits: ['submit', 'cancel', 'update:open'],
  template: '<section v-if="open"><h1>{{ title }}</h1><slot /><button type="button" data-testid="submit" @click="$emit(\'submit\')">save</button></section>',
};

const mountForm = (category: { id: number; name: { lt: string; en: string }; description: { lt: string; en: string } | null } | null = null) => mount(ProblemCategorySheetForm, {
  props: { open: true, category },
  global: {
    stubs: {
      SheetForm: SheetFormStub,
      MultiLocaleInput: { template: '<div />' },
    },
  },
});

describe('ProblemCategorySheetForm.vue', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('posts a new category', async () => {
    const wrapper = mountForm();

    expect(wrapper.text()).toContain('Nauja kategorija');

    await wrapper.find('[data-testid="submit"]').trigger('click');

    expect(vi.mocked(useForm).mock.results[0]?.value.post).toHaveBeenCalledWith('/mocked-route/problemCategories.store', expect.any(Object));
  });

  it('patches the selected category instead of creating another one', async () => {
    const wrapper = mountForm({ id: 4, name: { lt: 'Procesai', en: 'Processes' }, description: null });

    expect(wrapper.text()).toContain('Redaguoti kategoriją');

    await wrapper.find('[data-testid="submit"]').trigger('click');

    expect(vi.mocked(useForm).mock.results[0]?.value.patch).toHaveBeenCalledWith('/mocked-route/problemCategories.update/4', expect.any(Object));
  });
});
