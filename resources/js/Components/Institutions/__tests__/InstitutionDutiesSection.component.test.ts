import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount } from '@vue/test-utils';
import { router } from '@inertiajs/vue3';

import InstitutionDutiesSection from '../InstitutionDutiesSection.vue';

import { commonStubs } from '@/tests/stubs';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

vi.stubGlobal('route', (name?: string) => (name === undefined ? { current: () => false } : `/mocked/${name}`));

const stubs = {
  ...commonStubs,
  // The real one drives Sortable on the DOM; the up/down buttons are what a test can reach.
  SortableDutiesTable: {
    props: ['modelValue'],
    template: '<div><div v-for="(model, index) in modelValue" :key="model.id" :data-row="model.id"><slot :model="model" :index="index" /></div></div>',
  },
  DutyCard: { props: ['duty', 'canManage'], template: '<div class="duty-card">{{ duty.name }}</div>' },
  EmptyState: { props: ['title', 'actionLabel'], template: '<div data-testid="empty">{{ title }}<span v-if="actionLabel">{{ actionLabel }}</span></div>' },
};

const duties = [
  { id: 'a', name: 'Pirmininkas', current_users: [] },
  { id: 'b', name: 'Sekretorius', current_users: [] },
  { id: 'c', name: 'Iždininkas', current_users: [] },
];

const mountSection = (props: Record<string, unknown> = {}) =>
  mount(InstitutionDutiesSection, {
    props: { duties, institutionId: 'inst-1', canManage: true, ...props },
    global: { stubs },
  });

const button = (wrapper: ReturnType<typeof mount>, label: string) =>
  wrapper.findAll('button').find(b => b.text().includes(label) || b.attributes('aria-label')?.includes(label));

describe('InstitutionDutiesSection', () => {
  beforeEach(() => {
    vi.mocked(router.post).mockClear?.();
  });

  it('lists the duties and offers to create the first one when there are none', () => {
    expect(mountSection().findAll('.duty-card')).toHaveLength(3);

    const empty = mountSection({ duties: [] });
    expect(empty.find('[data-testid="empty"]').exists()).toBe(true);
    expect(empty.text()).toContain('Sukurti pirmą pareigybę');
  });

  it('hides ordering and creating from someone who may not update the institution', () => {
    const wrapper = mountSection({ canManage: false });

    expect(button(wrapper, 'Keisti tvarką')).toBeUndefined();
    expect(button(wrapper, 'Nauja pareigybė')).toBeUndefined();
  });

  it('does not offer ordering for a single duty', () => {
    expect(button(mountSection({ duties: [duties[0]] }), 'Keisti tvarką')).toBeUndefined();
  });

  it('moves a duty with the arrow buttons and only enables saving once the order changed', async () => {
    const wrapper = mountSection();

    await button(wrapper, 'Keisti tvarką')!.trigger('click');
    expect(button(wrapper, 'Išsaugoti tvarką')!.attributes('disabled')).toBeDefined();

    await wrapper.find('[data-row="b"]').findAll('button').find(b => b.attributes('aria-label') === 'Perkelti aukščiau')!.trigger('click');

    expect(wrapper.findAll('[data-row]').map(row => row.attributes('data-row'))).toEqual(['b', 'a', 'c']);
    expect(button(wrapper, 'Išsaugoti tvarką')!.attributes('disabled')).toBeUndefined();
  });

  it('cannot move the first duty up or the last one down', async () => {
    const wrapper = mountSection();
    await button(wrapper, 'Keisti tvarką')!.trigger('click');

    const up = wrapper.find('[data-row="a"]').findAll('button').find(b => b.attributes('aria-label') === 'Perkelti aukščiau')!;
    const down = wrapper.find('[data-row="c"]').findAll('button').find(b => b.attributes('aria-label') === 'Perkelti žemiau')!;

    expect(up.attributes('disabled')).toBeDefined();
    expect(down.attributes('disabled')).toBeDefined();
  });

  it('saves the new order as id + position to the reorder route', async () => {
    const wrapper = mountSection();

    await button(wrapper, 'Keisti tvarką')!.trigger('click');
    await wrapper.find('[data-row="a"]').findAll('button').find(b => b.attributes('aria-label') === 'Perkelti žemiau')!.trigger('click');
    await button(wrapper, 'Išsaugoti tvarką')!.trigger('click');

    expect(router.post).toHaveBeenCalledWith(
      '/mocked/institutions.reorderDuties',
      { duties: [{ id: 'b', order: 0 }, { id: 'a', order: 1 }, { id: 'c', order: 2 }] },
      expect.objectContaining({ preserveScroll: true }),
    );
  });

  it('leaves order mode without saving when cancelled', async () => {
    const wrapper = mountSection();

    await button(wrapper, 'Keisti tvarką')!.trigger('click');
    await button(wrapper, 'Atšaukti')!.trigger('click');

    expect(router.post).not.toHaveBeenCalled();
    expect(button(wrapper, 'Keisti tvarką')).toBeDefined();
  });
});
