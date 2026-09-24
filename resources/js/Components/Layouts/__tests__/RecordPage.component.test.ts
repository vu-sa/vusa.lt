import { mount } from '@vue/test-utils';
import { CircleCheck } from 'lucide-vue-next';
import { describe, expect, it, vi } from 'vitest';
import { router } from '@inertiajs/vue3';

import RecordPage from '@/Components/Layouts/RecordPage.vue';
import { commonStubs } from '@/tests/stubs';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

const createWrapper = () => mount(RecordPage, {
  props: {
    title: 'Senato posėdis',
    entityType: 'meeting',
    status: { label: 'Užpildyta', role: 'success', icon: CircleCheck },
    facts: [{ key: 'institution', label: 'Institucija', value: 'VU Senatas' }],
    sections: [
      { value: 'agenda', label: 'Darbotvarkė', count: 2 },
      { value: 'files', label: 'Failai' },
      { value: 'tasks', label: 'Užduotys' },
    ],
    primaryAction: { key: 'edit', label: 'Redaguoti' },
    navigation: { position: 3, total: 24, previousHref: '/previous', nextHref: '/next' },
  },
  slots: {
    agenda: '<div data-testid="agenda">Agenda</div>',
    files: '<div data-testid="files">Files</div>',
    tasks: '<div data-testid="tasks">Tasks</div>',
    activity: '<div data-testid="activity">Activity</div>',
  },
  global: { stubs: commonStubs },
});

describe('RecordPage', () => {
  it('renders the canonical record anatomy', () => {
    const wrapper = createWrapper();

    expect(wrapper.text()).toContain('Senato posėdis');
    expect(wrapper.text()).toContain('VU Senatas');
    expect(wrapper.text()).toContain('3 / 24');
    expect(wrapper.find('[data-testid="activity"]').exists()).toBe(true);
    expect(wrapper.findAll('[role="tab"]')).toHaveLength(3);
  });

  it('keeps mobile sections mounted while showing only the selected section on desktop', async () => {
    const wrapper = createWrapper();

    expect(wrapper.find('[data-testid="agenda"]').element.closest('section')?.className).toContain('md:block');
    expect(wrapper.find('[data-testid="files"]').element.closest('section')?.className).toContain('md:hidden');

    await wrapper.findAll('[role="tab"]')[1]!.trigger('click');

    expect(wrapper.find('[data-testid="agenda"]').element.closest('section')?.className).toContain('md:hidden');
    expect(wrapper.find('[data-testid="files"]').element.closest('section')?.className).toContain('md:block');
  });

  it('emits the primary action key', async () => {
    const wrapper = createWrapper();

    await wrapper.findAll('button').find(button => button.text().includes('Redaguoti'))!.trigger('click');

    expect(wrapper.emitted('action')).toEqual([['edit']]);
  });

  it('follows an overflow action that carries a link, and emits one that does not', async () => {
    const wrapper = mount(RecordPage, {
      props: {
        title: 'Projektorius',
        entityType: 'resource',
        overflowActions: [
          { key: 'edit', label: 'Redaguoti', href: '/mano/resources/1/edit' },
          { key: 'delete', label: 'Ištrinti', destructive: true },
        ],
      },
      global: { stubs: { ...commonStubs, SheetClose: { template: '<div><slot /></div>' } } },
    });

    const button = (label: string) => wrapper.findAll('button').find(candidate => candidate.text() === label);

    await button('Redaguoti')!.trigger('click');
    expect(router.visit).toHaveBeenCalledWith('/mano/resources/1/edit');
    expect(wrapper.emitted('action')).toBeUndefined();

    await button('Ištrinti')!.trigger('click');
    expect(wrapper.emitted('action')).toEqual([['delete']]);
  });
});
