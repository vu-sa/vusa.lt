import { beforeEach, describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { usePage } from '@inertiajs/vue3';

import type { UserWithPivot } from '@/Types/DutyAssignment';
import { createMockPage } from '@/tests/helpers/createMockPage';
import { commonStubs, stubPopover, stubPopoverContent } from '@/tests/stubs';

import DutyHoldersPopover from '../DutyHoldersPopover.vue';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

const stubs = {
  ...commonStubs,
  Popover: stubPopover,
  PopoverTrigger: { template: '<div><slot /></div>' },
  PopoverContent: stubPopoverContent,
};

const makeHolder = (overrides: Partial<UserWithPivot> = {}): UserWithPivot => ({
  id: 'u1',
  name: 'Rūta Petraitė',
  pivot: { id: 'term-1', start_date: '2025-09-01', end_date: null },
  ...overrides,
});

describe('DutyHoldersPopover', () => {
  beforeEach(() => {
    vi.mocked(usePage).mockReturnValue(createMockPage());
  });

  it('reuses UsersAvatarGroup with duty headers, tenure dates and management actions', async () => {
    const holder = makeHolder();
    const wrapper = mount(DutyHoldersPopover, {
      props: { holders: [holder], canManage: true },
      global: { stubs },
    });

    expect(wrapper.findComponent({ name: 'UsersAvatarGroup' }).exists()).toBe(true);
    expect(wrapper.get('[data-slot="duty-holders-trigger"]').text()).toContain('Užima');
    expect(wrapper.get('.popover-content').text()).toContain('Užima · 1');
    expect(wrapper.get('.popover-content').text()).toContain('Rūta Petraitė');
    expect(wrapper.get('.popover-content').text()).toContain('nuo');

    const editBtn = wrapper.findAll('button').find(b => b.attributes('aria-label')?.includes('Redaguoti pareigybės laikotarpį'));
    await editBtn!.trigger('click');
    expect(wrapper.emitted('edit-term')?.[0]).toEqual([holder]);

    await wrapper.get('[data-slot="duty-holders-assign"]').trigger('click');
    expect(wrapper.emitted('assign')).toHaveLength(1);
  });

  it.each<[number, string]>([[0, 'Užima'], [1, 'Užima'], [2, 'Užima'], [3, 'Užima 3'], [9, 'Užima 9']])(
    'shows the total beside Užima for more than two of %i holders',
    (count, label) => {
      const holders = Array.from({ length: count }, (_, index) => makeHolder({ id: String(index) }));
      const wrapper = mount(DutyHoldersPopover, { props: { holders, canManage: true }, global: { stubs } });

      expect(wrapper.get('[data-slot="duty-holders-trigger"] > span:first-child').text()).toBe(label);
    },
  );

  it('links holder rows only when in admin context with user index access', () => {
    vi.mocked(usePage).mockReturnValue(createMockPage({
      app: { path: 'mano/institutions/1' },
      auth: { can: { index: { user: true } } },
    }));

    const permitted = mount(DutyHoldersPopover, {
      props: { holders: [makeHolder()] },
      global: { stubs },
    });
    expect(permitted.findAll('[data-testid="inertia-link"]')).toHaveLength(1);
    expect(permitted.get('[data-testid="inertia-link"]').attributes('href')).toContain('users.show');

    vi.mocked(usePage).mockReturnValue(createMockPage({
      app: { path: 'mano/institutions/1' },
      auth: { can: { index: { user: false } } },
    }));

    const denied = mount(DutyHoldersPopover, {
      props: { holders: [makeHolder()] },
      global: { stubs },
    });
    expect(denied.findAll('[data-testid="inertia-link"]')).toHaveLength(0);
  });

  it('renders the vacant message and assign action when empty and canManage is true', () => {
    const wrapper = mount(DutyHoldersPopover, {
      props: { holders: [], canManage: true },
      global: { stubs },
    });

    expect(wrapper.get('.popover-content').text()).toContain('Neužimta');
    expect(wrapper.find('[data-slot="duty-holders-assign"]').exists()).toBe(true);
  });
});
