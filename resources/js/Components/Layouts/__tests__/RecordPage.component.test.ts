import { mount } from '@vue/test-utils';
import { CircleCheck } from '@lucide/vue';
import { describe, expect, it, vi } from 'vitest';
import { router, usePage } from '@inertiajs/vue3';
import { reactive } from 'vue';

import RecordPage from '@/Components/Layouts/RecordPage.vue';
import { createMockPage } from '@/tests/helpers/createMockPage';
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
  it('stacks every section with persistent headings when requested', async () => {
    const wrapper = createWrapper();
    await wrapper.setProps({ sectionLayout: 'stacked', section: 'files' });

    expect(wrapper.findAll('[role="tab"]')).toHaveLength(0);
    for (const key of ['agenda', 'files', 'tasks']) {
      const section = wrapper.get(`[aria-labelledby="record-section-${key}"]`);
      expect(section.classes()).not.toContain('md:hidden');
      expect(section.get('h2').classes()).not.toContain('md:hidden');
      expect(section.get(`[data-testid="${key}"]`).exists()).toBe(true);
    }
    expect(wrapper.get('[data-slot="record-activity"]').classes()).not.toContain('md:hidden');
    expect(wrapper.get('[aria-labelledby="record-section-agenda"]').element.parentElement?.classList.contains('md:space-y-0')).toBe(false);
  });

  it('renders rich title content inside the existing heading and keeps the plain browser title', () => {
    const wrapper = mount(RecordPage, {
      props: { title: 'Koordinatorius', entityType: 'duty' },
      slots: { title: '<span data-testid="rich-title">Koordinatorė</span>' },
      global: { stubs: commonStubs },
    });

    expect(wrapper.get('h1 [data-testid="rich-title"]').text()).toBe('Koordinatorė');
    expect(wrapper.get('h1').classes()).toContain('u-display');
    expect(wrapper.get('title').text()).toContain('Koordinatorius');
    wrapper.unmount();
  });

  it('keeps the lead under the title, out of the column the actions sit in', () => {
    const wrapper = mount(RecordPage, {
      props: { title: 'Komunikacija', entityType: 'role', actionsBesideTitle: true, primaryAction: { key: 'edit', label: 'Redaguoti' } },
      slots: { lead: '<p data-testid="lead">Tvarko naujienas.</p>' },
      global: { stubs: commonStubs },
    });

    const lead = wrapper.get('[data-slot="record-lead"]');
    expect(lead.get('[data-testid="lead"]').text()).toBe('Tvarko naujienas.');
    expect(lead.classes()).toContain('lg:col-start-1');
    expect(lead.element.previousElementSibling?.getAttribute('data-slot')).toBe('record-title');
    wrapper.unmount();
  });

  it('renders the canonical record anatomy', () => {
    const wrapper = createWrapper();

    expect(wrapper.text()).toContain('Senato posėdis');
    expect(wrapper.text()).toContain('VU Senatas');
    expect(wrapper.text()).toContain('3 / 24');
    expect(wrapper.find('[data-testid="activity"]').exists()).toBe(true);
    expect(wrapper.findAll('[role="tab"]')).toHaveLength(3);
  });

  /** Phones stack every section, so the comments stay at the bottom there; only desktop tabs hide them. */
  it('shows comments under the first tab only', async () => {
    const wrapper = createWrapper();
    const activity = () => wrapper.get('[data-slot="record-activity"]');

    expect(activity().classes()).not.toContain('md:hidden');

    await wrapper.findAll('[role="tab"]')[1]!.trigger('click');
    expect(activity().classes()).toContain('md:hidden');
    expect(activity().find('[data-testid="activity"]').exists()).toBe(true);
  });

  it('places title actions beside the title when requested', async () => {
    const wrapper = createWrapper();
    await wrapper.setProps({ actionsBesideTitle: true });

    expect(wrapper.find('[data-slot="record-title"]').classes()).toContain('lg:row-start-2');
    expect(wrapper.find('[data-slot="record-identity"]').classes()).not.toContain('lg:min-h-42');
    expect(wrapper.find('header').html()).toContain('lg:items-start');
    expect(wrapper.find('header').html()).toContain('lg:col-start-2 lg:row-start-2');
  });

  it('reserves room for a three-line title only when the actions sit under it', () => {
    const wrapper = createWrapper();

    expect(wrapper.find('[data-slot="record-identity"]').classes()).toContain('lg:min-h-42');
  });

  it('wires the tabs to their panels and moves between them with the arrow keys', async () => {
    const wrapper = createWrapper();
    const tabs = () => wrapper.findAll('[role="tab"]');

    expect(wrapper.find('[role="tablist"]').exists()).toBe(true);
    expect(tabs()[0]!.attributes()).toMatchObject({ 'aria-controls': 'record-panel-agenda', 'tabindex': '0' });
    expect(tabs()[1]!.attributes('tabindex')).toBe('-1');
    expect(wrapper.find('#record-panel-files').exists()).toBe(true);

    await tabs()[0]!.trigger('keydown', { key: 'ArrowLeft' });
    expect(tabs()[2]!.attributes('aria-selected')).toBe('true');

    await tabs()[2]!.trigger('keydown', { key: 'Home' });
    expect(tabs()[0]!.attributes('aria-selected')).toBe('true');
  });

  it('keeps the sections and comments at least a screen tall so a short tab cannot pull the page up', () => {
    const wrapper = createWrapper();
    const body = wrapper.get('[data-slot="record-body"]');

    expect(body.classes().some(name => name.startsWith('md:min-h-'))).toBe(true);
    // The comments sit inside the reserved height, right after the first section.
    expect(body.find('[data-slot="record-activity"]').exists()).toBe(true);
  });

  /** Phones have no tabs: the select narrows the stacked sections to one, or shows them all. */
  it('narrows the phone view to the section picked in the switcher', async () => {
    const wrapper = createWrapper();
    const sectionOf = (key: string) => wrapper.get(`#record-panel-${key}`).classes();
    const select = wrapper.get('[data-slot="record-section-switcher"] select');

    expect((select.element as HTMLSelectElement).value).toBe('__all');
    expect(sectionOf('files')).not.toContain('max-md:hidden');

    await select.setValue('files');

    expect(sectionOf('files')).not.toContain('max-md:hidden');
    expect(sectionOf('agenda')).toContain('max-md:hidden');
    expect(wrapper.get('[data-slot="record-activity"]').classes()).toContain('max-md:hidden');
    expect(wrapper.emitted('update:section')?.at(-1)).toEqual(['files']);

    await select.setValue('__all');
    expect(sectionOf('agenda')).not.toContain('max-md:hidden');
  });

  it('opens a phone on the section a link points to', () => {
    const wrapper = mount(RecordPage, {
      props: {
        title: 'Senato posėdis',
        entityType: 'meeting',
        section: 'tasks',
        sections: [
          { value: 'agenda', label: 'Darbotvarkė' },
          { value: 'files', label: 'Failai' },
          { value: 'tasks', label: 'Užduotys', count: 4 },
        ],
      },
      global: { stubs: commonStubs },
    });

    const select = wrapper.get('[data-slot="record-section-switcher"] select');
    expect((select.element as HTMLSelectElement).value).toBe('tasks');
    expect(select.text()).toContain('Užduotys · 4');
    expect(wrapper.get('#record-panel-agenda').classes()).toContain('max-md:hidden');
  });

  /** Each tab a reader opens is a step back can undo, without a request. */
  it('records the open section in the URL as a history entry and follows back navigation', async () => {
    const page = reactive({ ...createMockPage(), url: '/mano/institutions/1' });
    vi.mocked(usePage).mockReturnValue(page as unknown as ReturnType<typeof usePage>);
    vi.mocked(router.push).mockClear();
    const wrapper = createWrapper();
    await wrapper.setProps({ sectionQueryKey: 'tab' });

    await wrapper.findAll('[role="tab"]')[2]!.trigger('click');
    expect(router.push).toHaveBeenCalledWith(expect.objectContaining({ url: '/mano/institutions/1?tab=tasks', preserveState: true }));

    page.url = '/mano/institutions/1?tab=files';
    await wrapper.vm.$nextTick();
    expect(wrapper.findAll('[role="tab"]')[1]!.attributes('aria-selected')).toBe('true');

    vi.mocked(router.push).mockClear();
    await wrapper.findAll('[role="tab"]')[0]!.trigger('click');
    expect(router.push).toHaveBeenCalledWith(expect.objectContaining({ url: '/mano/institutions/1' }));

    page.url = '/mano/institutions/1';
    await wrapper.vm.$nextTick();
    expect(router.push).toHaveBeenCalledTimes(1);
    expect(wrapper.findAll('[role="tab"]')[0]!.attributes('aria-selected')).toBe('true');
  });

  it('leaves the URL alone without a section key', async () => {
    vi.mocked(router.push).mockClear();
    const wrapper = createWrapper();

    await wrapper.findAll('[role="tab"]')[1]!.trigger('click');
    expect(router.push).not.toHaveBeenCalled();
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

    expect(wrapper.get('a[href="/mano/resources/1/edit"]').text()).toBe('Redaguoti');
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

it.each([false, true])('renders a fact with an unsupported URL as text when external is %s', (external) => {
  const wrapper = mount(RecordPage, {
    props: {
      title: 'Kontaktai', entityType: 'user',
      facts: [{ key: 'profile', label: 'Profilis', value: 'Facebook', href: 'java\tscript:void(0)', external }],
    },
    global: { stubs: commonStubs },
  });

  expect(wrapper.text()).toContain('Facebook');
  expect(wrapper.find('a[href*="script"]').exists()).toBe(false);
  wrapper.unmount();
});
