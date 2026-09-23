import { describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';

import SectionTabs from '../SectionTabs.vue';

import { atstovavimas, pradzia, rezervacijos, workspace } from './fixtures';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

describe('SectionTabs', () => {
  it('renders one tab per section of the workspace', () => {
    const wrapper = mount(SectionTabs, { props: { workspace: pradzia, activeSection: pradzia.sections[0] } });

    expect(wrapper.findAll('a').map(link => link.text())).toEqual([
      'shell.sections.apzvalga',
      'shell.sections.uzduotys',
      'shell.sections.pranesimai',
    ]);
    expect(wrapper.findAll('a svg')).toHaveLength(3);
    expect(wrapper.findAll('a').every(link => link.classes().includes('font-bold'))).toBe(true);
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
});
