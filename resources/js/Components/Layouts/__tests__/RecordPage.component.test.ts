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

  it('places title actions beside the title when requested', async () => {
    const wrapper = createWrapper();
    await wrapper.setProps({ actionsBesideTitle: true });

    expect(wrapper.find('[data-slot="record-title"]').classes()).toContain('lg:row-start-2');
    expect(wrapper.find('header').html()).toContain('lg:col-start-2 lg:row-start-2');
  });

  it('fits four facts into one wide row while retaining smaller breakpoint columns', () => {
    const wrapper = mount(RecordPage, {
      props: {
        title: 'Faktų įrašas',
        entityType: 'institution',
        facts: Array.from({ length: 4 }, (_, index) => ({ key: `fact-${index}`, label: `Faktas ${index}`, value: index })),
      },
      global: { stubs: commonStubs },
    });

    const grid = wrapper.find('[data-slot="record-facts"]');
    expect(grid.element.tagName).toBe('DL');
    expect(grid.findAll(':scope > div')).toHaveLength(4);
    expect(grid.classes()).toEqual(expect.arrayContaining([
      '[&>*]:basis-full', 'sm:[&>*]:basis-1/2', 'lg:[&>*]:basis-1/3', 'xl:[&>*]:basis-1/4',
    ]));
  });

  it('applies a fact surface and heading color', () => {
    const wrapper = mount(RecordPage, {
      props: {
        title: 'VU Senatas',
        entityType: 'institution',
        facts: [{ key: 'type', label: 'VU organas', value: 'Senatas', surfaceClass: 'bg-[#78003F]/10', labelClass: 'text-[#78003F]' }],
      },
      global: { stubs: commonStubs },
    });

    const fact = wrapper.find('[data-slot="record-facts"] > div');
    expect(fact.classes()).toContain('bg-[#78003F]/10');
    expect(fact.find('dt').classes()).toContain('text-[#78003F]');
    expect(fact.find('dd').text()).toBe('Senatas');
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

  /** Rarely needed, and about the whole record — so it waits in ⋯ rather than beside the comments. */
  it('offers the change history in the ⋯ menu, before destructive actions', async () => {
    const wrapper = mount(RecordPage, {
      props: {
        title: 'Senato posėdis',
        entityType: 'meeting',
        overflowActions: [
          { key: 'copy-link', label: 'Kopijuoti nuorodą' },
          { key: 'delete', label: 'Šalinti', destructive: true },
        ],
        historySubject: { type: 'meeting', id: 'm1' },
      },
      global: {
        stubs: {
          ...commonStubs,
          ActivityLogSheet: { name: 'ActivityLogSheet', props: ['open', 'subjectType', 'subjectId'], template: '<div data-testid="history" :data-open="open" />' },
        },
      },
    });

    const labels = wrapper.find('[data-testid="dropdown-menu-content"]').text();
    expect(labels.indexOf('activity.title')).toBeGreaterThan(labels.indexOf('Kopijuoti nuorodą'));
    expect(labels.indexOf('activity.title')).toBeLessThan(labels.indexOf('Šalinti'));

    await wrapper.find('[data-testid="dropdown-menu-content"]').findAll('button')
      .find(button => button.text().includes('activity.title'))!
      .trigger('click');
    expect(wrapper.find('[data-testid="history"]').attributes('data-open')).toBe('true');
    expect(wrapper.emitted('action')).toBeUndefined();
  });

  it('can set a long title in sentence case instead of the display face', () => {
    const wrapper = mount(RecordPage, {
      props: { title: 'Dėl studijų tvarkos pakeitimų', entityType: 'agenda_item', titleVoice: 'sentence' },
      global: { stubs: commonStubs },
    });

    expect(wrapper.find('h1').classes()).not.toContain('u-display');
  });

  /** DropdownMenu's root renders no element, so a breakpoint class on it never applied — ⋯ showed twice on phones. */
  it('shows the menu ⋯ only from md and the sheet ⋯ only below it', () => {
    const wrapper = mount(RecordPage, {
      props: { title: 'Senato posėdis', entityType: 'meeting', overflowActions: [{ key: 'copy-link', label: 'Kopijuoti nuorodą' }] },
      global: { stubs: commonStubs },
    });

    const triggers = wrapper.findAll('button[aria-label="Daugiau veiksmų"]').map(button => button.classes());
    expect(triggers).toHaveLength(2);
    expect(triggers[0]).toEqual(expect.arrayContaining(['hidden', 'md:inline-flex']));
    expect(triggers[1]).toContain('md:hidden');
  });

  it('shows a section icon and the neighbours\' labels beside ‹ ›', () => {
    const wrapper = mount(RecordPage, {
      props: {
        title: 'Senato posėdis',
        entityType: 'meeting',
        sections: [
          { value: 'agenda', label: 'Darbotvarkė', icon: CircleCheck },
          { value: 'files', label: 'Failai' },
          { value: 'tasks', label: 'Užduotys' },
        ],
        navigation: {
          position: 2,
          total: 3,
          previousHref: '/previous',
          nextHref: '/next',
          previousLabel: '04-15',
          nextLabel: '06-10',
          previousAriaLabel: 'Ankstesnis posėdis: 2026 m. balandžio 15 d.',
        },
      },
      global: { stubs: commonStubs },
    });

    expect(wrapper.findAll('[role="tab"]')[0]!.find('svg').exists()).toBe(true);
    expect(wrapper.findAll('[role="tab"]')[1]!.find('svg').exists()).toBe(false);
    expect(wrapper.find('button[aria-label="Ankstesnis posėdis: 2026 m. balandžio 15 d."]').text()).toBe('04-15');
    expect(wrapper.find('button[aria-label="Kitas įrašas"]').text()).toBe('06-10');
  });

  it('folds a title longer than three lines behind a toggle', async () => {
    vi.useFakeTimers();
    const wrapper = mount(RecordPage, {
      props: { title: 'Trumpas', entityType: 'agenda_item', titleVoice: 'sentence' },
      global: { stubs: commonStubs },
    });
    expect(wrapper.find('[data-testid="record-title-toggle"]').exists()).toBe(false);

    // jsdom has no layout, so give the clamped heading the measurements of an overflowing one.
    const heading = wrapper.find('[data-slot="record-title"]').element;
    Object.defineProperty(heading, 'scrollHeight', { configurable: true, value: 200 });
    Object.defineProperty(heading, 'clientHeight', { configurable: true, value: 90 });
    await wrapper.setProps({ title: 'Labai ilgas pavadinimas '.repeat(20) });
    vi.runAllTimers();
    await wrapper.vm.$nextTick();

    expect(wrapper.find('h1').classes()).toContain('line-clamp-3');
    await wrapper.find('[data-testid="record-title-toggle"]').trigger('click');
    expect(wrapper.find('h1').classes()).not.toContain('line-clamp-3');
    vi.useRealTimers();
  });
});
