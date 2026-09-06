import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';

import FooterNavigationManager from '@/Features/Admin/NavigationBuilder/FooterNavigationManager.vue';
import type { AdminFooterColumn } from '@/Features/Admin/NavigationBuilder/types';
import { commonStubs } from '@/tests/stubs';

// AlertDialog is a separate component family from the Dialog `commonStubs` covers, so it
// needs its own stub — same as NavigationRootItem.component.test.ts.
const alertDialogStubs = {
  AlertDialog: { template: '<div><slot /></div>' },
  AlertDialogContent: { template: '<div><slot /></div>' },
  AlertDialogHeader: { template: '<div><slot /></div>' },
  AlertDialogTitle: { template: '<div><slot /></div>' },
  AlertDialogDescription: { template: '<div><slot /></div>' },
  AlertDialogFooter: { template: '<div><slot /></div>' },
  AlertDialogTrigger: { template: '<div><slot /></div>' },
  AlertDialogAction: { template: '<button class="confirm-delete" @click="$emit(\'click\')"><slot /></button>', emits: ['click'] },
  AlertDialogCancel: { template: '<button class="cancel-delete"><slot /></button>' },
};

function makeColumn(overrides: Partial<AdminFooterColumn> = {}): AdminFooterColumn {
  return {
    id: 1,
    name: 'Apie mus',
    url: '#',
    parent_id: 0,
    lang: 'lt',
    order: 0,
    is_active: true,
    extra_attributes: { location: 'footer', type: 'category-link' },
    links: [
      { id: 10, name: 'Kontaktai', url: '/kontaktai', parent_id: 1, lang: 'lt', order: 0, is_active: true, extra_attributes: { location: 'footer', type: 'link' } },
    ],
    ...overrides,
  };
}

function createWrapper(props: Record<string, unknown> = {}) {
  return mount(FooterNavigationManager, {
    props: {
      columns: [makeColumn()],
      lang: 'lt',
      maxColumns: 4,
      ...props,
    },
    global: { stubs: { ...commonStubs, ...alertDialogStubs } },
  });
}

describe('FooterNavigationManager.vue', () => {
  it('renders a column heading and its links', () => {
    const wrapper = createWrapper();

    expect(wrapper.text()).toContain('Apie mus');
    expect(wrapper.text()).toContain('Kontaktai');
  });

  it('marks a column without a real URL as text-only', () => {
    const wrapper = createWrapper({ columns: [makeColumn({ url: '#' })] });

    expect(wrapper.text()).toContain('navigation.builder.footer_column_text_only');
  });

  it('does not mark a column with a real URL as text-only', () => {
    const wrapper = createWrapper({ columns: [makeColumn({ url: '/apie' })] });

    expect(wrapper.text()).not.toContain('navigation.builder.footer_column_text_only');
  });

  it('shows the empty-column hint when a column has no links', () => {
    const wrapper = createWrapper({ columns: [makeColumn({ links: [] })] });

    expect(wrapper.text()).toContain('navigation.builder.empty_column');
  });

  it('hides "add footer column" once maxColumns is reached', () => {
    const wrapper = createWrapper({ columns: [makeColumn({ id: 1 }), makeColumn({ id: 2 }), makeColumn({ id: 3 }), makeColumn({ id: 4 })], maxColumns: 4 });

    expect(wrapper.text()).not.toContain('navigation.builder.add_footer_column');
  });

  it('shows "add footer column" below maxColumns', () => {
    const wrapper = createWrapper({ columns: [makeColumn({ id: 1 })], maxColumns: 4 });

    expect(wrapper.text()).toContain('navigation.builder.add_footer_column');
  });

  it('emits delete-column when the column delete action is confirmed', async () => {
    const column = makeColumn();
    const wrapper = createWrapper({ columns: [column] });

    await wrapper.find('.confirm-delete').trigger('click');

    expect(wrapper.emitted('delete-column')?.[0]).toEqual([column]);
  });
});
