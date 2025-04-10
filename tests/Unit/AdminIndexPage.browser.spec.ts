import { describe, it, expect, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { NCard } from 'naive-ui';
import AdminIndexPage from '@/Components/Layouts/IndexModel/AdminIndexPage.vue';

vi.mock('@/Components/Layouts/IndexModel/IndexPageLayout.vue', () => ({
  default: {
    name: 'IndexPageLayout',
    props: ['title', 'modelName', 'canUseRoutes', 'columns', 'paginatedModels', 'icon'],
    template: '<div data-testid="index-page-layout">{{ title }}</div>',
    setup() {
      return {}
    }
  }
}));

describe('AdminIndexPage', () => {
  it('renders correctly with props', () => {
    const props = {
      modelName: 'institutions',
      entityName: 'institution',
      paginatedModels: {
        data: [],
        total: 0,
        per_page: 10,
        current_page: 1,
        last_page: 1
      },
      columnBuilder: () => [],
      initialSorters: { name: false },
      initialFilters: { 'type.id': [] },
      canUseRoutes: {
        create: true,
        show: true,
        edit: true,
        destroy: true,
        duplicate: false
      }
    };

    const wrapper = mount(AdminIndexPage, {
      props,
      global: {
        stubs: {
          NCard
        }
      }
    });

    expect(wrapper.find('[data-testid="index-page-layout"]').exists()).toBe(true);
  });
});