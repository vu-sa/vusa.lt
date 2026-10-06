import { usePage } from '@inertiajs/vue3';
import { afterEach, describe, expect, it, vi } from 'vitest';
import { flushPromises, mount } from '@vue/test-utils';
import { reactive } from 'vue';

import SectionTabs from '../SectionTabs.vue';

import { atstovavimas, pradzia, rezervacijos, section, workspace } from './fixtures';

import { clearTrail, enterAgendaItem } from '@/Composables/useRecordTrail';
import { createMockPage } from '@/tests/helpers/createMockPage';
import { commonStubs } from '@/tests/stubs';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

describe('SectionTabs', () => {
  it('uses a translucent surface with an opaque high-contrast override', () => {
    const wrapper = mount(SectionTabs, { props: { workspace: atstovavimas } });
    const nav = wrapper.find('nav');

    expect(nav.classes()).toContain('bg-secondary/50');
    expect(nav.classes()).toContain('backdrop-blur-sm');
    expect(nav.classes()).toContain('[.a11y-contrast_&]:bg-background');
    expect(nav.classes()).toContain('[.a11y-contrast_&]:backdrop-blur-none');
  });

  it('renders one tab per section of the workspace', () => {
    const wrapper = mount(SectionTabs, { props: { workspace: pradzia, activeSection: pradzia.sections[0] } });

    expect(wrapper.findAll('li a').map(link => link.text())).toEqual([
      'shell.sections.apzvalga',
      'shell.sections.uzduotys',
      'shell.sections.pranesimai',
    ]);
    expect(wrapper.findAll('li a svg')).toHaveLength(3);
    expect(wrapper.findAll('li a').every(link => link.classes().includes('font-bold'))).toBe(true);
  });

  it('ends Pradžia\'s row with Visi skyriai, and no other workspace\'s', () => {
    const allSections = (props: Record<string, unknown>) =>
      mount(SectionTabs, { props }).find('[data-slot="section-tabs-all-sections"]');

    expect(allSections({ workspace: pradzia, activeSection: pradzia.sections[0] }).text()).toBe('shell.chrome.all_sections');
    expect(allSections({ workspace: atstovavimas, activeSection: atstovavimas.sections[0] }).exists()).toBe(false);
  });

  it('draws a separator before a section that starts a group, but never before the first tab', () => {
    const grouped = workspace('atstovavimas', [
      section('apzvalga', 'dashboard.atstovavimas', true),
      section('uzduociu_suvestine', 'tasks.summary'),
      section('institucijos', 'institutions.index', true),
    ]);
    const items = mount(SectionTabs, { props: { workspace: grouped } }).findAll('li');

    expect(items.map(item => item.classes().includes('border-l'))).toEqual([false, false, true]);
  });

  it('marks the active section with aria-current and a brand rule', () => {
    const wrapper = mount(SectionTabs, { props: { workspace: atstovavimas, activeSection: atstovavimas.sections[1] } });
    const [overview, meetings] = wrapper.findAll('a');

    expect(meetings.attributes('aria-current')).toBe('page');
    expect(meetings.classes()).toContain('border-brand-fill');
    expect(overview.attributes('aria-current')).toBeUndefined();
    expect(overview.classes()).toContain('border-transparent');
    expect(overview.classes()).toContain('hover:border-brand-fill');
    expect(overview.classes()).toContain('focus-visible:border-brand-fill');
  });

  it('prefetches tabs with a short fresh and stale cache window', () => {
    const wrapper = mount(SectionTabs, { props: { workspace: atstovavimas, activeSection: atstovavimas.sections[1] } });

    for (const link of wrapper.findAllComponents({ name: 'InertiaLink' })) {
      expect(link.props('prefetch')).toBe(true);
      expect(link.props('cacheFor')).toEqual(['15s', '1m']);
    }
  });

  it('collapses to nothing when the workspace has a single section', () => {
    const wrapper = mount(SectionTabs, { props: { workspace: rezervacijos } });

    expect(wrapper.find('nav').exists()).toBe(false);
  });

  it('renders nothing without a workspace', () => {
    expect(mount(SectionTabs).find('nav').exists()).toBe(false);
    expect(mount(SectionTabs, { props: { workspace: workspace('empty', []) } }).find('nav').exists()).toBe(false);
  });

  describe('on the institution → meeting → agenda item trail', () => {
    const visak = workspace('atstovavimas', [
      section('apzvalga', 'dashboard.atstovavimas'),
      section('institucijos', 'institutions.index'),
      section('posedziai', 'meetings.index'),
      section('darbotvarkes_klausimai', 'agendaItems.index'),
    ]);
    const meeting = { id: 'm1', start_time: '2026-09-12T10:00:00', institutions: [{ id: 'senatas', name: 'Vilniaus universiteto senatas' }] };

    const onAgendaItem = () => {
      vi.mocked(usePage).mockReturnValue(reactive({ ...createMockPage(), component: 'Admin/Representation/ShowAgendaItem' }) as ReturnType<typeof usePage>);
      clearTrail();
      enterAgendaItem({ id: 'a1', title: 'Dėl studijų programų' }, 3, meeting);
    };

    afterEach(() => {
      vi.restoreAllMocks();
      clearTrail();
    });

    it('lets the parent tabs stand in for their records, with the collection behind the chevron', () => {
      onAgendaItem();
      const wrapper = mount(SectionTabs, { props: { workspace: visak, activeSection: visak.sections[3] }, global: { stubs: commonStubs } });
      const [institution, meetingTab] = wrapper.findAll('[data-slot="section-tab-crumb"]');

      expect(wrapper.findAll('[data-slot="section-tab-crumb"]')).toHaveLength(3);
      expect(institution.find('a').attributes('href')).toBe('/mocked-route/institutions.show?institution=senatas');
      expect(institution.find('a .truncate').text()).toBe('Vilniaus universiteto senatas');
      expect(institution.find('[data-testid="dropdown-menu-content"] a').attributes('href')).toBe('/mocked-route/institutions.index');
      expect(meetingTab.find('a').attributes('href')).toBe('/mocked-route/meetings.show?meeting=m1');
      expect(meetingTab.find('a .truncate').text()).toBe('2026-09-12');
      expect(meetingTab.find('[data-testid="dropdown-menu-content"] a').attributes('href')).toBe('/mocked-route/meetings.index');
      expect(meetingTab.classes()).toContain('border-transparent');
      expect(meetingTab.find('a').attributes('aria-current')).toBeUndefined();
    });

    it('names the active tab after the current record, keeping its active rule', () => {
      onAgendaItem();
      const wrapper = mount(SectionTabs, { props: { workspace: visak, activeSection: visak.sections[3] }, global: { stubs: commonStubs } });
      const [overview, , , agendaItems] = wrapper.findAll('li');
      const current = agendaItems.find('[data-slot="section-tab-crumb"]');

      expect(overview.find('a').attributes('href')).toBe('/mocked-route/dashboard.atstovavimas');
      expect(current.classes()).toContain('border-brand-fill');
      expect(current.find('a').attributes('aria-current')).toBe('page');
      expect(current.find('a .truncate').text()).toBe('shell.trail.agenda_item');
      expect(current.find('[data-testid="dropdown-menu-content"] a').attributes('href')).toBe('/mocked-route/agendaItems.index');
    });

    it('offers the remembered child record from its parent page', () => {
      onAgendaItem();
      vi.mocked(usePage).mockReturnValue(reactive({ ...createMockPage(), component: 'Admin/Representation/ShowMeeting' }) as ReturnType<typeof usePage>);
      const wrapper = mount(SectionTabs, { props: { workspace: visak, activeSection: visak.sections[2] }, global: { stubs: commonStubs } });
      const agendaItems = wrapper.findAll('li')[3];

      expect(agendaItems.find('a').attributes('href')).toBe('/mocked-route/agendaItems.show?agendaItem=a1');
      expect(agendaItems.find('a .truncate').text()).toBe('shell.trail.agenda_item');
    });

    it('shows no crumbs once the user is off the record pages', () => {
      onAgendaItem();
      vi.mocked(usePage).mockReturnValue(reactive({ ...createMockPage(), component: 'Admin/Representation/IndexMeeting' }) as ReturnType<typeof usePage>);
      const wrapper = mount(SectionTabs, { props: { workspace: visak, activeSection: visak.sections[2] }, global: { stubs: commonStubs } });

      expect(wrapper.find('[data-slot="section-tab-crumb"]').exists()).toBe(false);
    });
  });

  describe('when the tabs do not fit', () => {
    const organizacija = workspace('organizacija', [
      section('apzvalga', 'dashboard.organizacija'),
      section('nariai', 'users.index'),
      section('pareigybes', 'duties.index'),
      section('institucijos', 'institutions.index'),
    ]);

    // jsdom has no layout: a 250px row of 100px tabs, so the last two overflow.
    const fakeLayout = () => {
      vi.spyOn(HTMLElement.prototype, 'clientWidth', 'get').mockImplementation(function (this: HTMLElement) {
        return this.tagName === 'UL' ? 250 : 100;
      });
      vi.spyOn(HTMLElement.prototype, 'getBoundingClientRect').mockImplementation(function (this: HTMLElement) {
        if (this.tagName !== 'LI') {
          return { left: 0, right: 250 } as DOMRect;
        }

        const index = [...(this.parentElement?.children ?? [])].indexOf(this);

        return { left: index * 100, right: (index + 1) * 100 } as DOMRect;
      });
    };

    const mountTabs = async (activeSection = organizacija.sections[0]) => {
      fakeLayout();
      const wrapper = mount(SectionTabs, { props: { workspace: organizacija, activeSection }, global: { stubs: commonStubs } });
      await flushPromises();

      return wrapper;
    };

    afterEach(() => vi.restoreAllMocks());

    it('hides the overflowing tabs and lists exactly those under Daugiau', async () => {
      const wrapper = await mountTabs();
      const tabs = wrapper.findAll('li');

      expect(tabs.map(tab => tab.classes().includes('invisible'))).toEqual([false, false, true, true]);
      expect(tabs[2].attributes('aria-hidden')).toBe('true');
      expect(tabs[2].find('a').attributes('tabindex')).toBe('-1');
      expect(wrapper.find('[data-testid="dropdown-menu-content"]').findAll('a').map(link => link.text()))
        .toEqual(['shell.sections.pareigybes', 'shell.sections.institucijos']);
      expect(wrapper.find('[data-slot="section-tabs-more"]').text()).toContain('shell.chrome.more_sections');
      expect(wrapper.find('[data-slot="section-tabs-more"]').attributes('aria-current')).toBeUndefined();
    });

    it('names the active section on the trigger when it has overflowed', async () => {
      const wrapper = await mountTabs(organizacija.sections[3]);
      const trigger = wrapper.find('[data-slot="section-tabs-more"]');

      expect(trigger.attributes('aria-current')).toBe('page');
      expect(trigger.classes()).toContain('border-brand-fill');
      expect(trigger.text()).toContain('shell.sections.institucijos');
    });
  });
});
